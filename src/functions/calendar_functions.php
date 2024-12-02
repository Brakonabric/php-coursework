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
    $events = [];
    
    $sql = "SELECT * FROM events WHERE 
            (start_date BETWEEN ? AND ?) OR 
            (end_date BETWEEN ? AND ?) OR
            (start_date <= ? AND end_date >= ?)
            ORDER BY start_date ASC";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ssssss', $startDate, $endDate, $startDate, $endDate, $startDate, $endDate);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        // Проверяем видимость события для текущей роли
        $visible_roles = json_decode($row['event_visibility'] ?? '[]', true);
        if (in_array($userRole, $visible_roles)) {
            // Получаем даты начала и окончания события
            $start = new DateTime($row['start_date']);
            $end = new DateTime($row['end_date']);
            
            // Создаем период между датами
            $interval = new DateInterval('P1D');
            $dateRange = new DatePeriod($start, $interval, $end->modify('+1 day'));
            
            // Добавляем событие на каждый день периода
            foreach ($dateRange as $date) {
                $eventDate = $date->format('Y-m-d');
                if (!isset($events[$eventDate])) {
                    $events[$eventDate] = [];
                }
                $events[$eventDate][] = $row;
            }
        }
    }
    
    return $events;
}

/**
 * Возвращает доступные типы событий
 */
function getEventTypes(): array
{
    return [
        'match' => 'Матч',
        'training' => 'Тренировка',
        'open_training' => 'Открытая тренировка',
        'meeting' => 'Собрание',
        'admin_meeting' => 'Административное собрание',
        'masterclass' => 'Мастер-класс',
        'sports_camp' => 'Спортивный лагерь',
        'other' => 'Другое'
    ];
}

/**
 * Проверяет доступ к типу события для роли
 */
function hasEventTypeAccess($eventType, $userRole): bool
{
    $typeAccess = [
        'match' => ['admin', 'coach', 'team_member', 'fan', 'guest'],
        'training' => ['admin', 'coach', 'team_member'],
        'open_training' => ['admin', 'coach', 'team_member', 'fan'],
        'meeting' => ['admin', 'coach', 'team_member'],
        'admin_meeting' => ['admin', 'coach'],
        'masterclass' => ['admin', 'coach', 'team_member', 'fan', 'guest'],
        'sports_camp' => ['admin', 'coach', 'team_member', 'fan', 'guest'],
        'other' => ['admin', 'coach', 'team_member', 'fan']
    ];

    return isset($typeAccess[$eventType]) && in_array($userRole, $typeAccess[$eventType]);
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
    
    // От -3 до +3 месяца (всего 7 месяцев)
    for ($i = -3; $i <= 3; $i++) {
        $timestamp = strtotime("$currentYear-$currentMonth-01 $i months");
        $year = date('Y', $timestamp);
        $month = date('m', $timestamp);
        
        $firstDay = date('N', strtotime("$year-$month-01")) - 1;
        $daysInMonth = date('t', strtotime("$year-$month-01"));
        
        $calendar = [];
        
        // Добавляем дни предыдущего месяца
        if ($firstDay > 0) {
            $prevMonth = date('m', strtotime("$year-$month-01 -1 month"));
            $prevYear = date('Y', strtotime("$year-$month-01 -1 month"));
            $daysInPrevMonth = date('t', strtotime("$prevYear-$prevMonth-01"));
            
            for ($day = $daysInPrevMonth - $firstDay + 1; $day <= $daysInPrevMonth; $day++) {
                $date = sprintf('%s-%s-%02d', $prevYear, $prevMonth, $day);
                $calendar[] = [
                    'day' => $day,
                    'date' => $date,
                    'currentMonth' => false
                ];
            }
        }
        
        // Добавляем дни текущего месяца
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = sprintf('%s-%s-%02d', $year, $month, $day);
            $calendar[] = [
                'day' => $day,
                'date' => $date,
                'currentMonth' => true
            ];
        }
        
        // Добавляем дни следующего месяца
        $remainingDays = 42 - count($calendar); // 6 недель по 7 дней
        if ($remainingDays > 0) {
            $nextMonth = date('m', strtotime("$year-$month-01 +1 month"));
            $nextYear = date('Y', strtotime("$year-$month-01 +1 month"));
            
            for ($day = 1; $day <= $remainingDays; $day++) {
                $date = sprintf('%s-%s-%02d', $nextYear, $nextMonth, $day);
                $calendar[] = [
                    'day' => $day,
                    'date' => $date,
                    'currentMonth' => false
                ];
            }
        }
        
        $months[] = [
            'year' => $year,
            'month' => $month,
            'calendar' => $calendar,
            'isCurrent' => ($i === 0)
        ];
    }
    
    return $months;
}
