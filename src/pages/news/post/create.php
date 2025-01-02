<?php
session_start();
ob_start();

$pageTitle = 'Izveidot ziņu';
include '../../../config.php';

if (isset($_SERVER['CONTENT_LENGTH'])) {
    $postSize = (int)$_SERVER['CONTENT_LENGTH'];
    $maxPostSize = min(
        return_bytes(ini_get('post_max_size')),
        return_bytes(ini_get('upload_max_filesize'))
    );
    
    if ($postSize > $maxPostSize) {
        ob_end_clean();
        die('Kopējais augšupielādēto failu izmērs pārsniedz ' . formatBytes($maxPostSize) . ' limitu.');
    }
}

function return_bytes($val) {
    $val = trim($val);
    $last = strtolower($val[strlen($val)-1]);
    $val = (int)$val;
    switch($last) {
        case 'g':
            $val *= 1024;
        case 'm':
            $val *= 1024;
        case 'k':
            $val *= 1024;
    }
    return $val;
}

function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= (1 << (10 * $pow));
    return round($bytes, $precision) . ' ' . $units[$pow];
}

include '../../../includes/header.php';

if (!hasAccess('coach', $_SESSION['userRole'])) {
    header('Location: /pages/news.php');
    exit('Piekļuve liegta');
}

$uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/assets/images/uploads/news/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
    chmod($uploadDir, 0777);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $conn->real_escape_string($_POST['title']);
    $content = $conn->real_escape_string($_POST['content']);
    $authorId = $_SESSION['userId'];
    
    $conn->begin_transaction();
    
    try {
        $previewImage = null;
        $extraImages = [];

        if (isset($_FILES['preview_image'])) {
            if ($_FILES['preview_image']['error'] === UPLOAD_ERR_OK) {
                if ($_FILES['preview_image']['size'] > 15 * 1024 * 1024) {
                    throw new Exception("Faila izmērs pārsniedz 15MB. Lūdzu, samaziniet attēla izmēru.");
                }
                
                $previewImage = processImage($_FILES['preview_image'], $uploadDir, 'preview');
                if (!$previewImage) {
                    throw new Exception("Kļūda, augšupielādējot priekšskatījuma attēlu");
                }
            } else {
                $uploadErrors = array(
                    UPLOAD_ERR_INI_SIZE => 'Faila izmērs pārsniedz upload_max_filesize',
                    UPLOAD_ERR_FORM_SIZE => 'Faila izmērs pārsniedz MAX_FILE_SIZE',
                    UPLOAD_ERR_PARTIAL => 'Fails tika augšupielādēts tikai daļēji',
                    UPLOAD_ERR_NO_FILE => 'Fails netika augšupielādēts',
                    UPLOAD_ERR_NO_TMP_DIR => 'Nav pagaidu mapes',
                    UPLOAD_ERR_CANT_WRITE => 'Neizdevās ierakstīt failu diskā',
                    UPLOAD_ERR_EXTENSION => 'PHP paplašinājums apturēja faila augšupielādi',
                );
                $errorMessage = isset($uploadErrors[$_FILES['preview_image']['error']]) 
                    ? $uploadErrors[$_FILES['preview_image']['error']] 
                    : 'Nezināma augšupielādes kļūda';
                throw new Exception($errorMessage);
            }
        } else {
            throw new Exception("Priekšskatījuma attēls ir obligāts");
        }

        $sql = "INSERT INTO news (title, content, user_id, image_path_preview) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ssis', $title, $content, $authorId, $previewImage);
        $stmt->execute();
        $newsId = $conn->insert_id;

        if (isset($_FILES['extra_images'])) {
            $fileCount = count($_FILES['extra_images']['name']);
            for ($i = 0; $i < $fileCount; $i++) {
                if ($_FILES['extra_images']['error'][$i] === UPLOAD_ERR_OK) {
                    if ($_FILES['extra_images']['size'][$i] > 10 * 1024 * 1024) {
                        continue;
                    }
                    
                    $file = [
                        'name' => $_FILES['extra_images']['name'][$i],
                        'type' => $_FILES['extra_images']['type'][$i],
                        'tmp_name' => $_FILES['extra_images']['tmp_name'][$i],
                        'error' => $_FILES['extra_images']['error'][$i],
                        'size' => $_FILES['extra_images']['size'][$i]
                    ];
                    
                    $extraImage = processImage($file, $uploadDir, $newsId . '_extra_' . $i);
                    if ($extraImage) {
                        $extraImages[] = $extraImage;
                    }
                }
            }
        }

        if (!empty($extraImages)) {
            $extraImagesJson = json_encode($extraImages);
            $sql = "UPDATE news SET image_path_extra = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('si', $extraImagesJson, $newsId);
            $stmt->execute();
        }
        
        $conn->commit();
        ob_end_clean();
        header("Location: /pages/news/post.php?id=$newsId");
        exit();
        
    } catch (Exception $e) {
        $conn->rollback();
        $error = "Radās kļūda, veidojot ziņu: " . $e->getMessage();
    }
}

function processImage($file, $uploadDir, $prefix) {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    if (!in_array($file['type'], $allowedTypes)) {
        throw new Exception("Neatbalstīts faila tips. Atļauti tikai JPEG, PNG un GIF");
    }
    
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = $prefix . '_' . time() . '.' . $extension;
    $filepath = $uploadDir . $filename;
    
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        throw new Exception("Kļūda, saglabājot failu");
    }
    
    return '/assets/images/uploads/news/' . $filename;
}
?>

