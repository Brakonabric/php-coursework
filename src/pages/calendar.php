<?php
$page_title = 'Kalendārs';
include '../includes/header.php';
include '../config.php';
require_once '../modules/calendar/functions.php';

$userRole = $_SESSION['userRole'] ?? 'guest';

$today = new DateTime();
$currentMonth = isset($_GET['month']) ? (int)$_GET['month'] : (int)$today->format('m');
$currentYear = isset($_GET['year']) ? (int)$_GET['year'] : (int)$today->format('Y');

$date = new DateTime();
$date->setDate($currentYear, $currentMonth, 1);

$currentMonth = (int)$date->format('m');
$currentYear = (int)$date->format('Y');

$prevDate = clone $date;
$prevDate->modify('-1 month');
$nextDate = clone $date;
$nextDate->modify('+1 month');

$startDate = date('Y-m-d', strtotime("-3 months", $date->getTimestamp()));
$endDate = date('Y-m-d', strtotime("+3 months", $date->getTimestamp()));
$events = getEvents($conn, $startDate, $endDate, $userRole);

$months = getMonthsData($currentYear, $currentMonth, $events);

$latvianMonths = [
    1 => 'Janvāris',
    2 => 'Februāris',
    3 => 'Marts',
    4 => 'Aprīlis',
    5 => 'Maijs',
    6 => 'Jūnijs',
    7 => 'Jūlijs',
    8 => 'Augusts',
    9 => 'Septembris',
    10 => 'Oktobris',
    11 => 'Novembris',
    12 => 'Decembris'
];

function formatMonthYear($date, $latvianMonths) {
    if (is_string($date)) {
        $date = new DateTime($date);
    }
    $month = (int)$date->format('n');
    $year = $date->format('Y');
    return $latvianMonths[$month] . ' ' . $year;
}
?>

