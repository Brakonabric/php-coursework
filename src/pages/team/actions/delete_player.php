<?php
ini_set('display_errors', 0);
error_reporting(0);

require_once __DIR__ . '/../../../config.php';
$conn->set_charset("utf8mb4");
header('Content-Type: application/json; charset=utf-8');

function sendJsonResponse($data, $statusCode = 200) {
    global $conn;
    if (ob_get_length()) ob_clean();
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    if ($conn) $conn->close();
    exit();
}

try {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!isset($input['id'])) {
        sendJsonResponse(['success' => false, 'error' => 'ID игрока не указан'], 400);
    }

    $id = filter_var($input['id'], FILTER_VALIDATE_INT);
    if ($id === false || $id <= 0) {
        sendJsonResponse(['success' => false, 'error' => 'Некорректный ID игрока'], 400);
    }

    $conn->begin_transaction();

    try {
        // Удаляем голы игрока
        $stmt = $conn->prepare("DELETE FROM player_goals WHERE player_id = ?");
        $stmt->bind_param('i', $id);
        if (!$stmt->execute()) {
            throw new Exception("Ошибка при удалении голов");
        }

        // Удаляем штрафы игрока
        $stmt = $conn->prepare("DELETE FROM player_penalties WHERE player_id = ?");
        $stmt->bind_param('i', $id);
        if (!$stmt->execute()) {
            throw new Exception("Ошибка при удалении штрафов");
        }

        // Получаем путь к фото
        $stmt = $conn->prepare("SELECT photo_url FROM players WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $player = $result->fetch_assoc();

        // Удаляем игрока
        $stmt = $conn->prepare("DELETE FROM players WHERE id = ?");
        $stmt->bind_param('i', $id);
        if (!$stmt->execute()) {
            throw new Exception("Ошибка при удалении игрока");
        }

        if ($stmt->affected_rows === 0) {
            throw new Exception("Игрок не найден");
        }

        // Удаляем фото
        if ($player && $player['photo_url']) {
            $photo_path = __DIR__ . '/../../../' . ltrim($player['photo_url'], '/');
            if (file_exists($photo_path)) {
                unlink($photo_path);
            }
        }

        $conn->commit();
        sendJsonResponse(['success' => true]);

    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }

} catch (Exception $e) {
    sendJsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
} 