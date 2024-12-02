<?php
// Начинаем буферизацию вывода
ob_start();

$page_title = 'Новость';
include '../includes/header.php';
include '../config.php';

$post_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Получение данных новости (перемещаем получение данных перед обработкой удаления)
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

// Обработка удаления поста
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_post']) && hasAccess('coach', $_SESSION['user_role'])) {
    try {
        $conn->begin_transaction();
        
        // Удаляем файлы изображений
        if ($post['image_path_preview']) {
            $preview_path = $_SERVER['DOCUMENT_ROOT'] . $post['image_path_preview'];
            if (file_exists($preview_path)) {
                unlink($preview_path);
            }
        }
        
        if ($post['image_path_extra']) {
            $extra_images = json_decode($post['image_path_extra'], true);
            if (is_array($extra_images)) {
                foreach ($extra_images as $image) {
                    $image_path = $_SERVER['DOCUMENT_ROOT'] . $image;
                    if (file_exists($image_path)) {
                        unlink($image_path);
                    }
                }
            }
        }
        
        // Удаляем комментарии
        $sql = "DELETE FROM news_comments WHERE news_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $post_id);
        $stmt->execute();
        
        // Удаляем сам пост
        $sql = "DELETE FROM news WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $post_id);
        $stmt->execute();
        
        $conn->commit();
        
        ob_end_clean();
        header('Location: /pages/news.php');
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        $error = "Ошибка при удалении поста: " . $e->getMessage();
    }
}

// Получение комментариев
$sql = "SELECT c.*, u.name as user_name 
        FROM news_comments c 
        LEFT JOIN users u ON c.user_id = u.id 
        WHERE c.news_id = ? 
        ORDER BY c.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $post_id);
$stmt->execute();
$comments = $stmt->get_result();

// Добавление комментария
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_comment'])) {
    if (!isset($_SESSION['user_id'])) {
        $error = "Необходимо войти в систему для добавления комментария";
    } else {
        // Очищаем текст комментария от лишних пробелов и переносов строк
        $content = trim(str_replace(["\r\n", "\r"], "\n", $_POST['content']));
        $user_id = $_SESSION['user_id'];
        
        $sql = "INSERT INTO news_comments (news_id, user_id, comment) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('iis', $post_id, $user_id, $content);
        
        if ($stmt->execute()) {
            ob_end_clean();
            header("Location: post.php?id=$post_id");
            exit();
        } else {
            $error = "Ошибка при добавлении комментария";
        }
    }
}
?>

<main>
    <article class="post">
        <?php if (hasAccess('coach', $_SESSION['user_role'])): ?>
            <div class="post-actions">
                <a href="/pages/edit-post.php?id=<?= $post_id ?>" class="btn btn-edit">Редактировать</a>
                <form method="POST" class="delete-form" onsubmit="return confirm('Вы уверены, что хотите удалить этот пост?');">
                    <button type="submit" name="delete_post" class="btn btn-delete">Удалить</button>
                </form>
            </div>
        <?php endif; ?>

        <h1><?= htmlspecialchars($post['title']) ?></h1>
        <div class="post-meta">
            <span class="date">Опубликовано: <?= date('d.m.Y H:i', strtotime($post['created_at'])) ?></span>
        </div>

        <?php if ($post['image_path_preview']): ?>
            <img src="<?= htmlspecialchars($post['image_path_preview']) ?>" alt="Превью изображение" class="main-image">
        <?php endif; ?>

        <div class="post-content">
            <?= nl2br(htmlspecialchars($post['content'])) ?>
        </div>

        <?php
        if ($post['image_path_extra']) {
            $extra_images = json_decode($post['image_path_extra'], true);
            if (!empty($extra_images)): ?>
                <div class="additional-images">
                    <?php foreach ($extra_images as $image): ?>
                        <img src="<?= htmlspecialchars($image) ?>" alt="Дополнительное изображение">
                    <?php endforeach; ?>
                </div>
            <?php endif;
        }
        ?>

        <section class="comments">
            <h2>Комментарии</h2>
            
            <?php if (isset($_SESSION['user_id'])): ?>
                <form class="comment-form" method="POST">
                    <textarea name="content" required placeholder="Ваш комментарий"></textarea>
                    <button type="submit" name="add_comment">Отправить</button>
                </form>
                <?php if (isset($error)): ?>
                    <p class="error"><?= $error ?></p>
                <?php endif; ?>
            <?php else: ?>
                <p>Чтобы оставить комментарий, пожалуйста, <a href="/auth/login.php">войдите</a> в систему.</p>
            <?php endif; ?>

            <div class="comments-list">
                <?php while ($comment = $comments->fetch_assoc()): ?>
                    <div class="comment">
                        <div class="comment-header">
                            <span class="user-name"><?= htmlspecialchars($comment['user_name']) ?></span>
                            <span class="comment-date"><?= date('d.m.Y H:i', strtotime($comment['created_at'])) ?></span>
                        </div>
                        <div class="comment-content">
                            <?= nl2br(htmlspecialchars($comment['comment'])) ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </section>
    </article>
</main>

<style>
.post-actions {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
}

.btn-edit {
    background-color: #4CAF50;
}

.btn-edit:hover {
    background-color: #45a049;
}

.btn-delete {
    background-color: #f44336;
    border: none;
    cursor: pointer;
}

.btn-delete:hover {
    background-color: #da190b;
}

.delete-form {
    display: inline;
}
</style>

<?php include '../includes/footer.php'; ?> 