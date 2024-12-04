<?php
// Включаем вывод ошибок для отладки
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Записываем ошибки в лог
ini_set('log_errors', 1);
ini_set('error_log', '/tmp/php-error.log');

// Начинаем буферизацию вывода
ob_start();

require_once __DIR__ . '/../../../config.php';

// Устанавливаем кодировку соединения
$conn->set_charset("utf8mb4");

// Логируем входящие данные
error_log("GET params: " . print_r($_GET, true));

// Устанавливаем заголовки
header('Content-Type: application/json; charset=utf-8');
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

function sendJsonResponse($data, $statusCode = 200) {
    global $conn;
    
    // Очищаем буфер вывода
    ob_clean();
    
    // Логируем ответ
    error_log("Response data: " . print_r($data, true));
    error_log("Status code: " . $statusCode);
    
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    
    if ($conn) {
        $conn->close();
    }
    exit();
}

try {
    if (!isset($_GET['id'])) {
        error_log("ID не указан");
        sendJsonResponse(['error' => 'ID игрока не указан', 'status' => 'error'], 400);
    }

    $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    error_log("Filtered ID: " . print_r($id, true));
    
    if ($id === false || $id <= 0) {
        error_log("Некорректный ID: " . print_r($_GET['id'], true));
        sendJsonResponse(['error' => 'Некорректный ID игрока', 'status' => 'error'], 400);
    }

    // Получаем основные данные игрока
    $query = "
        SELECT 
            p.id,
            p.number,
            p.name,
            p.position,
            COALESCE(p.height, 0) as height,
            COALESCE(p.weight, 0) as weight,
            COALESCE(DATE_FORMAT(p.birth_date, '%Y-%m-%d'), '') as birth_date,
            COALESCE(p.accuracy, 0) as accuracy,
            COALESCE(p.photo_url, '') as photo_url,
            (SELECT COUNT(*) FROM player_goals WHERE player_id = p.id) as goals,
            (SELECT COUNT(*) FROM player_penalties WHERE player_id = p.id) as penalties
        FROM players p
        WHERE p.id = ?
        LIMIT 1
    ";
    
    error_log("SQL Query: " . $query);
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        error_log("Ошибка подготовки запроса: " . $conn->error);
        sendJsonResponse([
            'error' => 'Ошибка подготовки запроса: ' . $conn->error,
            'status' => 'error'
        ], 500);
    }
    
    $stmt->bind_param('i', $id);
    
    if (!$stmt->execute()) {
        error_log("Ошибка выполнения запроса: " . $stmt->error);
        sendJsonResponse([
            'error' => 'Ошибка выполнения запроса: ' . $stmt->error,
            'status' => 'error'
        ], 500);
    }
    
    $result = $stmt->get_result();
    if (!$result) {
        error_log("Ошибка получения результата: " . $stmt->error);
        sendJsonResponse([
            'error' => 'Ошибка получения результата',
            'status' => 'error'
        ], 500);
    }
    
    $player = $result->fetch_assoc();
    error_log("Raw player data: " . print_r($player, true));
    
    if (!$player) {
        error_log("Игрок не найден с ID: " . $id);
        sendJsonResponse(['error' => 'Игрок не найден', 'status' => 'error'], 404);
    }

    // Преобразуем числовые поля
    $numericFields = ['id', 'number', 'height', 'weight', 'accuracy', 'goals', 'penalties'];
    foreach ($numericFields as $field) {
        if (isset($player[$field])) {
            $player[$field] = (int)$player[$field];
        }
    }

    error_log("Processed player data: " . print_r($player, true));
    
    $stmt->close();
    sendJsonResponse($player);

} catch (Exception $e) {
    error_log("Exception in get_player.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    sendJsonResponse([
        'error' => 'Ошибка при получении данных игрока',
        'status' => 'error',
        'debug' => $e->getMessage()
    ], 500);
} 