<main>
    <div class="calendar-container">
        <div class="calendar-grid">
            <div class="past-months">
                <?php for ($i = 0; $i < 3; $i++): ?>
                    <?php $month = $months[$i]; ?>
                    <div class="month">
                        <h3><?= formatMonthYear($month['year'] . '-' . $month['month'] . '-01', $latvianMonths) ?></h3>
                        <table>
                            <thead>
                                <tr>
                                    <th>P</th>
                                    <th>O</th>
                                    <th>T</th>
                                    <th>C</th>
                                    <th>Pk</th>
                                    <th>S</th>
                                    <th>Sv</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_chunk($month['calendar'], 7) as $week): ?>
                                    <tr>
                                        <?php foreach ($week as $day): ?>
                                            <?php 
                                                $hasEvent = isset($events[$day['date']]);
                                                $classes = [$day['currentMonth'] ? 'current' : 'other'];
                                                $eventData = '';
                                                
                                                if ($hasEvent) {
                                                    $classes[] = 'event';
                                                    $dayEvents = $events[$day['date']];
                                                    foreach ($dayEvents as $event) {
                                                        $eventType = strtolower(htmlspecialchars($event['event_type']));
                                                        $classes[] = 'event-' . $eventType;
                                                    }
                                                    $eventData = htmlspecialchars(json_encode($dayEvents), ENT_QUOTES, 'UTF-8');
                                                }
                                            ?>
                                            <td class="<?= implode(' ', $classes) ?>"
                                                <?php if ($hasEvent): ?>
                                                data-event='<?= $eventData ?>'
                                                onclick="openEventModal(this.dataset.event)"
                                                style="cursor: pointer;"
                                                <?php endif; ?>>
                                                <?= $day['day'] ?>
                                            </td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endfor; ?>
            </div>

            <div class="navigation">
                <div class="control">
                    <a href="?month=<?= $prevDate->format('m') ?>&year=<?= $prevDate->format('Y') ?>" class="nav-btn">&#9664; Iepriekšējais</a>
                    <span><?= formatMonthYear($date, $latvianMonths) ?></span>
                    <a href="?month=<?= $nextDate->format('m') ?>&year=<?= $nextDate->format('Y') ?>" class="nav-btn">Nākamais &#9654;</a>
                </div>
                <?php if (hasAccess('coach', $userRole)): ?>
                    <div class="add-event-btn-box">
                        <a href="/pages/calendar/event/create.php" class="link-btn btn-primary">
                            <span class="material-icons">add</span>
                            Izveidot notikumu
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <div class="month current-month">
                <h3><?= formatMonthYear($date, $latvianMonths) ?></h3>
                <table>
                    <thead>
                        <tr>
                            <th>P</th>
                            <th>O</th>
                            <th>T</th>
                            <th>C</th>
                            <th>Pk</th>
                            <th>S</th>
                            <th>Sv</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_chunk($months[3]['calendar'], 7) as $week): ?>
                            <tr>
                                <?php foreach ($week as $day): ?>
                                    <?php 
                                        $hasEvent = isset($events[$day['date']]);
                                        $classes = [$day['currentMonth'] ? 'current' : 'other'];
                                        $eventData = '';
                                        
                                        if ($hasEvent) {
                                            $classes[] = 'event';
                                            $dayEvents = $events[$day['date']];
                                            foreach ($dayEvents as $event) {
                                                $eventType = strtolower(htmlspecialchars($event['event_type']));
                                                $classes[] = 'event-' . $eventType;
                                            }
                                            $eventData = htmlspecialchars(json_encode($dayEvents), ENT_QUOTES, 'UTF-8');
                                        }
                                    ?>
                                    <td class="<?= implode(' ', $classes) ?>"
                                        <?php if ($hasEvent): ?>
                                        data-event='<?= $eventData ?>'
                                        onclick="openEventModal(this.dataset.event)"
                                        style="cursor: pointer;"
                                        <?php endif; ?>>
                                        <?= $day['day'] ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>


        <div class="calendar-legend">
            <h4>Notikumu veidi:</h4>
            <div class="legend-items">
                <div class="legend-item">
                    <span class="color-box event-match"></span>
                    <span>Spēle</span>
                </div>
                <div class="legend-item">
                    <span class="color-box event-teamtraining"></span>
                    <span>Komandas treniņš</span>
                </div>
                <div class="legend-item">
                    <span class="color-box event-individualtraining"></span>
                    <span>Individuālais treniņš</span>
                </div>
                <div class="legend-item">
                    <span class="color-box event-teammeeting"></span>
                    <span>Komandas sanāksme</span>
                </div>
                <div class="legend-item">
                    <span class="color-box event-coachmeeting"></span>
                    <span>Treneru sanāksme</span>
                </div>
                <div class="legend-item">
                    <span class="color-box event-medicalcheckup"></span>
                    <span>Medicīniskā pārbaude</span>
                </div>
                <div class="legend-item">
                    <span class="color-box event-teambuilding"></span>
                    <span>Komandas pasākums</span>
                </div>
                <div class="legend-item">
                    <span class="color-box event-tournament"></span>
                    <span>Turnīrs</span>
                </div>
            </div>
        </div>
    </div>

            <div class="future-months">
                <?php for ($i = 4; $i < 7; $i++): ?>
                    <?php $month = $months[$i]; ?>
                    <div class="month">
                        <h3><?= formatMonthYear($month['year'] . '-' . $month['month'] . '-01', $latvianMonths) ?></h3>
                        <table>
                            <thead>
                                <tr>
                                    <th>P</th>
                                    <th>O</th>
                                    <th>T</th>
                                    <th>C</th>
                                    <th>Pk</th>
                                    <th>S</th>
                                    <th>Sv</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_chunk($month['calendar'], 7) as $week): ?>
                                    <tr>
                                        <?php foreach ($week as $day): ?>
                                            <?php 
                                                $hasEvent = isset($events[$day['date']]);
                                                $classes = [$day['currentMonth'] ? 'current' : 'other'];
                                                $eventData = '';
                                                
                                                if ($hasEvent) {
                                                    $classes[] = 'event';
                                                    $dayEvents = $events[$day['date']];
                                                    foreach ($dayEvents as $event) {
                                                        $eventType = strtolower(htmlspecialchars($event['event_type']));
                                                        $classes[] = 'event-' . $eventType;
                                                    }
                                                    $eventData = htmlspecialchars(json_encode($dayEvents), ENT_QUOTES, 'UTF-8');
                                                }
                                            ?>
                                            <td class="<?= implode(' ', $classes) ?>"
                                                <?php if ($hasEvent): ?>
                                                data-event='<?= $eventData ?>'
                                                onclick="openEventModal(this.dataset.event)"
                                                style="cursor: pointer;"
                                                <?php endif; ?>>
                                                <?= $day['day'] ?>
                                            </td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endfor; ?>
            </div>
        </div>

    <div id="event-modal" class="modal">
        <div class="modal-event">
            <div class="modal-header">
                <div class="title-box">
                    <h3 id="event-title">Notikums</h3>
                    <span id="event-type" class="event-type"></span>
                </div>
                <button type="button" onclick="closeEventModal()" class="close-btn">
                    <span class="material-icons">close</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="event-info">
                    <div class="info-row">
                        <span class="material-icons">description</span>
                        <div>
                            <label>Apraksts:</label>
                            <p id="event-description"></p>
                        </div>
                    </div>
                    <div class="info-row">
                        <span class="material-icons">schedule</span>
                        <div>
                            <label>Sākums:</label>
                            <p id="event-start-date"></p>
                        </div>
                    </div>
                    <div id="end-date-row" class="info-row" style="display: none;">
                        <span class="material-icons">schedule</span>
                        <div>
                            <label>Beigas:</label>
                            <p id="event-end-date"></p>
                        </div>
                    </div>
                    <div class="info-row">
                        <span class="material-icons">location_on</span>
                        <div>
                            <label>Vieta:</label>
                            <p id="event-location"></p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <?php if (hasAccess('coach', $userRole)): ?>
                    <a id="edit-event-link" href="#" class="link-btn btn-secondary">
                        <span class="material-icons">edit</span>
                        Rediģēt
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<script>
    function openEventModal(eventData) {
        if (!eventData) return;
        
        try {
            const events = JSON.parse(eventData);
            if (!events || events.length === 0) return;
            
            const event = events[0];
            const eventTypes = {
                'match': 'Spēle',
                'teamtraining': 'Komandas treniņš',
                'individualtraining': 'Individuālais treniņš',
                'teammeeting': 'Komandas sanāksme',
                'coachmeeting': 'Treneru sanāksme',
                'medicalcheckup': 'Medicīniskā pārbaude',
                'teambuilding': 'Komandas pasākums',
                'tournament': 'Turnīrs'
            };
            
            document.getElementById('event-title').innerText = event.title || 'Nav nosaukuma';
            document.getElementById('event-type').innerText = eventTypes[event.event_type] || event.event_type || 'Nav norādīts';
            document.getElementById('event-description').innerText = event.description || 'Nav apraksta';
            document.getElementById('event-start-date').innerText = formatDateTime(event.start_date);
            document.getElementById('event-location').innerText = event.location || 'Nav norādīta';
            
            const endDateRow = document.getElementById('end-date-row');
            if (event.start_date !== event.end_date) {
                document.getElementById('event-end-date').innerText = formatDateTime(event.end_date);
                endDateRow.style.display = 'flex';
            } else {
                endDateRow.style.display = 'none';
            }
            
            const editLink = document.getElementById('edit-event-link');
            if (editLink) {
                editLink.href = '/pages/calendar/event/edit.php?id=' + event.id;
            }
            
            const modal = document.getElementById('event-modal');
            modal.style.display = 'flex';
        } catch (e) {
            console.error('Error parsing event data:', e);
        }
    }

    function closeEventModal() {
        const modal = document.getElementById('event-modal');
        modal.style.display = 'none';
    }

    const latvianMonths = [
        'Janvāris', 'Februāris', 'Marts', 'Aprīlis', 'Maijs', 'Jūnijs',
        'Jūlijs', 'Augusts', 'Septembris', 'Oktobris', 'Novembris', 'Decembris'
    ];

    function formatDateTime(dateTimeStr) {
        if (!dateTimeStr) return 'Nav norādīts';
        const date = new Date(dateTimeStr);
        const day = date.getDate();
        const month = latvianMonths[date.getMonth()];
        const year = date.getFullYear();
        const hours = String(date.getHours()).padStart(2, '0');
        const minutes = String(date.getMinutes()).padStart(2, '0');
        
        return `${day}. ${month} ${year}, ${hours}:${minutes}`;
    }

    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('event-modal');
        
        window.onclick = function(event) {
            if (event.target === modal) {
                closeEventModal();
            }
        };

        const modalContent = modal.querySelector('.modal-event');
        modalContent.onclick = function(event) {
            event.stopPropagation();
        };
    });
</script>

<?php include '../includes/footer.php'; ?>

