<?php
ob_start();
$page_title = 'Skatīt ziņu';
include '../../includes/header.php';
include '../../config.php';

if (!isset($_GET['id'])) {
    header('Location: /404.php');
    exit();
}

$post_id = (int)$_GET['id'];

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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment'])) {
    if (!isset($_SESSION['userId'])) {
        $error = "Lai pievienotu komentāru, jums jāpiesakās sistēmā";
    } else {
        $check_sql = "SELECT can_comment FROM users WHERE id = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param('i', $_SESSION['userId']);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        $user_data = $check_result->fetch_assoc();
        
        if (!$user_data['can_comment']) {
            $error = "Jums nav atļauts pievienot komentārus";
        } else {
            $comment = trim($_POST['comment']);
            if (!empty($comment)) {
                $sql = "INSERT INTO news_comments (news_id, user_id, comment) VALUES (?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('iis', $post_id, $_SESSION['userId'], $comment);
                
                if ($stmt->execute()) {
                    header("Location: /pages/news/post.php?id=$post_id");
                    exit();
                } else {
                    $error = "Kļūda, pievienojot komentāru";
                }
            } else {
                $error = "Komentārs nevar būt tukšs";
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete']) && hasAccess('coach', $_SESSION['userRole'])) {
    try {
        $conn->begin_transaction();

        if ($post['image_path_preview']) {
            @unlink($_SERVER['DOCUMENT_ROOT'] . $post['image_path_preview']);

            $sql = "DELETE FROM gallery WHERE image_path = ? AND source_type = 'news' AND source_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('si', $post['image_path_preview'], $post_id);
            $stmt->execute();
        }

        if ($post['image_path_extra']) {
            $extraImages = json_decode($post['image_path_extra'], true) ?? [];
            foreach ($extraImages as $image) {
                @unlink($_SERVER['DOCUMENT_ROOT'] . $image);

                $sql = "DELETE FROM gallery WHERE image_path = ? AND source_type = 'news' AND source_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('si', $image, $post_id);
                $stmt->execute();
            }
        }

        $sql = "DELETE FROM gallery WHERE source_type = 'news' AND source_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $post_id);
        $stmt->execute();

        $sql = "DELETE FROM news_comments WHERE news_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $post_id);
        $stmt->execute();

        $sql = "DELETE FROM news WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $post_id);
        $stmt->execute();
        
        $conn->commit();
        header('Location: /pages/news.php');
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        $error = "Kļūda, dzēšot ziņu: " . $e->getMessage();
    }
}

$sql = "SELECT c.*, u.name as user_name 
        FROM news_comments c 
        LEFT JOIN users u ON c.user_id = u.id 
        WHERE c.news_id = ? 
        ORDER BY c.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $post_id);
$stmt->execute();
$comments_result = $stmt->get_result();

$can_comment = false;
if (isset($_SESSION['userId'])) {
    $check_sql = "SELECT can_comment FROM users WHERE id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param('i', $_SESSION['userId']);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    $user_data = $check_result->fetch_assoc();
    $can_comment = $user_data['can_comment'];
}
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
                <?= nl2br(stripslashes($post['content'])) ?>
            </div>
            
            <?php if ($post['image_path_extra']): ?>
                <div class="post-gallery">
                    <?php 
                    $extra_images = json_decode($post['image_path_extra'], true);
                    if (is_array($extra_images)):
                        foreach ($extra_images as $image_path):
                    ?>
                        <div class="gallery-item">
                            <img src="<?= htmlspecialchars($image_path) ?>" alt="Papildu attēls" onclick="openNewsImage(this.parentElement)">
                        </div>
                    <?php 
                        endforeach;
                    endif;
                    ?>
                </div>
            <?php endif; ?>
            
            <?php if (hasAccess('coach', $_SESSION['userRole'])): ?>
                <div class="post-actions">
                    <a href="/pages/news/post/edit.php?id=<?= $post_id ?>" class="link-btn btn-primary">
                        <span class="material-icons">edit</span>
                        Rediģēt
                    </a>
                    <form method="POST" class="delete-form" onsubmit="return confirm('Vai tiešām vēlaties dzēst šo ziņu?');">
                        <button type="submit" name="delete" class="btn btn-danger">
                            <span class="material-icons">delete</span>
                            Dzēst
                        </button>
                    </form>
                </div>
            <?php endif; ?>
        </article>
        
        <section class="comments-section">
            <h2>Komentāri</h2>
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
                                <?= nl2br(stripslashes($comment['comment'])) ?>
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
            <?php if (isset($_SESSION['userId'])): ?>
                <?php if ($can_comment): ?>
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
                        <p>Jums nav atļauts pievienot komentārus.</p>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="auth-prompt">
                    <span class="material-icons">info</span>
                    <p>Lai pievienotu komentāru, lūdzu, <a href="/auth/login.php">piesakieties</a> vai <a href="/auth/register.php">reģistrējieties</a>.</p>
                </div>
            <?php endif; ?>
        </section>
    </div>
</main>

<?php include '../../includes/footer.php'; ?> 