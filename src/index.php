<?php
$page_title = 'Sākumlapa';
include 'includes/header.php';
include 'config.php';

$news_result = null;
try {
    $news_query = "SELECT n.*, u.name as author_name 
                   FROM news n 
                   LEFT JOIN users u ON n.user_id = u.id 
                   ORDER BY n.created_at DESC 
                   LIMIT 3";
    $news_result = $conn->query($news_query);
} catch (Exception $e) {
    error_log("Error fetching news: " . $e->getMessage());
}

$gallery_result = null;
try {
    $gallery_query = "SELECT g.*, 
                      (SELECT COUNT(*) FROM gallery_likes gl WHERE gl.photo_id = g.id) as likes_count,
                      (SELECT COUNT(*) FROM gallery_comments gc WHERE gc.photo_id = g.id) as comments_count
                      FROM gallery g 
                      ORDER BY g.created_at DESC 
                      LIMIT 6";
    $gallery_result = $conn->query($gallery_query);
} catch (Exception $e) {
    error_log("Error fetching gallery: " . $e->getMessage());
}

$events_result = null;
try {
    $current_date = date('Y-m-d H:i:s');
    $userRole = $_SESSION['userRole'] ?? 'guest';
    
    $events_query = "SELECT * FROM events 
                    WHERE DATE(start_date) >= DATE(NOW())
                    AND JSON_CONTAINS(event_visibility, '\"" . $userRole . "\"')
                    ORDER BY start_date ASC 
                    LIMIT 3";
    
    error_log("Events query: " . $events_query);
    $events_result = $conn->query($events_query);
    
    if ($events_result) {
        error_log("Number of events found: " . $events_result->num_rows);
    } else {
        error_log("Query failed: " . $conn->error);
    }
} catch (Exception $e) {
    error_log("Error fetching events: " . $e->getMessage());
}

try {
    $structure_query = "DESCRIBE events";
    $structure_result = $conn->query($structure_query);
    if ($structure_result) {
        error_log("Table structure:");
        while ($field = $structure_result->fetch_assoc()) {
            error_log(print_r($field, true));
        }
    }
} catch (Exception $e) {
    error_log("Error getting table structure: " . $e->getMessage());
}

function getEventTypeLabel($type) {
    global $conn;
    $stmt = $conn->prepare("SELECT label FROM event_types WHERE id = ?");
    $stmt->bind_param("s", $type);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        return $row['label'];
    }
    return $type;
}

?>

<main class="home-page">
    <section class="welcome-banner section-a">
        <div class="banner-content">
            <h1>Laipni lūdzam NoNames!</h1>
            <p class="subtitle">Pievienojieties mūsu komandai un kļūstiet par daļu no mūsu vēstures!</p>
            <?php if (!isset($_SESSION['userId'])): ?>
                <div class="banner-actions">
                    <a href="/auth/register.php">Reģistrēties</a>
                    <a href="/auth/login.php">Ieiet</a>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <section class="latest-news section-b">
        <div class="section-header">
            <h2>Jaunākās ziņas</h2>
            <a href="/pages/news.php" class="view-all">Visas ziņas</a>
        </div>
        <div class="news-grid">
            <?php if ($news_result && $news_result->num_rows > 0): ?>
                <?php while ($news = $news_result->fetch_assoc()): ?>
                    <a href="/pages/news/post.php?id=<?= $news['id'] ?>" class="news-card">
                        <?php if ($news['image_path_preview']): ?>
                            <div class="news-image">
                                <img src="<?= htmlspecialchars($news['image_path_preview']) ?>" alt="Ziņu priekšskatjums">
                            </div>
                        <?php endif; ?>
                        <div class="news-content">
                            <h3><?= htmlspecialchars($news['title']) ?></h3>
                            <div class="news-meta">
                                <span class="author">Autors: <?= htmlspecialchars($news['author_name']) ?></span>
                                <span class="date"><?= date('d.m.Y', strtotime($news['created_at'])) ?></span>
                            </div>
                        </div>
                    </a>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-content">
                    <span class="material-icons">article</span>
                    <p>Pagaidām nav ziņu</p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <section class="upcoming-events section-a">
        <div class="section-header">
            <h2>Gaidāmie notikumi</h2>
            <a href="/pages/calendar.php" class="view-all">Notikumu kalendārs</a>
        </div>
        <div class="events-list">
            <?php if ($events_result && $events_result->num_rows > 0): ?>
                <?php while ($event = $events_result->fetch_assoc()): ?>
                    <a href="/pages/calendar.php" class="event-card">
                        <div class="event-header">
                            <div class="event-type">
                                <span class="material-icons">event</span>
                                <span><?= getEventTypeLabel($event['event_type']) ?></span>
                            </div>
                            <div class="event-date">
                                <span class="day"><?= date('d', strtotime($event['start_date'])) ?></span>
                                <span class="month"><?= date('M', strtotime($event['start_date'])) ?></span>
                            </div>
                        </div>
                        <h3 class="event-title"><?= htmlspecialchars($event['title']) ?></h3>
                        <div class="event-details">
                            <?php if ($event['location']): ?>
                                <p class="event-location">
                                    <span class="material-icons">place</span>
                                    <?= htmlspecialchars($event['location']) ?>
                                </p>
                            <?php endif; ?>
                            <p class="event-time">
                                <span class="material-icons">schedule</span>
                                <?= date('H:i', strtotime($event['start_date'])) ?>
                            </p>
                            <?php if ($event['description']): ?>
                                <p class="event-description">
                                    <span class="material-icons">description</span>
                                    <?= htmlspecialchars($event['description']) ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    </a>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-content">
                    <span class="material-icons">event_busy</span>
                    <p>Nav gaidāmo notikumu</p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <section class="gallery-preview section-b">
        <div class="section-header">
            <h2>Fotogalerija</h2>
            <a href="/pages/gallery.php" class="view-all">Visa galerija</a>
        </div>
        <div class="gallery-grid">
            <?php if ($gallery_result && $gallery_result->num_rows > 0): ?>
                <?php while ($photo = $gallery_result->fetch_assoc()): ?>
                    <div class="gallery-item" data-id="<?= $photo['id'] ?>">
                        <img src="<?= htmlspecialchars($photo['image_path']) ?>" alt="Foto no galerijas" onclick="openHomeImage(this.parentElement)">
                        <div class="photo-overlay">
                            <span class="likes-count"><span class="material-icons">favorite</span> <?= $photo['likes_count'] ?></span>
                            <span class="comments-count"><span class="material-icons">comment</span> <?= $photo['comments_count'] ?></span>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-content">
                    <span class="material-icons">photo_library</span>
                    <p>Galerijā pagaidām nav fotogrāfiju</p>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php include 'includes/footer.php'; ?>
