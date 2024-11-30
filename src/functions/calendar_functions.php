<?php
require_once '../includes/access.php';

/**
 * Проверяет, может ли пользователь добавлять события.
 */
function canAddEvent($role): bool
{
    return hasAccess('coach', $role); // Только тренеры и админы могут добавлять события
}

/**
 * Получает события для заданного диапазона дат.
 */
function getEvents($conn, $startDate, $endDate, $userRole): array
{
    $sql = "SELECT * FROM events WHERE date BETWEEN ? AND ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ss', $startDate, $endDate);
    $stmt->execute();
    $result = $stmt->get_result();
    $events = [];

    while ($row = $result->fetch_assoc()) {
        if (hasAccess($row['access'], $userRole)) {
            $events[$row['date']][] = $row;
        }
    }

    return $events;
}

/**
 * Генерирует массив данных для отображения календаря.
 */
function generateMonth($year, $month): array
{
    $firstDayOfMonth = strtotime("$year-$month-01");
    $daysInMonth = date('t', $firstDayOfMonth);
    $firstDayOfWeek = date('N', $firstDayOfMonth) - 1; // 0 - Понедельник
    $lastMonthDays = date('t', strtotime("-1 month", $firstDayOfMonth));

    $calendar = [];

    // Дни предыдущего месяца
    for ($i = $firstDayOfWeek - 1; $i >= 0; $i--) {
        $day = $lastMonthDays - $i;
        $calendar[] = [
            'day' => $day,
            'currentMonth' => false,
            'date' => date('Y-m-d', strtotime("-1 month", $firstDayOfMonth) + (($day - 1) * 86400)),
        ];
    }

    // Дни текущего месяца
    for ($day = 1; $day <= $daysInMonth; $day++) {
        $calendar[] = [
            'day' => $day,
            'currentMonth' => true,
            'date' => "$year-$month-" . str_pad($day, 2, '0', STR_PAD_LEFT),
        ];
    }

    // Дни следующего месяца
    $nextDays = 7 - (count($calendar) % 7);
    if ($nextDays < 7) {
        for ($day = 1; $day <= $nextDays; $day++) {
            $calendar[] = [
                'day' => $day,
                'currentMonth' => false,
                'date' => date('Y-m-d', strtotime("+1 month", $firstDayOfMonth) + (($day - 1) * 86400)),
            ];
        }
    }

    return $calendar;
}

/**
 * Получает массив данных для отображения месяцев.
 */
function getMonthsData($currentYear, $currentMonth, $events): array
{
    $months = [];
    for ($i = -3; $i <= 3; $i++) {
        $monthTimestamp = strtotime("$i months", strtotime("$currentYear-$currentMonth-01"));
        $year = date('Y', $monthTimestamp);
        $month = date('m', $monthTimestamp);
        $months[] = [
            'year' => $year,
            'month' => $month,
            'calendar' => generateMonth($year, $month, $events),
            'isCurrent' => ($i === 0),
        ];
    }
    return $months;
}
