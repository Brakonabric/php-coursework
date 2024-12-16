<?php
ob_start();
$page_title = 'Rediģēt ziņu';
include '../../../includes/header.php';
include '../../../config.php';

if (!hasAccess('coach', $_SESSION['user_role'])) {
    header('Location: /404.php');
    exit();
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
        
        if (isset($_POST['delete_extra_images']) && !empty($_POST['delete_extra_images'])) {
            $extra_images = json_decode($post['image_path_extra'], true) ?? [];
            $new_extra_images = [];
            
            foreach ($extra_images as $img) {
                if (!in_array($img, $_POST['delete_extra_images'])) {
                    $new_extra_images[] = $img;
                } else {
                    @unlink($_SERVER['DOCUMENT_ROOT'] . $img);
                }
            }
            
            $extra_images_json = json_encode($new_extra_images);
            $sql = "UPDATE news SET image_path_extra = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('si', $extra_images_json, $post_id);
            $stmt->execute();
        }
        
        $sql = "UPDATE news SET title = ?, content = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ssi', $title, $content, $post_id);
        $stmt->execute();
        
        if (isset($_FILES['preview_image']) && $_FILES['preview_image']['error'] === UPLOAD_ERR_OK) {
            if ($_FILES['preview_image']['size'] > 10 * 1024 * 1024) {
                throw new Exception("Faila izmērs pārsniedz 10MB. Lūdzu, samaziniet attēla izmēru.");
            }
            
            $preview_image = processImage($_FILES['preview_image'], $upload_dir, $post_id . '_preview');
            
            if ($preview_image) {
                if ($post['image_path_preview']) {
                    @unlink($_SERVER['DOCUMENT_ROOT'] . $post['image_path_preview']);
                }
                
                $sql = "UPDATE news SET image_path_preview = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('si', $preview_image, $post_id);
                $stmt->execute();
            }
        }
        
        if (isset($_FILES['extra_images']) && $_FILES['extra_images']['error'][0] !== UPLOAD_ERR_NO_FILE) {
            $extra_images = [];
            
            if ($post['image_path_extra']) {
                $extra_images = json_decode($post['image_path_extra'], true) ?? [];
            }
            
            foreach ($_FILES['extra_images']['tmp_name'] as $key => $tmp_name) {
                if ($_FILES['extra_images']['error'][$key] === UPLOAD_ERR_OK) {
                    if ($_FILES['extra_images']['size'][$key] > 10 * 1024 * 1024) {
                        continue;
                    }
                    
                    $file = [
                        'name' => $_FILES['extra_images']['name'][$key],
                        'type' => $_FILES['extra_images']['type'][$key],
                        'tmp_name' => $tmp_name,
                        'error' => $_FILES['extra_images']['error'][$key],
                        'size' => $_FILES['extra_images']['size'][$key]
                    ];
                    
                    $extra_image = processImage($file, $upload_dir, $post_id . '_extra_' . time() . '_' . $key);
                    if ($extra_image) {
                        $extra_images[] = $extra_image;
                    }
                }
            }
            
            $extra_images_json = json_encode($extra_images);
            $sql = "UPDATE news SET image_path_extra = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('si', $extra_images_json, $post_id);
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
                <label for="preview_image">Priekšskatījuma attēls:</label>
                <div class="file-upload-wrapper">
                    <label for="preview_image" class="file-input-label">
                        <span class="material-icons">add_photo_alternate</span>
                        <span class="label-text">Izvēlēties jaunu attēlu</span>
                    </label>
                    <input type="file" id="preview_image" name="preview_image" accept="image/*">
                    <div class="file-info">
                        <span class="material-icons">info</span>
                        <small class="form-text">Atbalstītie formāti: JPEG, PNG, GIF. Maksimālais izmērs: 10MB</small>
                    </div>
                </div>
                <?php if ($post['image_path_preview']): ?>
                    <div class="current-image">
                        <p>Pašreizējais attēls:</p>
                        <div class="image-container">
                            <img src="<?= htmlspecialchars($post['image_path_preview']) ?>" alt="Pašreizējais priekšskatījums">
                            <input type="hidden" name="current_preview_image" value="<?= htmlspecialchars($post['image_path_preview']) ?>">
                            <button type="button" class="delete-image" data-image-type="preview">
                                <span class="material-icons">close</span>
                            </button>
                        </div>
                    </div>
                <?php endif; ?>
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
                        <small class="form-text">Atbalstītie formāti: JPEG, PNG, GIF. Maksimālais izmērs katram: 10MB</small>
                    </div>
                </div>
                <?php if ($post['image_path_extra']): ?>
                    <div class="current-images">
                        <p>Pašreizējie papildu attēli:</p>
                        <div class="images-grid">
                            <?php foreach (json_decode($post['image_path_extra'], true) as $image): ?>
                                <div class="image-container">
                                    <img src="<?= htmlspecialchars($image) ?>" alt="Papildu attēls">
                                    <input type="hidden" name="current_extra_images[]" value="<?= htmlspecialchars($image) ?>">
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
                <button type="submit" class="btn btn-primary">
                    <span class="material-icons">save</span>
                    Saglabāt izmaiņas
                </button>
                <a href="/pages/news/post.php?id=<?= $post_id ?>" class="btn btn-secondary">
                    <span class="material-icons">close</span>
                    Atcelt
                </a>
            </div>
        </form>
    </div>
</main>

<script>
document.getElementById('preview_image').addEventListener('change', function() {
    const preview = document.getElementById('preview-image-preview');
    const file = this.files[0];
    const label = this.previousElementSibling;
    
    if (file) {
        if (file.size > 10 * 1024 * 1024) {
            alert('Faila izmērs pārsniedz 10MB. Lūdzu, izvēlieties mazāku failu.');
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

document.getElementById('extra_images').addEventListener('change', function() {
    const preview = document.getElementById('extra-images-preview');
    const files = Array.from(this.files);
    const label = this.previousElementSibling;
    
    preview.innerHTML = '';
    const validFiles = files.filter(file => {
        if (file.size > 10 * 1024 * 1024) {
            alert(`Fails "${file.name}" pārsniedz 10MB un tiks izlaists.`);
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

// Добавляем обработчики для удаления изображений
document.querySelectorAll('.delete-image').forEach(button => {
    button.addEventListener('click', function() {
        if (!confirm('Vai tiešām vēlaties dzēst šo attēlu?')) {
            return;
        }

        const container = this.closest('.image-container');
        const imageType = this.dataset.imageType;
        const imageInput = container.querySelector('input[type="hidden"]');

        if (imageType === 'preview') {
            // Создаем скрытое поле для удаления превью
            const deleteInput = document.createElement('input');
            deleteInput.type = 'hidden';
            deleteInput.name = 'delete_preview_image';
            deleteInput.value = '1';
            container.closest('form').appendChild(deleteInput);
        } else if (imageType === 'extra') {
            // Создаем скрытое поле для удаления дополнительного изображения
            const deleteInput = document.createElement('input');
            deleteInput.type = 'hidden';
            deleteInput.name = 'delete_extra_images[]';
            deleteInput.value = imageInput.value;
            container.closest('form').appendChild(deleteInput);
        }

        // Скрываем контейнер с изображением
        container.style.display = 'none';
    });
});
</script>

<?php include '../../../includes/footer.php'; ?> 