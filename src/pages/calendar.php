<?php
$page_title = 'Календарь событий';
include '../includes/header.php';
include '../config.php';
require_once '../functions/calendar_functions.php'; // Подключение функций

// Получение роли пользователя
$userRole = $_SESSION['user_role'] ?? 'guest';

// Логика добавления события
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_event'])) {
    if (canAddEvent($userRole)) {
        $title = $_POST['title'];
        $date = $_POST['date'];
        $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
        $time = $_POST['time'];
        $location = $_POST['location'];
        $type = $_POST['type'];
        $access = $type === 'other' ? $_POST['access'] : getAccessByType($type);
        $description = $_POST['description'];

        // Проверка, что дата окончания не раньше даты начала
        if ($end_date && strtotime($end_date) < strtotime($date)) {
            $errorMessage = "Дата окончания не может быть раньше даты начала.";
        } else {
            $sql = "INSERT INTO events (title, date, end_date, time, location, type, access, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('ssssssss', $title, $date, $end_date, $time, $location, $type, $access, $description);
            
            if ($stmt->execute()) {
                $successMessage = "Событие успешно добавлено!";
            } else {
                $errorMessage = "Ошибка при добавлении события: " . $stmt->error;
            }
        }
    } else {
        $errorMessage = "У вас нет прав для добавления событий.";
    }
}

// Определение текущего месяца и года
$currentMonth = isset($_GET['month']) ? (int)$_GET['month'] : date('m');
$currentYear = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

// Обработка изменения месяца
if ($currentMonth < 1) {
    $currentMonth = 12;
    $currentYear--;
} elseif ($currentMonth > 12) {
    $currentMonth = 1;
    $currentYear++;
}

// Получение событий
$startDate = date('Y-m-01', strtotime("-3 months", strtotime("$currentYear-$currentMonth-01")));
$endDate = date('Y-m-t', strtotime("+3 months", strtotime("$currentYear-$currentMonth-01")));
$events = getEvents($conn, $startDate, $endDate, $userRole);

// Генерация данных для отображения календаря
$months = getMonthsData($currentYear, $currentMonth, $events);

// Получение уровня доступа на основе типа события
function getAccessByType($type)
{
    $accessByType = [
        'match' => 'guest',
        'training' => 'player',
        'open_training' => 'fan',
        'meeting' => 'player',
        'admin_meeting' => 'coach',
        'masterclass' => 'guest',
        'sports_camp' => 'guest',
    ];

    return $accessByType[$type] ?? 'guest'; // По умолчанию — 'guest'
}

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
                <?php if (canAddEvent($userRole)): ?>
                    <button class="add-event-btn" onclick="openAddEventModal()">Добавить событие</button>
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
                        <td class="<?= $day['currentMonth'] ? 'current' : 'other' ?> <?= isset($events[$day['date']]) ? 'event' : '' ?>"
                            title="<?= isset($events[$day['date']]) ? $events[$day['date']][0]['title'] : '' ?>"
                            onclick="openEventModal('<?= isset($events[$day['date']]) ? htmlspecialchars(json_encode($events[$day['date']])) : '' ?>')">
                            <?= $day['day'] ?>
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
            <p><strong>Тип:</strong> <span id="event-type"></span></p>
            <p><strong>Дата начала:</strong> <span id="event-date"></span></p>
            <p><strong>Дата окончания:</strong> <span id="event-end-date"></span></p>
            <p><strong>Время:</strong> <span id="event-time"></span></p>
            <p><strong>Место:</strong> <span id="event-location"></span></p>
            <p><strong>Описание:</strong> <span id="event-description"></span></p>
            <button type="button" onclick="closeEventModal()">Закрыть</button>
        </div>
    </div>

    <div id="add-event-modal" class="modal">
        <div class="modal-content">
            <form action="calendar.php" method="POST">
                <h3>Добавить событие</h3>
                <?php if (isset($errorMessage)): ?>
                    <p style="color: red;"><?= htmlspecialchars($errorMessage); ?></p>
                <?php endif; ?>
                <?php if (isset($successMessage)): ?>
                    <p style="color: green;"><?= htmlspecialchars($successMessage); ?></p>
                <?php endif; ?>

                <label for="title">Название:</label>
                <input type="text" id="title" name="title" required>

                <label for="date">Дата начала:</label>
                <input type="date" id="date" name="date" required>

                <label for="end_date">Дата окончания:</label>
                <input type="date" id="end_date" name="end_date">

                <label for="time">Время:</label>
                <input type="time" id="time" name="time" required>

                <label for="location">Место:</label>
                <input type="text" id="location" name="location" required>

                <label for="type">Тип:</label>
                <select id="type" name="type" onchange="toggleAccessField(this.value)">
                    <?php foreach (getEventTypes() as $value => $label): ?>
                        <option value="<?= htmlspecialchars($value) ?>"><?= htmlspecialchars($label) ?></option>
                    <?php endforeach; ?>
                </select>

                <div id="access-field" style="display: none;">
                    <label for="access">Доступ:</label>
                    <select id="access" name="access">
                        <option value="guest">Гость</option>
                        <option value="fan">Фанат</option>
                        <option value="player">Игрок</option>
                        <option value="coach">Тренер</option>
                        <option value="admin">Админ</option>
                    </select>
                </div>

                <label for="description">Описание:</label>
                <textarea id="description" name="description"></textarea>

                <button type="submit" name="add_event">Сохранить</button>
                <button type="button" onclick="closeAddEventModal()">Отмена</button>
            </form>
        </div>
    </div>
</main>

<script>
    function openAddEventModal() {
        document.getElementById('add-event-modal').style.display = 'flex';
        // Установка минимальной даты окончания равной дате начала
        document.getElementById('date').addEventListener('change', function() {
            document.getElementById('end_date').min = this.value;
        });
    }

    function closeAddEventModal() {
        document.getElementById('add-event-modal').style.display = 'none';
    }

    function toggleAccessField(type) {
        const accessField = document.getElementById('access-field');
        if (type === 'other') {
            accessField.style.display = 'block';
        } else {
            accessField.style.display = 'none';
        }
    }

    function openEventModal(eventData) {
        if (!eventData) return;
        const event = JSON.parse(eventData)[0];
        document.getElementById('event-title').innerText = event.title;
        document.getElementById('event-type').innerText = event.type;
        document.getElementById('event-date').innerText = event.date;
        document.getElementById('event-end-date').innerText = event.end_date || 'Нет';
        document.getElementById('event-time').innerText = event.time;
        document.getElementById('event-location').innerText = event.location;
        document.getElementById('event-description').innerText = event.description;
        document.getElementById('event-modal').style.display = 'flex';
    }

    function closeEventModal() {
        document.getElementById('event-modal').style.display = 'none';
    }
</script>

<?php include '../includes/footer.php'; ?>