<main>
    <div class="create-post-container">
        <h1>Izveidot ziņu</h1>
        
        <?php if (isset($error)): ?>
            <div class="error-message"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data" class="create-post-form">
            <div class="form-group">
                <label for="title">Virsraksts:</label>
                <input type="text" id="title" name="title" required>
            </div>
            
            <div class="form-group">
                <label for="content">Saturs:</label>
                <textarea id="content" name="content" required></textarea>
            </div>
            
            <div class="form-group">
                <label for="preview_image">Priekšskatījuma attēls (obligāts):</label>
                <div class="file-upload-wrapper">
                    <label for="preview_image" class="file-input-label">
                        <span class="material-icons">add_photo_alternate</span>
                        <span class="label-text">Izvēlēties attēlu</span>
                    </label>
                    <input type="file" id="preview_image" name="preview_image" accept="image/*" required>
                    <div class="file-info">
                        <span class="material-icons">info</span>
                        <small class="form-text">Atbalstītie formāti: JPEG, PNG, GIF. Maksimālais izmērs: 15MB</small>
                    </div>
                </div>
                <div class="image-preview" id="preview-image-preview"></div>
            </div>
            
            <div class="form-group">
                <label for="extra_images">Papildu attēli:</label>
                <div class="file-upload-wrapper">
                    <label for="extra_images" class="file-input-label">
                        <span class="material-icons">photo_library</span>
                        <span class="label-text">Pievienot vairākus attēlus</span>
                    </label>
                    <input type="file" id="extra_images" name="extra_images[]" accept="image/*" multiple>
                    <div class="file-info">
                        <span class="material-icons">info</span>
                        <small class="form-text">Atbalstītie formāti: JPEG, PNG, GIF. Maksimālais izmērs katram: 15MB</small>
                    </div>
                </div>
                <div class="image-preview" id="extra-images-preview"></div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-success">
                    <span class="material-icons">publish</span>
                    Publicēt
                </button>
                <a href="/pages/news.php" class="link-btn btn-danger">
                    <span class="material-icons">close</span>
                    Atcelt
                </a>
            </div>
        </form>
    </div>
</main>

<script>
const MAX_TOTAL_SIZE = 10 * 1024 * 1024;
let totalSize = 0;

document.getElementById('preview_image').addEventListener('change', function() {
    const preview = document.getElementById('preview-image-preview');
    const file = this.files[0];
    const label = this.previousElementSibling;
    
    if (file) {
        if (file.size > MAX_TOTAL_SIZE) {
            alert('Faila izmērs pārsniedz 15MB. Lūdzu, izvēlieties mazāku failu.');
            this.value = '';
            preview.innerHTML = '';
            label.querySelector('.label-text').textContent = 'Izvēlēties attēlu';
            return;
        }
        
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.innerHTML = `<div class="preview-item">
                <img src="${e.target.result}" alt="Preview">
                <div class="preview-info">
                    <span class="file-name">${file.name}</span>
                    <span class="file-size">${(file.size / (1024 * 1024)).toFixed(2)} MB</span>
                </div>
            </div>`;
        }
        reader.readAsDataURL(file);
        label.querySelector('.label-text').textContent = file.name;
        totalSize = file.size;
    } else {
        preview.innerHTML = '';
        label.querySelector('.label-text').textContent = 'Izvēlēties attēlu';
        totalSize = 0;
    }
});

document.getElementById('extra_images').addEventListener('change', function() {
    const preview = document.getElementById('extra-images-preview');
    const files = Array.from(this.files);
    const label = this.previousElementSibling;
    let extraSize = 0;
    
    preview.innerHTML = '';
    const validFiles = files.filter(file => {
        if (file.size + totalSize + extraSize > MAX_TOTAL_SIZE) {
            alert(`Kopējais failu izmērs pārsniedz 15MB limitu. Fails "${file.name}" tiks izlaists.`);
            return false;
        }
        extraSize += file.size;
        return true;
    });
    
    if (validFiles.length > 0) {
        label.querySelector('.label-text').textContent = `Izvēlēti ${validFiles.length} attēli`;
        
        validFiles.forEach(file => {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.innerHTML += `<div class="preview-item">
                    <img src="${e.target.result}" alt="Extra image">
                    <div class="preview-info">
                        <span class="file-name">${file.name}</span>
                        <span class="file-size">${(file.size / (1024 * 1024)).toFixed(2)} MB</span>
                    </div>
                </div>`;
            }
            reader.readAsDataURL(file);
        });
    } else {
        label.querySelector('.label-text').textContent = 'Pievienot vairākus attēlus';
    }
});

document.querySelector('.create-post-form').addEventListener('submit', function(e) {
    let totalUploadSize = 0;
    const previewFile = document.getElementById('preview_image').files[0];
    const extraFiles = document.getElementById('extra_images').files;
    
    if (previewFile) {
        totalUploadSize += previewFile.size;
    }
    
    for (let file of extraFiles) {
        totalUploadSize += file.size;
    }
    
    if (totalUploadSize > MAX_TOTAL_SIZE) {
        e.preventDefault();
        alert('Kopējais failu izmērs pārsniedz 15MB limitu. Lūdzu, samaziniet failu izmēru vai izvēlieties mazāk failu.');
    }
});
</script>

<?php include '../../../includes/footer.php'; ?> 