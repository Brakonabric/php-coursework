<?php
$page_title = 'Календарь событий';
include '../includes/header.php';
include '../config.php';
require_once '../functions/calendar_functions.php';

// Получение роли пользователя
$userRole = $_SESSION['user_role'] ?? 'guest';

// Определение текущего месяца и года
$currentMonth = isset($_GET['month']) ? (int)$_GET['month'] : date('m');
$currentYear = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

// Получение цветов для типов событий
$sql = "SELECT id, color FROM event_types";
$result = $conn->query($sql);
$eventColors = [];
while ($row = $result->fetch_assoc()) {
    $eventColors[$row['id']] = $row['color'];
}
?>

<style>
.event {
    position: relative;
    overflow: hidden;
}

.event::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    opacity: 0.3;
    z-index: 1;
}

<?php foreach ($eventColors as $type => $color): ?>
.event-<?= $type ?>::before {
    background-color: <?= $color ?>;
}
<?php endforeach; ?>

.event:hover::before {
    opacity: 0.5;
}

.calendar-grid td {
    position: relative;
    height: 40px;
}

.calendar-grid .current {
    background-color: #fff;
}

.calendar-grid .other {
    background-color: #f5f5f5;
}

.calendar-grid td span {
    position: relative;
    z-index: 2;
}
</style>

<!-- Остальной код календаря -->
<?php
// Получение событий
$startDate = date('Y-m-d', strtotime("-3 months", strtotime("$currentYear-$currentMonth-01")));
$endDate = date('Y-m-d', strtotime("+3 months", strtotime("$currentYear-$currentMonth-01")));
$events = getEvents($conn, $startDate, $endDate, $userRole);

// Генерация данных для отображения календаря
$months = getMonthsData($currentYear, $currentMonth, $events);
?>

<main>
    <div class="calendar-grid">
        <div class="navigation large">
            <div class="control">
                <a href="?month=<?= $currentMonth - 1 ?>&year=<?= $currentYear ?>" class="nav-btn">&#9664; Предыдущий</a>
                <span><?= date('F Y', strtotime("$currentYear-$currentMonth-01")) ?></span>
                <a href="?month=<?= $currentMonth + 1 ?>&year=<?= $currentYear ?>" class="nav-btn">Следующий &#9654;</a>
            </div>
            <div class="add-event-btn-box">
                <?php if (hasAccess('coach', $userRole)): ?>
                    <a href="/pages/calendar/event/create.php" class="btn btn-primary">Создать событие</a>
                <?php endif; ?>
            </div>
        </div>

        <?php foreach ($months as $month): ?>
            <div class="month <?= $month['isCurrent'] ? 'large' : 'small' ?>">
                <h3><?= date('F Y', strtotime($month['year'] . '-' . $month['month'] . '-01')) ?></h3>
                <table>
                    <thead>
                        <tr>
                            <th>Пн</th>
                            <th>Вт</th>
                            <th>Ср</th>
                            <th>Чт</th>
                            <th>Пт</th>
                            <th>Сб</th>
                            <th>Вс</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <?php foreach ($month['calendar'] as $key => $day): ?>
                            <?php 
                                $hasEvent = isset($events[$day['date']]);
                                $classes = [$day['currentMonth'] ? 'current' : 'other'];
                                $eventData = '';
                                
                                if ($hasEvent) {
                                    $classes[] = 'event';
                                    $dayEvents = $events[$day['date']];
                                    foreach ($dayEvents as $event) {
                                        $classes[] = 'event-' . htmlspecialchars($event['event_type']);
                                    }
                                    $eventData = htmlspecialchars(json_encode($dayEvents));
                                }
                            ?>
                            <td class="<?= implode(' ', $classes) ?>"
                                <?php if ($hasEvent): ?>
                                data-event='<?= $eventData ?>'
                                style="cursor: pointer;"
                                onclick="openEventModal(this.dataset.event)"
                                <?php endif; ?>>
                                <span><?= $day['day'] ?></span>
                            </td>
                            <?php if (($key + 1) % 7 === 0): ?>
                        </tr>
                        <tr>
                            <?php endif; ?>
                            <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Модальное окно события -->
    <div id="event-modal" class="modal">
        <div class="modal-event">
            <h3 id="event-title">Событие</h3>
            <p><strong>Описание:</strong> <span id="event-description"></span></p>
            <p><strong>Начало:</strong> <span id="event-start-date"></span></p>
            <p><strong>Окончание:</strong> <span id="event-end-date"></span></p>
            <?php if (hasAccess('coach', $userRole)): ?>
                <a id="edit-event-link" href="#" class="btn btn-primary">Редактировать</a>
            <?php endif; ?>
            <button type="button" onclick="closeEventModal()" class="btn btn-secondary">Закрыть</button>
        </div>
    </div>
</main>

<script>
    function openEventModal(eventData) {
        if (!eventData) return;
        
        try {
            const events = JSON.parse(eventData);
            if (!events || events.length === 0) return;
            
            const event = events[0]; // Показываем первое событие, если их несколько
            
            document.getElementById('event-title').innerText = event.title;
            document.getElementById('event-description').innerText = event.description || 'Нет описания';
            document.getElementById('event-start-date').innerText = formatDateTime(event.start_date);
            document.getElementById('event-end-date').innerText = formatDateTime(event.end_date);
            
            // Обновляем ссылку на редактирование
            const editLink = document.getElementById('edit-event-link');
            if (editLink) {
                editLink.href = '/pages/calendar/event/edit.php?id=' + event.id;
            }
            
            document.getElementById('event-modal').style.display = 'flex';
        } catch (e) {
            console.error('Error parsing event data:', e);
        }
    }

    function closeEventModal() {
        document.getElementById('event-modal').style.display = 'none';
    }

    function formatDateTime(dateTimeStr) {
        if (!dateTimeStr) return 'Не указано';
        const date = new Date(dateTimeStr);
        return date.toLocaleString('ru-RU', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    // Закрытие модального окна при клике вне его
    window.onclick = function(event) {
        const modal = document.getElementById('event-modal');
        if (event.target === modal) {
            closeEventModal();
        }
    }
</script>

<?php include '../includes/footer.php'; ?>

