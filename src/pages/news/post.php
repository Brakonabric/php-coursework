<?php
ob_start();
$page_title = 'Skatīt ziņu';
include '../../includes/header.php';
include '../../config.php';

// Проверка наличия ID новости
if (!isset($_GET['id'])) {
    header('Location: /404.php');
    exit();
}

$post_id = (int)$_GET['id'];

// Получение информации о новости
$sql = "SELECT n.*, u.name as author_name 
        FROM news n 
        LEFT JOIN users u ON n.user_id = u.id 
        WHERE n.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $post_id);
$stmt->execute();
$result = $stmt->get_result();
$post = $result->fetch_assoc();

if (!$post) {
    header('Location: /404.php');
    exit();
}

// Обработка добавления комментария
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment'])) {
    if (!isset($_SESSION['user_id'])) {
        $error = "Необходимо войти в систему для добавления комментария";
    } else {
        $comment = trim($_POST['comment']);
        if (!empty($comment)) {
            $sql = "INSERT INTO news_comments (news_id, user_id, comment) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('iis', $post_id, $_SESSION['user_id'], $comment);
            
            if ($stmt->execute()) {
                // Перенаправляем на ту же страницу для обновления списка комментариев
                header("Location: /pages/news/post.php?id=$post_id");
                exit();
            } else {
                $error = "Ошибка при добавлении комментария";
            }
        } else {
            $error = "Комментарий не может быть пустым";
        }
    }
}

// Обработка удаления поста
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_post'])) {
    if (hasAccess('coach', $_SESSION['user_role'])) {
        try {
            // Начинаем транзакцию
            $conn->begin_transaction();
            
            // Удаляем комментарии к новости
            $sql = "DELETE FROM news_comments WHERE news_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('i', $post_id);
            $stmt->execute();
            
            // Удаляем саму новость
            $sql = "DELETE FROM news WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('i', $post_id);
            $stmt->execute();
            
            // Если вс�� успешно, подтверждаем транзакцию
            $conn->commit();
            
            // Удаляем изображения
            if ($post['image_path_preview']) {
                $preview_path = $_SERVER['DOCUMENT_ROOT'] . $post['image_path_preview'];
                if (file_exists($preview_path)) {
                    unlink($preview_path);
                }
            }
            
            if ($post['image_path_extra']) {
                $extra_images = json_decode($post['image_path_extra'], true);
                if (is_array($extra_images)) {
                    foreach ($extra_images as $image_path) {
                        $full_path = $_SERVER['DOCUMENT_ROOT'] . $image_path;
                        if (file_exists($full_path)) {
                            unlink($full_path);
                        }
                    }
                }
            }
            
            header('Location: /pages/news.php');
            exit();
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Ошибка при удалении новости: " . $e->getMessage();
        }
    } else {
        $error = "У вас нет прав для удаления новостей";
    }
}

// Получение комментариев к н��вости
$sql = "SELECT c.*, u.name as user_name 
        FROM news_comments c 
        LEFT JOIN users u ON c.user_id = u.id 
        WHERE c.news_id = ? 
        ORDER BY c.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $post_id);
$stmt->execute();
$comments_result = $stmt->get_result();
?>

<main>
    <div class="post-container">
        <?php if (isset($error)): ?>
            <div class="error-message"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <article class="post">
            <h1><?= htmlspecialchars($post['title']) ?></h1>
            
            <div class="post-meta">
                <span class="author">Autors: <?= htmlspecialchars($post['author_name']) ?></span>
                <span class="date">Datums: <?= date('d.m.Y H:i', strtotime($post['created_at'])) ?></span>
            </div>
            
            <?php if ($post['image_path_preview']): ?>
                <div class="post-image">
                    <img src="<?= htmlspecialchars($post['image_path_preview']) ?>" alt="Ziņas attēls">
                </div>
            <?php endif; ?>
            
            <div class="post-content">
                <?= nl2br(htmlspecialchars($post['content'])) ?>
            </div>
            
            <?php if ($post['image_path_extra']): ?>
                <div class="post-gallery">
                    <?php 
                    $extra_images = json_decode($post['image_path_extra'], true);
                    if (is_array($extra_images)):
                        foreach ($extra_images as $image_path):
                    ?>
                        <div class="gallery-item">
                            <img src="<?= htmlspecialchars($image_path) ?>" alt="Papildu attēls">
                        </div>
                    <?php 
                        endforeach;
                    endif;
                    ?>
                </div>
            <?php endif; ?>
            
            <?php if (hasAccess('coach', $_SESSION['user_role'])): ?>
                <div class="post-actions">
                    <a href="/pages/news/post/edit.php?id=<?= $post_id ?>" class="btn btn-edit">
                        <span class="material-icons">edit</span>
                        Rediģēt
                    </a>
                    <form method="POST" class="delete-form" onsubmit="return confirm('Vai tiešām vēlaties dzēst šo ziņu?');">
                        <button type="submit" name="delete_post" class="btn btn-delete">
                            <span class="material-icons">delete</span>
                            Dzēst
                        </button>
                    </form>
                </div>
            <?php endif; ?>
        </article>
        
        <section class="comments-section">
            <h2>Komentāri</h2>
            
            <?php if (isset($_SESSION['user_id'])): ?>
                <form method="POST" class="comment-form">
                    <div class="form-group">
                        <label for="comment">Jūsu komentārs:</label>
                        <textarea id="comment" name="comment" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <span class="material-icons">send</span>
                        Nosūtīt
                    </button>
                </form>
            <?php else: ?>
                <div class="auth-prompt">
                    <span class="material-icons">info</span>
                    <p>Lai pievienotu komentāru, lūdzu, <a href="/auth/login.php">piesakieties</a> vai <a href="/auth/register.php">reģistrējieties</a>.</p>
                </div>
            <?php endif; ?>
            
            <div class="comments-list">
                <?php if ($comments_result->num_rows > 0): ?>
                    <?php while ($comment = $comments_result->fetch_assoc()): ?>
                        <div class="comment">
                            <div class="comment-meta">
                                <div class="comment-author">
                                    <span class="material-icons">account_circle</span>
                                    <?= htmlspecialchars($comment['user_name']) ?>
                                </div>
                                <div class="comment-date">
                                    <span class="material-icons">schedule</span>
                                    <?= date('d.m.Y H:i', strtotime($comment['created_at'])) ?>
                                </div>
                            </div>
                            <div class="comment-content">
                                <?= nl2br(htmlspecialchars($comment['comment'])) ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-comments">
                        <span class="material-icons">chat_bubble_outline</span>
                        <p>Pagaidām nav neviena komentāra. Esiet pirmais, kas komentē!</p>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </div>
</main>

<?php include '../../includes/footer.php'; ?> 