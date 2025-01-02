<?php
$page_title = 'Ziņas';
include '../includes/header.php';
include '../config.php';

$posts_per_page = 5;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $posts_per_page;

$total_posts_query = "SELECT COUNT(*) as count FROM news";
$total_result = $conn->query($total_posts_query);
$total_posts = $total_result->fetch_assoc()['count'];
$total_pages = ceil($total_posts / $posts_per_page);

$sort = isset($_GET['sort']) ? $_GET['sort'] : 'date_desc';

$order_by = match($sort) {
    'date_asc' => 'n.created_at ASC',
    'comments' => 'comments_count DESC',
    default => 'n.created_at DESC'
};

$sql = "SELECT n.*, COUNT(c.id) as comments_count 
        FROM news n 
        LEFT JOIN news_comments c ON n.id = c.news_id 
        GROUP BY n.id 
        ORDER BY {$order_by} 
        LIMIT ? OFFSET ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param('ii', $posts_per_page, $offset);
$stmt->execute();
$result = $stmt->get_result();
?>

<main>
    <div class="news-container">
        <h1>Komandas ziņas</h1>
        <div class="news-toolbar">
            <div class="news-filters">
                <select onchange="window.location.href='?sort=' + this.value">
                    <option value="date_desc" <?= $sort === 'date_desc' ? 'selected' : '' ?>>Sākumā jaunākās</option>
                    <option value="date_asc" <?= $sort === 'date_asc' ? 'selected' : '' ?>>Sākumā vecākās</option>
                    <option value="comments" <?= $sort === 'comments' ? 'selected' : '' ?>>Pēc komentāriem</option>
                </select>
            </div>
            <?php if (hasAccess('coach', $_SESSION['userRole'])): ?>
                <div class="create-post-button">
                    <a href="/pages/news/post/create.php" class="link-btn btn-primary">
                        <span class="material-icons">add</span>
                        Izveidot ziņu
                    </a>
                </div>
            <?php endif; ?>
        </div>
        
        <?php if ($result->num_rows > 0): ?>
            <div class="news-grid">
                <?php while ($post = $result->fetch_assoc()): ?>
                    <article class="news-card">
                        <a href="/pages/news/post.php?id=<?= $post['id'] ?>">
                            <?php if ($post['image_path_preview']): ?>
                                <img src="<?= htmlspecialchars($post['image_path_preview']) ?>" alt="Ziņu priekšskatījums">
                            <?php endif; ?>
                            <div class="news-content">
                                <h2><?= htmlspecialchars($post['title']) ?></h2>
                                <div class="news-meta">
                                    <span class="date">
                                        <span class="material-icons">calendar_today</span>
                                        <?= date('d.m.Y', strtotime($post['created_at'])) ?>
                                    </span>
                                    <span class="comments">
                                        <span class="material-icons">comment</span>
                                        <?= $post['comments_count'] ?>
                                    </span>
                                </div>
                            </div>
                        </a>
                    </article>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="no-content">
                <span class="material-icons">article</span>
                <p>Pagaidām nav ziņu</p>
            </div>
        <?php endif; ?>

        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?= $page - 1 ?>&sort=<?= $sort ?>" class="page-link">
                        <span class="material-icons">chevron_left</span>
                        Iepriekšējā
                    </a>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?= $i ?>&sort=<?= $sort ?>" 
                       class="page-link <?= $i === $page ? 'active' : '' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?= $page + 1 ?>&sort=<?= $sort ?>" class="page-link">
                        Nākamā
                        <span class="material-icons">chevron_right</span>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php include '../includes/footer.php'; ?>
