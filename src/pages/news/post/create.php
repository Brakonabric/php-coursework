<?php
// Начинаем буферизацию вывода
ob_start();

$page_title = 'Izveidot ziņu';
include '../../../includes/header.php';
include '../../../config.php';

// Проверка прав доступа
if (!hasAccess('coach', $_SESSION['user_role'])) {
    header('Location: /404.php');
    exit();
}

// Определяем директорию для загрузки
$upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/assets/images/uploads/news/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
    chmod($upload_dir, 0777);
}

// Обработка отправки формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $conn->real_escape_string($_POST['title']);
    $content = $conn->real_escape_string($_POST['content']);
    $author_id = $_SESSION['user_id'];
    
    $conn->begin_transaction();
    
    try {
        // Сначала обрабатываем превью изображение
        $preview_image = null;
        $extra_images = [];
        
        // Обработка превью изображения
        if (isset($_FILES['preview_image'])) {
            if ($_FILES['preview_image']['error'] === UPLOAD_ERR_OK) {
                // Проверяем размер файла (10MB)
                if ($_FILES['preview_image']['size'] > 10 * 1024 * 1024) {
                    throw new Exception("Faila izmērs pārsniedz 10MB. Lūdzu, samaziniet attēla izmēru.");
                }
                
                $preview_image = processImage($_FILES['preview_image'], $upload_dir, 'preview');
                if (!$preview_image) {
                    throw new Exception("Kļūda, augšupielādējot priekšskatījuma attēlu");
                }
            } else {
                $upload_errors = array(
                    UPLOAD_ERR_INI_SIZE => 'Faila izmērs pārsniedz upload_max_filesize',
                    UPLOAD_ERR_FORM_SIZE => 'Faila izmērs pārsniedz MAX_FILE_SIZE',
                    UPLOAD_ERR_PARTIAL => 'Fails tika augšupielādēts tikai daļēji',
                    UPLOAD_ERR_NO_FILE => 'Fails netika augšupielādēts',
                    UPLOAD_ERR_NO_TMP_DIR => 'Nav pagaidu mapes',
                    UPLOAD_ERR_CANT_WRITE => 'Neizdevās ierakstīt failu diskā',
                    UPLOAD_ERR_EXTENSION => 'PHP paplašinājums apturēja faila augšupielādi',
                );
                $error_message = isset($upload_errors[$_FILES['preview_image']['error']]) 
                    ? $upload_errors[$_FILES['preview_image']['error']] 
                    : 'Nezināma augšupielādes kļūda';
                throw new Exception($error_message);
            }
        } else {
            throw new Exception("Priekšskatījuma attēls ir obligāts");
        }
        
        // Создаем запись в базе данных
        $sql = "INSERT INTO news (title, content, user_id, image_path_preview) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ssis', $title, $content, $author_id, $preview_image);
        $stmt->execute();
        $news_id = $conn->insert_id;
        
        // Обработка дополнительных изображений
        if (isset($_FILES['extra_images'])) {
            $file_count = count($_FILES['extra_images']['name']);
            for ($i = 0; $i < $file_count; $i++) {
                if ($_FILES['extra_images']['error'][$i] === UPLOAD_ERR_OK) {
                    // Проверяем размер файла
                    if ($_FILES['extra_images']['size'][$i] > 10 * 1024 * 1024) {
                        continue; // Пропускаем файлы больше 10MB
                    }
                    
                    $file = [
                        'name' => $_FILES['extra_images']['name'][$i],
                        'type' => $_FILES['extra_images']['type'][$i],
                        'tmp_name' => $_FILES['extra_images']['tmp_name'][$i],
                        'error' => $_FILES['extra_images']['error'][$i],
                        'size' => $_FILES['extra_images']['size'][$i]
                    ];
                    
                    $extra_image = processImage($file, $upload_dir, $news_id . '_extra_' . $i);
                    if ($extra_image) {
                        $extra_images[] = $extra_image;
                    }
                }
            }
        }
        
        // Обновляем запись только если есть дополнительные изображения
        if (!empty($extra_images)) {
            $extra_images_json = json_encode($extra_images);
            $sql = "UPDATE news SET image_path_extra = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('si', $extra_images_json, $news_id);
            $stmt->execute();
        }
        
        $conn->commit();
        ob_end_clean();
        header("Location: /pages/news/post.php?id=$news_id");
        exit();
        
    } catch (Exception $e) {
        $conn->rollback();
        $error = "Radās kļūda, veidojot ziņu: " . $e->getMessage();
    }
}

// Функция для обработки загруженного изображения
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
                        <small class="form-text">Atbalstītie formāti: JPEG, PNG, GIF. Maksimālais izmērs: 10MB</small>
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
                        <small class="form-text">Atbalstītie formāti: JPEG, PNG, GIF. Maksimālais izmērs katram: 10MB</small>
                    </div>
                </div>
                <div class="image-preview" id="extra-images-preview"></div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <span class="material-icons">publish</span>
                    Publicēt
                </button>
                <a href="/pages/news.php" class="btn btn-secondary">
                    <span class="material-icons">close</span>
                    Atcelt
                </a>
            </div>
        </form>
    </div>
</main>

<script>
// Предпросмотр превью изображения
document.getElementById('preview_image').addEventListener('change', function() {
    const preview = document.getElementById('preview-image-preview');
    const file = this.files[0];
    const label = this.previousElementSibling;
    
    if (file) {
        if (file.size > 10 * 1024 * 1024) {
            alert('Faila izmērs pārsniedz 10MB. Lūdzu, izvēlieties mazāku failu.');
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
    } else {
        preview.innerHTML = '';
        label.querySelector('.label-text').textContent = 'Izvēlēties attēlu';
    }
});

// Предпросмотр дополнительных изображений
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
</script>

<?php include '../../../includes/footer.php'; ?> 