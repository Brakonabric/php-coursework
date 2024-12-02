<?php
$page_title = 'Новости';
include '../includes/header.php';
include '../config.php';

// Пагинация
$posts_per_page = 5;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $posts_per_page;

// Получение общего количества постов
$total_posts_query = "SELECT COUNT(*) as count FROM news";
$total_result = $conn->query($total_posts_query);
$total_posts = $total_result->fetch_assoc()['count'];
$total_pages = ceil($total_posts / $posts_per_page);

// Получение постов для текущей страницы
$sql = "SELECT n.*, COUNT(c.id) as comments_count 
        FROM news n 
        LEFT JOIN news_comments c ON n.id = c.news_id 
        GROUP BY n.id 
        ORDER BY n.created_at DESC 
        LIMIT ? OFFSET ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param('ii', $posts_per_page, $offset);
$stmt->execute();
$result = $stmt->get_result();
?>

<main>
    <h1>Новости команды</h1>
    
    <?php if (hasAccess('coach', $_SESSION['user_role'])): ?>
        <div class="create-post-button">
            <a href="/pages/create-post.php" class="btn">Создать новость</a>
        </div>
    <?php endif; ?>
    
    <div class="news-grid">
        <?php while ($post = $result->fetch_assoc()): ?>
            <article class="news-card">
                <a href="/pages/post.php?id=<?= $post['id'] ?>">
                    <?php if ($post['image_path_preview']): ?>
                        <img src="<?= htmlspecialchars($post['image_path_preview']) ?>" alt="Превью новости">
                    <?php endif; ?>
                    <div class="news-content">
                        <h2><?= htmlspecialchars($post['title']) ?></h2>
                        <div class="news-meta">
                            <span class="date"><?= date('d.m.Y', strtotime($post['created_at'])) ?></span>
                            <span class="comments">Комментарии: <?= $post['comments_count'] ?></span>
                        </div>
                    </div>
                </a>
            </article>
        <?php endwhile; ?>
    </div>

    <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?page=<?= $page - 1 ?>" class="page-link">&laquo; Предыдущая</a>
            <?php endif; ?>
            
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?page=<?= $i ?>" class="page-link <?= $i === $page ? 'active' : '' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
            
            <?php if ($page < $total_pages): ?>
                <a href="?page=<?= $page + 1 ?>" class="page-link">Следующая &raquo;</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</main>

<?php include '../includes/footer.php'; ?>
