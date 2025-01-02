<?php
ob_start();
$page_title = 'Rediģēt ziņu';
include '../../../includes/header.php';
include '../../../config.php';

if (!hasAccess('coach', $_SESSION['userRole'])) {
    header('Location: /pages/news.php');
    exit('Piekļuve liegta');
}

$upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/assets/images/uploads/news/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
    chmod($upload_dir, 0777);
}

$post_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$sql = "SELECT * FROM news WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $post_id);
$stmt->execute();
$result = $stmt->get_result();
$post = $result->fetch_assoc();

if (!$post) {
    header('Location: /404.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $title = trim($_POST['title']);
        $content = trim($_POST['content']);
        
        $conn->begin_transaction();
        
        if (isset($_POST['delete_preview_image']) && $post['image_path_preview']) {
            @unlink($_SERVER['DOCUMENT_ROOT'] . $post['image_path_preview']);
            $sql = "UPDATE news SET image_path_preview = NULL WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('i', $post_id);
            $stmt->execute();
        }
        
        if (isset($_POST['delete_extraImages']) && !empty($_POST['delete_extraImages'])) {
            $extraImages = json_decode($post['image_path_extra'], true) ?? [];
            $newExtraImages = [];
            
            foreach ($extraImages as $img) {
                if (!in_array($img, $_POST['delete_extraImages'])) {
                    $newExtraImages[] = $img;
                } else {
                    $sql = "DELETE FROM gallery WHERE image_path = ? AND source_type = 'news' AND source_id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param('si', $img, $post_id);
                    $stmt->execute();
                    
                    @unlink($_SERVER['DOCUMENT_ROOT'] . $img);
                }
            }
            
            $extraImagesJson = empty($newExtraImages) ? null : json_encode($newExtraImages);
            $sql = "UPDATE news SET image_path_extra = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('si', $extraImagesJson, $post_id);
            $stmt->execute();
            
            $post['image_path_extra'] = $extraImagesJson;
        }
        
        $sql = "UPDATE news SET title = ?, content = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ssi', $title, $content, $post_id);
        $stmt->execute();
        
        if (isset($_FILES['previewImage']) && $_FILES['previewImage']['error'] === UPLOAD_ERR_OK) {
            if ($_FILES['previewImage']['size'] > 15 * 1024 * 1024) {
                throw new Exception("Faila izmērs pārsniedz 15MB. Lūdzu, samaziniet attēla izmēru.");
            }
            
            $previewImage = processImage($_FILES['previewImage'], $upload_dir, $post_id . '_preview');
            
            if ($previewImage) {
                if ($post['image_path_preview']) {
                    @unlink($_SERVER['DOCUMENT_ROOT'] . $post['image_path_preview']);
                }
                
                $sql = "UPDATE news SET image_path_preview = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('si', $previewImage, $post_id);
                $stmt->execute();
            }
        }
        
        if (isset($_FILES['extraImages']) && $_FILES['extraImages']['error'][0] !== UPLOAD_ERR_NO_FILE) {
            $extraImages = [];
            
            if ($post['image_path_extra']) {
                $extraImages = json_decode($post['image_path_extra'], true) ?? [];
            }
            
            foreach ($_FILES['extraImages']['tmp_name'] as $key => $tmpName) {
                if ($_FILES['extraImages']['error'][$key] === UPLOAD_ERR_OK) {
                    if ($_FILES['extraImages']['size'][$key] > 10 * 1024 * 1024) {
                        continue;
                    }
                    
                    $file = [
                        'name' => $_FILES['extraImages']['name'][$key],
                        'type' => $_FILES['extraImages']['type'][$key],
                        'tmp_name' => $tmpName,
                        'error' => $_FILES['extraImages']['error'][$key],
                        'size' => $_FILES['extraImages']['size'][$key]
                    ];
                    
                    $extraImage = processImage($file, $upload_dir, $post_id . '_extra_' . time() . '_' . $key);
                    if ($extraImage) {
                        $extraImages[] = $extraImage;
                    }
                }
            }
            
            $extraImagesJson = empty($extraImages) ? null : json_encode($extraImages);
            $sql = "UPDATE news SET image_path_extra = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('si', $extraImagesJson, $post_id);
            $stmt->execute();
        }
        
        $conn->commit();
        ob_end_clean();
        header("Location: /pages/news/post.php?id=$post_id");
        exit();
        
    } catch (Exception $e) {
        $conn->rollback();
        $error = "Kļūda, atjauninot ziņu: " . $e->getMessage();
    }
}

function processImage($file, $upload_dir, $prefix) {
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    if (!in_array($file['type'], $allowed_types)) {
        throw new Exception("Neatbalstīts faila tips. Atļauti tikai JPEG, PNG un GIF");
    }
    
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = $prefix . '_' . time() . '.' . $extension;
    $filepath = $upload_dir . $filename;
    
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        throw new Exception("Kļūda, saglabājot failu");
    }
    
    return '/assets/images/uploads/news/' . $filename;
}
?>

