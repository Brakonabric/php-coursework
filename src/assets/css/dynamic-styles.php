<?php
header('Content-Type: text/css');
require_once '../../config.php';

// Получение цветов для типов событий
$sql = "SELECT id, color FROM event_types";
$result = $conn->query($sql);
$eventColors = [];
while ($row = $result->fetch_assoc()) {
    $eventColors[$row['id']] = $row['color'];
}

// Генерация CSS для каждого типа события
foreach ($eventColors as $type => $color): ?>
.event-<?= $type ?>::before {
    background-color: <?= $color ?>;
}
<?php endforeach; ?> 