<?php
require_once __DIR__ . '/../../includes/access.php';

function canAddEvent($role): bool
{
    return hasAccess('coach', $role);
}

function getEvents($conn, $startDate, $endDate, $userRole): array
{
    $events = [];
    
    try {
        $sql = "SELECT * FROM events WHERE 
                (start_date BETWEEN ? AND ?) OR 
                (end_date BETWEEN ? AND ?) OR
                (start_date <= ? AND end_date >= ?)
                ORDER BY start_date ASC";
                
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            error_log("Failed to prepare statement: " . $conn->error);
            return [];
        }
        
        $stmt->bind_param('ssssss', $startDate, $endDate, $startDate, $endDate, $startDate, $endDate);
        if (!$stmt->execute()) {
            error_log("Failed to execute statement: " . $stmt->error);
            return [];
        }
        
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $visible_roles = json_decode($row['event_visibility'] ?? '[]', true);
            if (empty($visible_roles) || in_array($userRole, $visible_roles)) {
                $start = new DateTime($row['start_date']);
                $end = new DateTime($row['end_date']);

                $interval = new DateInterval('P1D');
                $dateRange = new DatePeriod($start, $interval, $end->modify('+1 day'));

                foreach ($dateRange as $date) {
                    $eventDate = $date->format('Y-m-d');
                    if (!isset($events[$eventDate])) {
                        $events[$eventDate] = [];
                    }
                    $events[$eventDate][] = $row;
                }
            }
        }
    } catch (Exception $e) {
        error_log("Error in getEvents: " . $e->getMessage());
        return [];
    }
    
    return $events;
}

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

function hasEventTypeAccess($eventType, $userRole): bool
{
    $typeAccess = [
        'match' => ['admin', 'trainer', 'teamMember', 'fan', 'guest'],
        'training' => ['admin', 'trainer', 'teamMember'],
        'openTraining' => ['admin', 'trainer', 'teamMember', 'fan'],
        'meeting' => ['admin', 'trainer', 'teamMember'],
        'adminMeeting' => ['admin', 'trainer'],
        'masterclass' => ['admin', 'trainer', 'teamMember', 'fan', 'guest'],
        'sportsCamp' => ['admin', 'trainer', 'teamMember', 'fan', 'guest'],
        'other' => ['admin', 'trainer', 'teamMember', 'fan']
    ];

    return isset($typeAccess[$eventType]) && in_array($userRole, $typeAccess[$eventType]);
}

function generateMonth($year, $month): array
{
    $firstDayOfMonth = strtotime("$year-$month-01");
    $daysInMonth = date('t', $firstDayOfMonth);
    $firstDayOfWeek = date('N', $firstDayOfMonth) - 1;
    $lastMonthDays = date('t', strtotime("-1 month", $firstDayOfMonth));

    $calendar = [];

    for ($i = $firstDayOfWeek - 1; $i >= 0; $i--) {
        $day = $lastMonthDays - $i;
        $calendar[] = [
            'day' => $day,
            'currentMonth' => false,
            'date' => date('Y-m-d', strtotime("-1 month", $firstDayOfMonth) + (($day - 1) * 86400)),
        ];
    }

    for ($day = 1; $day <= $daysInMonth; $day++) {
        $calendar[] = [
            'day' => $day,
            'currentMonth' => true,
            'date' => "$year-$month-" . str_pad($day, 2, '0', STR_PAD_LEFT),
        ];
    }

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

function getMonthsData($currentYear, $currentMonth, $events): array
{
    $months = [];

    $timestamp = mktime(0, 0, 0, $currentMonth, 1, $currentYear);
    $currentYear = date('Y', $timestamp);
    $currentMonth = date('m', $timestamp);

    for ($i = -3; $i <= 3; $i++) {
        $timestamp = strtotime("$currentYear-$currentMonth-01 $i months");
        $year = date('Y', $timestamp);
        $month = date('m', $timestamp);
        
        $firstDay = date('N', strtotime("$year-$month-01")) - 1;
        $daysInMonth = date('t', strtotime("$year-$month-01"));
        
        $calendar = [];

        if ($firstDay > 0) {
            $prevTimestamp = strtotime("$year-$month-01 -1 month");
            $prevMonth = date('m', $prevTimestamp);
            $prevYear = date('Y', $prevTimestamp);
            $daysInPrevMonth = date('t', $prevTimestamp);
            
            for ($day = $daysInPrevMonth - $firstDay + 1; $day <= $daysInPrevMonth; $day++) {
                $date = sprintf('%s-%s-%02d', $prevYear, $prevMonth, $day);
                $calendar[] = [
                    'day' => $day,
                    'date' => $date,
                    'currentMonth' => false
                ];
            }
        }

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = sprintf('%s-%s-%02d', $year, $month, $day);
            $calendar[] = [
                'day' => $day,
                'date' => $date,
                'currentMonth' => true
            ];
        }

        $remainingDays = 42 - count($calendar);
        if ($remainingDays > 0) {
            $nextTimestamp = strtotime("$year-$month-01 +1 month");
            $nextMonth = date('m', $nextTimestamp);
            $nextYear = date('Y', $nextTimestamp);
            
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

function canManageEvents($role) {
    return hasAccess('coach', $role);
}

$eventTypeRoles = [
    'match' => ['admin', 'coach', 'teamMember', 'fan', 'guest'],
    'training' => ['admin', 'coach', 'teamMember'],
    'openTraining' => ['admin', 'coach', 'teamMember', 'fan'],
    'meeting' => ['admin', 'coach', 'teamMember'],
    'adminMeeting' => ['admin', 'coach'],
    'masterclass' => ['admin', 'coach', 'teamMember', 'fan', 'guest'],
    'sportsCamp' => ['admin', 'coach', 'teamMember', 'fan', 'guest'],
    'other' => ['admin', 'coach', 'teamMember', 'fan']
];
