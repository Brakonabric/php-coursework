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
                                <i class="fas fa-calendar-alt"></i>
                                <?= getEventTypeLabel($event['event_type']) ?>
                            </div>
                            <h3><?= htmlspecialchars($event['title']) ?></h3>
                            <?php if ($event['location']): ?>
                                <p class="event-location">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <?= htmlspecialchars($event['location']) ?>
                                </p>
                            <?php endif; ?>
                            <p class="event-time">
                                <i class="far fa-clock"></i>
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

<style>
/* Общие стили */
.home-page {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.view-all {
    color: #666;
    text-decoration: none;
    font-size: 0.9em;
}

.view-all:hover {
    color: #333;
}

/* Баннер */
.welcome-banner {
    background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
    color: white;
    padding: 60px 20px;
    border-radius: 10px;
    margin-bottom: 40px;
    text-align: center;
}

.banner-content h1 {
    font-size: 2.5em;
    margin-bottom: 15px;
}

.subtitle {
    font-size: 1.2em;
    margin-bottom: 30px;
    opacity: 0.9;
}

.banner-actions {
    display: flex;
    gap: 15px;
    justify-content: center;
}

/* Новости */
.news-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 40px;
}

.news-card {
    border: 1px solid #eee;
    border-radius: 8px;
    overflow: hidden;
    transition: transform 0.2s;
    background: white;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.news-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.news-image img {
    width: 100%;
    height: 200px;
    object-fit: cover;
}

.news-content {
    padding: 20px;
}

.news-content h3 {
    margin: 0 0 10px 0;
    font-size: 1.2em;
    color: #333;
}

.news-meta {
    font-size: 0.9em;
    color: #666;
    margin: 10px 0;
    display: flex;
    justify-content: space-between;
}

.read-more {
    display: inline-block;
    color: #1e3c72;
    text-decoration: none;
    font-weight: 500;
    margin-top: 10px;
}

.read-more:hover {
    color: #2a5298;
}

/* События */
.upcoming-events {
    margin-bottom: 40px;
}

.events-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.event-card {
    display: flex;
    align-items: flex-start;
    padding: 20px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: transform 0.2s, box-shadow 0.2s;
    border: 1px solid #eee;
}

.event-card:hover {
    transform: translateX(5px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.event-date {
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    background: #1e3c72;
    color: white;
    padding: 10px;
    border-radius: 8px;
    min-width: 60px;
    height: 70px;
    margin-right: 20px;
    box-shadow: 0 2px 4px rgba(30,60,114,0.2);
}

.event-date .day {
    font-size: 24px;
    font-weight: bold;
    line-height: 1;
    margin-bottom: 4px;
}

.event-date .month {
    font-size: 14px;
    text-transform: uppercase;
    line-height: 1;
}

.event-details {
    flex: 1;
}

.event-type {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
    color: #1e3c72;
    background: rgba(30,60,114,0.1);
    padding: 4px 8px;
    border-radius: 4px;
    margin-bottom: 10px;
}

.event-type i {
    font-size: 14px;
}

.event-details h3 {
    margin: 0 0 12px 0;
    color: #333;
    font-size: 18px;
    line-height: 1.3;
}

.event-location, .event-time {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
    color: #666;
    margin: 8px 0;
}

.event-location i, .event-time i {
    color: #1e3c72;
    font-size: 16px;
    width: 16px;
    text-align: center;
}

.event-description {
    font-size: 14px;
    color: #666;
    margin-top: 12px;
    padding-top: 12px;
    border-top: 1px solid #eee;
    line-height: 1.5;
}

.no-content {
    text-align: center;
    padding: 30px;
    background: #f8f9fa;
    border-radius: 12px;
    color: #666;
}

.no-content i {
    font-size: 32px;
    margin-bottom: 15px;
    color: #1e3c72;
    opacity: 0.5;
}

/* Галерея */
.gallery-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 40px;
}

.gallery-item {
    position: relative;
    aspect-ratio: 1;
    overflow: hidden;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.gallery-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s;
}

.gallery-item:hover img {
    transform: scale(1.1);
}

.photo-overlay {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 10px;
    background: linear-gradient(transparent, rgba(0,0,0,0.7));
    color: white;
}

.likes-count {
    font-size: 0.9em;
}

/* Адаптивность */
@media (max-width: 768px) {
    .welcome-banner {
        padding: 40px 20px;
    }

    .banner-content h1 {
        font-size: 2em;
    }

    .news-grid, .gallery-grid {
        grid-template-columns: 1fr;
    }

    .event-card {
        flex-direction: column;
    }
    
    .event-date {
        margin-right: 0;
        margin-bottom: 15px;
        align-self: flex-start;
    }
}

/* Добавляем Font Awesome для иконок */
@import url('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css');
</style>

<?php include 'includes/footer.php'; ?>
