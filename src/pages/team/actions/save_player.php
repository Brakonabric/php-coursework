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

// Устанавливаем заголовки
header('Content-Type: application/json; charset=utf-8');

function sendJsonResponse($data, $statusCode = 200) {
    global $conn;
    
    // Очищаем буфер вывода
    if (ob_get_length()) ob_clean();
    
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    
    if ($conn) {
        $conn->close();
    }
    exit();
}

try {
    // Логируем входящие данные
    error_log("POST data: " . print_r($_POST, true));
    error_log("FILES data: " . print_r($_FILES, true));

    // Проверяем наличие обязате��ьных полей
    $required_fields = ['number', 'name', 'position', 'height', 'weight', 'birth_date', 'accuracy'];
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || trim($_POST[$field]) === '') {
            sendJsonResponse([
                'success' => false,
                'error' => "Поле {$field} обязательно для заполнения"
            ], 400);
        }
    }

    // Валидация данных
    $id = isset($_POST['id']) ? filter_var($_POST['id'], FILTER_VALIDATE_INT) : null;
    $number = filter_var($_POST['number'], FILTER_VALIDATE_INT);
    $name = trim($_POST['name']);
    $position = trim($_POST['position']);
    $height = filter_var($_POST['height'], FILTER_VALIDATE_INT);
    $weight = filter_var($_POST['weight'], FILTER_VALIDATE_INT);
    $birth_date = trim($_POST['birth_date']);
    $accuracy = filter_var($_POST['accuracy'], FILTER_VALIDATE_INT);
    $goals = filter_var($_POST['goals'] ?? 0, FILTER_VALIDATE_INT);
    $penalties = filter_var($_POST['penalties'] ?? 0, FILTER_VALIDATE_INT);

    // Дополнительная валидация
    if ($number === false || $number <= 0) {
        sendJsonResponse(['success' => false, 'error' => 'Некорректный номер игрока'], 400);
    }
    if ($height === false || $height <= 0) {
        sendJsonResponse(['success' => false, 'error' => 'Некорректный рост'], 400);
    }
    if ($weight === false || $weight <= 0) {
        sendJsonResponse(['success' => false, 'error' => 'Некорректный вес'], 400);
    }
    if ($accuracy === false || $accuracy < 0 || $accuracy > 100) {
        sendJsonResponse(['success' => false, 'error' => 'Точность должна быть от 0 до 100'], 400);
    }
    if (!in_array($position, ['GK', 'DF', 'MF', 'FW'])) {
        sendJsonResponse(['success' => false, 'error' => 'Некорректная позиция'], 400);
    }
    if ($goals === false || $goals < 0) {
        sendJsonResponse(['success' => false, 'error' => 'Некорректное количество голов'], 400);
    }
    if ($penalties === false || $penalties < 0) {
        sendJsonResponse(['success' => false, 'error' => 'Некорректное количество штрафов'], 400);
    }

    // Начинаем транзакцию
    $conn->begin_transaction();

    try {
        if ($id) {
            // Обновление существующего игрока
            $stmt = $conn->prepare("
                UPDATE players 
                SET number = ?, name = ?, position = ?, height = ?, 
                    weight = ?, birth_date = ?, accuracy = ?
                WHERE id = ?
            ");
            $stmt->bind_param('issiisii', $number, $name, $position, $height, $weight, $birth_date, $accuracy, $id);
        } else {
            // Добавление нового игрока
            $stmt = $conn->prepare("
                INSERT INTO players (number, name, position, height, weight, birth_date, accuracy)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param('issiisi', $number, $name, $position, $height, $weight, $birth_date, $accuracy);
        }

        if (!$stmt->execute()) {
            throw new Exception($stmt->error);
        }

        // Если это новый игрок, получаем его ID
        $player_id = $id ?: $conn->insert_id;

        // Обработка голов
        if ($goals > 0) {
            $stmt = $conn->prepare("DELETE FROM player_goals WHERE player_id = ?");
            $stmt->bind_param('i', $player_id);
            $stmt->execute();

            $stmt = $conn->prepare("INSERT INTO player_goals (player_id, scored_at) VALUES (?, NOW())");
            $stmt->bind_param('i', $player_id);
            for ($i = 0; $i < $goals; $i++) {
                if (!$stmt->execute()) {
                    throw new Exception("О��ибка при сохранении голов");
                }
            }
        }

        // Обработка штрафов
        if ($penalties > 0) {
            $stmt = $conn->prepare("DELETE FROM player_penalties WHERE player_id = ?");
            $stmt->bind_param('i', $player_id);
            $stmt->execute();

            $stmt = $conn->prepare("INSERT INTO player_penalties (player_id, received_at) VALUES (?, NOW())");
            $stmt->bind_param('i', $player_id);
            for ($i = 0; $i < $penalties; $i++) {
                if (!$stmt->execute()) {
                    throw new Exception("Ошибка при сохранении штрафов");
                }
            }
        }

        // Обработка фотографии, если она загружена
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $photo = $_FILES['photo'];
            $ext = strtolower(pathinfo($photo['name'], PATHINFO_EXTENSION));
            
            // Проверяем тип файла
            if (!in_array($ext, ['jpg', 'jpeg', 'png'])) {
                throw new Exception('Разрешены только изображения в форматах JPG и PNG');
            }

            // Создаем директорию, если её нет
            $upload_dir = __DIR__ . '/../../../assets/images/uploads/players';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $photo_name = "player_{$player_id}.{$ext}";
            $photo_path = $upload_dir . '/' . $photo_name;
            
            if (!move_uploaded_file($photo['tmp_name'], $photo_path)) {
                throw new Exception('Ошибка при сохранении фотографии');
            }

            // Обновляем путь к фото в базе
            $photo_url = "/assets/images/uploads/players/{$photo_name}";
            $stmt = $conn->prepare("UPDATE players SET photo_url = ? WHERE id = ?");
            $stmt->bind_param('si', $photo_url, $player_id);
            
            if (!$stmt->execute()) {
                throw new Exception($stmt->error);
            }
        }

        // Если всё успешно, фиксируем транзакцию
        $conn->commit();
        
        sendJsonResponse(['success' => true]);

    } catch (Exception $e) {
        // В случае ошибки откатываем транзакцию
        $conn->rollback();
        throw $e;
    }

} catch (Exception $e) {
    error_log("Error in save_player.php: " . $e->getMessage());
    sendJsonResponse([
        'success' => false,
        'error' => $e->getMessage()
    ], 500);
} 