<main>
    <div class="create-post-container">
        <h1>Rediģēt ziņu</h1>
        
        <?php if (isset($error)): ?>
            <div class="error-message"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data" class="create-post-form">
            <div class="form-group">
                <label for="title">Virsraksts:</label>
                <input type="text" id="title" name="title" required 
                       value="<?= htmlspecialchars($post['title']) ?>">
            </div>
            
            <div class="form-group">
                <label for="content">Saturs:</label>
                <textarea id="content" name="content" required><?= htmlspecialchars($post['content']) ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="previewImage">Priekšskatījuma attēls:</label>
                <div class="file-upload-wrapper">
                    <label for="previewImage" class="file-input-label">
                        <span class="material-icons">add_photo_alternate</span>
                        <span class="label-text">Izvēlēties jaunu attēlu</span>
                    </label>
                    <input type="file" id="previewImage" name="previewImage" accept="image/*">
                    <div class="file-info">
                        <span class="material-icons">info</span>
                        <small class="form-text">Atbalstītie formāti: JPEG, PNG, GIF. Maksimālais izmērs: 15MB</small>
                    </div>
                </div>
                <?php if ($post['image_path_preview']): ?>
                    <div class="current-image">
                        <p>Pašreizējais attēls:</p>
                        <div class="image-container">
                            <img src="<?= htmlspecialchars($post['image_path_preview']) ?>" alt="Pašreizējais priekšskatījums">
                            <input type="hidden" name="currentPreviewImage" value="<?= htmlspecialchars($post['image_path_preview']) ?>">
                            <button type="button" class="delete-image" data-image-type="preview">
                                <span class="material-icons">close</span>
                            </button>
                        </div>
                    </div>
                <?php endif; ?>
                <div class="image-preview" id="preview-image-preview"></div>
            </div>
            
            <div class="form-group">
                <label for="extraImages">Papildu attēli:</label>
                <div class="file-upload-wrapper">
                    <label for="extraImages" class="file-input-label">
                        <span class="material-icons">photo_library</span>
                        <span class="label-text">Pievienot vairākus attēlus</span>
                    </label>
                    <input type="file" id="extraImages" name="extraImages[]" accept="image/*" multiple>
                    <div class="file-info">
                        <span class="material-icons">info</span>
                        <small class="form-text">Atbalstītie formāti: JPEG, PNG, GIF. Maksimālais izmērs katram: 15MB</small>
                    </div>
                </div>
                <?php if ($post['image_path_extra']): ?>
                    <div class="current-images">
                        <p>Pašreizējie papildu attēli:</p>
                        <div class="images-grid">
                            <?php foreach (json_decode($post['image_path_extra'], true) as $image): ?>
                                <div class="image-container">
                                    <img src="<?= htmlspecialchars($image) ?>" alt="Papildu attēls">
                                    <input type="hidden" name="currentExtraImages[]" value="<?= htmlspecialchars($image) ?>">
                                    <button type="button" class="delete-image" data-image-type="extra">
                                        <span class="material-icons">close</span>
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                <div class="image-preview" id="extra-images-preview"></div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-success">
                    <span class="material-icons">save</span>
                    Saglabāt izmaiņas
                </button>
                <a href="/pages/news/post.php?id=<?= $post_id ?>" class="link-btn btn-danger">
                    <span class="material-icons">close</span>
                    Atcelt
                </a>
            </div>
        </form>
    </div>
</main>

<script>
document.getElementById('previewImage').addEventListener('change', function() {
    const preview = document.getElementById('preview-image-preview');
    const file = this.files[0];
    const label = this.previousElementSibling;
    
    if (file) {
        if (file.size > 15 * 1024 * 1024) {
            alert('Faila izmērs pārsniedz 15MB. Lūdzu, izvēlieties mazāku failu.');
            this.value = '';
            preview.innerHTML = '';
            label.querySelector('.label-text').textContent = 'Izvēlēties jaunu attēlu';
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
    } else {
        preview.innerHTML = '';
        label.querySelector('.label-text').textContent = 'Izvēlēties jaunu attēlu';
    }
});

document.getElementById('extraImages').addEventListener('change', function() {
    const preview = document.getElementById('extra-images-preview');
    const files = Array.from(this.files);
    const label = this.previousElementSibling;
    
    preview.innerHTML = '';
    const validFiles = files.filter(file => {
        if (file.size > 15 * 1024 * 1024) {
            alert(`Fails "${file.name}" pārsniedz 15MB un tiks izlaists.`);
            return false;
        }
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

document.querySelectorAll('.delete-image').forEach(button => {
    button.addEventListener('click', function() {
        if (!confirm('Vai tiešām vēlaties dzēst šo attēlu?')) {
            return;
        }

        const container = this.closest('.image-container');
        const imageType = this.dataset.imageType;
        const imageInput = container.querySelector('input[type="hidden"]');

        if (imageType === 'preview') {
            const deleteInput = document.createElement('input');
            deleteInput.type = 'hidden';
            deleteInput.name = 'delete_preview_image';
            deleteInput.value = '1';
            container.closest('form').appendChild(deleteInput);
        } else if (imageType === 'extra') {
            const deleteInput = document.createElement('input');
            deleteInput.type = 'hidden';
            deleteInput.name = 'delete_extraImages[]';
            deleteInput.value = imageInput.value;
            container.closest('form').appendChild(deleteInput);
        }

        container.style.display = 'none';
    });
});
</script>

<?php include '../../../includes/footer.php'; ?> 