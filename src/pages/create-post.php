<?php
// Начинаем буферизацию вывода
ob_start();

$page_title = 'Создание новости';
include '../includes/header.php';
include '../config.php';

// Проверка прав доступа
if (!hasAccess('coach', $_SESSION['user_role'])) {
    header('Location: /404.php');
    exit();
}

// Определяем директорию для загрузки
$upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/news/';
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
        if (isset($_FILES['preview_image']) && $_FILES['preview_image']['error'] === UPLOAD_ERR_OK) {
            $preview_image = processImage($_FILES['preview_image'], $upload_dir, 'preview');
            if (!$preview_image) {
                throw new Exception("Ошибка при загрузке превью изображения");
            }
        } else {
            throw new Exception("Превью изображение обязательно");
        }
        
        // Создаем запись в базе данных со всеми необходимыми полями
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
        
        // Очищаем буфер и перенаправляем
        ob_end_clean();
        header("Location: /pages/post.php?id=$news_id");
        exit();
        
    } catch (Exception $e) {
        $conn->rollback();
        $error = "Произошла ошибка при создании новости: " . $e->getMessage();
    }
}

// Функция для обработки загруженного изображения
function processImage($file, $upload_dir, $prefix) {
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    if (!in_array($file['type'], $allowed_types)) {
        throw new Exception("Неподдерживаемый тип файла");
    }
    
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = $prefix . '_' . time() . '.' . $extension;
    $filepath = $upload_dir . $filename;
    
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        throw new Exception("Ошибка при сохранении файла");
    }
    
    // Возвращаем путь относительно корня сайта
    return '/uploads/news/' . $filename;
}
?>

<main>
    <div class="create-post-container">
        <h1>Создание новости</h1>
        
        <?php if (isset($error)): ?>
            <div class="error-message"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data" class="create-post-form">
            <div class="form-group">
                <label for="title">Заголовок:</label>
                <input type="text" id="title" name="title" required>
            </div>
            
            <div class="form-group">
                <label for="content">Содержание:</label>
                <textarea id="content" name="content" required></textarea>
            </div>
            
            <div class="form-group">
                <label for="preview_image">Превью изображение (обязательно):</label>
                <input type="file" id="preview_image" name="preview_image" accept="image/*" required>
                <div class="image-preview" id="preview-image-preview"></div>
            </div>
            
            <div class="form-group">
                <label for="extra_images">Дополнительные изображения:</label>
                <input type="file" id="extra_images" name="extra_images[]" accept="image/*" multiple>
                <div class="image-preview" id="extra-images-preview"></div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Опубликовать</button>
                <a href="/pages/news.php" class="btn btn-secondary">Отмена</a>
            </div>
        </form>
    </div>
</main>

<script>
// Предпросмотр превью изображения
document.getElementById('preview_image').addEventListener('change', function() {
    const preview = document.getElementById('preview-image-preview');
    if (this.files && this.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.innerHTML = `<img src="${e.target.result}" alt="Preview">`;
        }
        reader.readAsDataURL(this.files[0]);
    } else {
        preview.innerHTML = '';
    }
});

// Предпросмотр дополнительных изображений
document.getElementById('extra_images').addEventListener('change', function() {
    const preview = document.getElementById('extra-images-preview');
    preview.innerHTML = '';
    
    if (this.files) {
        Array.from(this.files).forEach(file => {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.innerHTML += `
                    <div class="extra-image-preview">
                        <img src="${e.target.result}" alt="Extra image">
                    </div>
                `;
            }
            reader.readAsDataURL(file);
        });
    }
});
</script>

<?php include '../includes/footer.php'; ?> 