<?php
$page_title = 'Главная страница';
include 'includes/header.php';
include 'config.php';

// Получаем последние новости
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

// Получаем последние фотографии из галереи
$gallery_result = null;
try {
    $gallery_query = "SELECT g.*, 
                      (SELECT COUNT(*) FROM gallery_likes gl WHERE gl.photo_id = g.id) as likes_count
                      FROM gallery g 
                      ORDER BY g.created_at DESC 
                      LIMIT 6";
    $gallery_result = $conn->query($gallery_query);
} catch (Exception $e) {
    error_log("Error fetching gallery: " . $e->getMessage());
}

// Получаем ближайшие события
$events_result = null;
try {
    $current_date = date('Y-m-d H:i:s'); // Текущая дата и время
    $user_role = $_SESSION['user_role'] ?? 'guest';
    
    $events_query = "SELECT * FROM events 
                    WHERE DATE(start_date) >= DATE(NOW())
                    AND JSON_CONTAINS(event_visibility, '\"" . $user_role . "\"')
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

// Добавляем проверку структуры таблицы
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

// Функция для перевода типа события
function getEventTypeLabel($type) {
    $labels = [
        'public_event' => 'Публичное мероприятие',
        'team_training' => 'Тренировка',
        'match' => 'Матч',
        'meeting' => 'Собрание',
        'tournament' => 'Турнир'
    ];
    return $labels[$type] ?? $type;
}

// Функция для перевода названия месяца
function getMonthName($month) {
    $months = [
        'Jan' => 'Янв',
        'Feb' => 'Фев',
        'Mar' => 'Мар',
        'Apr' => 'Апр',
        'May' => 'Май',
        'Jun' => 'Июн',
        'Jul' => 'Июл',
        'Aug' => 'Авг',
        'Sep' => 'Сен',
        'Oct' => 'Окт',
        'Nov' => 'Ноя',
        'Dec' => 'Дек'
    ];
    return $months[$month] ?? $month;
}
?>

<main class="home-page">
    <!-- Приветственный баннер -->
    <section class="welcome-banner">
        <div class="banner-content">
            <h1>Добро пожаловать в NoNames Team!</h1>
            <p class="subtitle">Присоединяйтесь к нашей команде и станьте частью нашей истории</p>
            <?php if (!isset($_SESSION['user_id'])): ?>
                <div class="banner-actions">
                    <a href="/auth/register.php" class="btn btn-primary">Присоединиться</a>
                    <a href="/auth/login.php" class="btn btn-secondary">Войти</a>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Последние новости -->
    <section class="latest-news">
        <div class="section-header">
            <h2>Последние новости</h2>
            <a href="/pages/news.php" class="view-all">Все новости →</a>
        </div>
        <div class="news-grid">
            <?php if ($news_result && $news_result->num_rows > 0): ?>
                <?php while ($news = $news_result->fetch_assoc()): ?>
                    <article class="news-card">
                        <?php if ($news['image_path_preview']): ?>
                            <div class="news-image">
                                <img src="<?= htmlspecialchars($news['image_path_preview']) ?>" alt="Превью новости">
                            </div>
                        <?php endif; ?>
                        <div class="news-content">
                            <h3><?= htmlspecialchars($news['title']) ?></h3>
                            <div class="news-meta">
                                <span class="author">Автор: <?= htmlspecialchars($news['author_name']) ?></span>
                                <span class="date"><?= date('d.m.Y', strtotime($news['created_at'])) ?></span>
                            </div>
                            <a href="/pages/news/post.php?id=<?= $news['id'] ?>" class="read-more">Читать далее →</a>
                        </div>
                    </article>
                <?php endwhile; ?>
            <?php else: ?>
                <p class="no-content">Новостей пока нет</p>
            <?php endif; ?>
        </div>
    </section>

    <!-- Ближайшие события -->
    <section class="upcoming-events">
        <div class="section-header">
            <h2>Ближайшие события</h2>
            <a href="/pages/calendar.php" class="view-all">Календарь событий →</a>
        </div>
        <div class="events-list">
            <?php if ($events_result && $events_result->num_rows > 0): ?>
                <?php while ($event = $events_result->fetch_assoc()): ?>
                    <div class="event-card">
                        <div class="event-date">
                            <span class="day"><?= date('d', strtotime($event['start_date'])) ?></span>
                            <span class="month"><?= getMonthName(date('M', strtotime($event['start_date']))) ?></span>
                        </div>
                        <div class="event-details">
                            <div class="event-type">
                                <span class="material-icons">event</span>
                                <?= getEventTypeLabel($event['event_type']) ?>
                            </div>
                            <h3><?= htmlspecialchars($event['title']) ?></h3>
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
                                <p class="event-description"><?= htmlspecialchars($event['description']) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-content">
                    <i class="far fa-calendar-times"></i>
                    <p>Ближайших событий нет</p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Галерея -->
    <section class="gallery-preview">
        <div class="section-header">
            <h2>Фотогалерея</h2>
            <a href="/pages/gallery.php" class="view-all">Вся галерея →</a>
        </div>
        <div class="gallery-grid">
            <?php if ($gallery_result && $gallery_result->num_rows > 0): ?>
                <?php while ($photo = $gallery_result->fetch_assoc()): ?>
                    <div class="gallery-item">
                        <img src="<?= htmlspecialchars($photo['image_path']) ?>" alt="Фото из галереи">
                        <div class="photo-overlay">
                            <span class="likes-count">❤ <?= $photo['likes_count'] ?></span>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p class="no-content">В галерее пока нет фотографий</p>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php include 'includes/footer.php'; ?>
