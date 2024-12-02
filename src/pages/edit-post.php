<?php
ob_start();
$page_title = 'Редактирование новости';
include '../includes/header.php';
include '../config.php';

// Определяем директорию для загрузки
$upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/news/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
    chmod($upload_dir, 0777);
}

// Проверка прав доступа
if (!hasAccess('coach', $_SESSION['user_role'])) {
    header('Location: /404.php');
    exit();
}

$post_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Получение данных поста
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

// Обработка формы редактирования
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $title = $conn->real_escape_string($_POST['title']);
        $content = $conn->real_escape_string($_POST['content']);
        
        $conn->begin_transaction();
        
        // Обновление основных данных
        $sql = "UPDATE news SET title = ?, content = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ssi', $title, $content, $post_id);
        $stmt->execute();
        
        // Обработка нового превью изображения
        if (isset($_FILES['preview_image']) && $_FILES['preview_image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/news/';
            $preview_image = processImage($_FILES['preview_image'], $upload_dir, $post_id . '_preview');
            
            if ($preview_image) {
                // Удаляем старое изображение
                if ($post['image_path_preview']) {
                    @unlink($_SERVER['DOCUMENT_ROOT'] . $post['image_path_preview']);
                }
                
                // Обновляем путь к новому изображению
                $sql = "UPDATE news SET image_path_preview = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('si', $preview_image, $post_id);
                $stmt->execute();
            }
        }
        
        // Обработка новых дополнительных изображений
        if (isset($_FILES['extra_images']) && $_FILES['extra_images']['error'][0] !== UPLOAD_ERR_NO_FILE) {
            $extra_images = [];
            
            // Получаем существующие дополнительные изображения
            if ($post['image_path_extra']) {
                $extra_images = json_decode($post['image_path_extra'], true) ?? [];
            }
            
            // Добавляем новые изображения
            foreach ($_FILES['extra_images']['tmp_name'] as $key => $tmp_name) {
                if ($_FILES['extra_images']['error'][$key] === UPLOAD_ERR_OK) {
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
            
            // Обновляем дополнительные изображения
            $extra_images_json = json_encode($extra_images);
            $sql = "UPDATE news SET image_path_extra = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('si', $extra_images_json, $post_id);
            $stmt->execute();
        }
        
        $conn->commit();
        ob_end_clean();
        header("Location: /pages/post.php?id=$post_id");
        exit();
        
    } catch (Exception $e) {
        $conn->rollback();
        $error = "Ошибка при обновлении поста: " . $e->getMessage();
    }
}

// Функция для обработки изображений (такая же, как в create-post.php)
function processImage($file, $upload_dir, $prefix) {
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    if (!in_array($file['type'], $allowed_types)) {
        return false;
    }
    
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = $prefix . '_' . time() . '.' . $extension;
    $filepath = $upload_dir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return '/uploads/news/' . $filename;
    }
    
    return false;
}
?>

<main>
    <div class="create-post-container">
        <h1>Редактирование новости</h1>
        
        <?php if (isset($error)): ?>
            <div class="error-message"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data" class="create-post-form">
            <div class="form-group">
                <label for="title">Заголовок:</label>
                <input type="text" id="title" name="title" required 
                       value="<?= htmlspecialchars($post['title']) ?>">
            </div>
            
            <div class="form-group">
                <label for="content">Содержание:</label>
                <textarea id="content" name="content" required><?= htmlspecialchars($post['content']) ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="preview_image">Новое превью изображение (необязательно):</label>
                <input type="file" id="preview_image" name="preview_image" accept="image/*">
                <?php if ($post['image_path_preview']): ?>
                    <div class="current-image">
                        <p>Текущее изображение:</p>
                        <img src="<?= htmlspecialchars($post['image_path_preview']) ?>" alt="Текущее превью">
                    </div>
                <?php endif; ?>
                <div class="image-preview" id="preview-image-preview"></div>
            </div>
            
            <div class="form-group">
                <label for="extra_images">Добавить изображения:</label>
                <input type="file" id="extra_images" name="extra_images[]" accept="image/*" multiple>
                <?php if ($post['image_path_extra']): ?>
                    <div class="current-images">
                        <p>Текущие дополнительные изображения:</p>
                        <?php foreach (json_decode($post['image_path_extra'], true) as $image): ?>
                            <img src="<?= htmlspecialchars($image) ?>" alt="Дополнительное изображение">
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <div class="image-preview" id="extra-images-preview"></div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Сохранить изменения</button>
                <a href="/pages/post.php?id=<?= $post_id ?>" class="btn btn-secondary">Отмена</a>
            </div>
        </form>
    </div>
</main>

<script>
// Тот же JavaScript для предпросмотра изображений, что и в create-post.php
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