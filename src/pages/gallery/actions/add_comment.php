<?php
session_start();
require_once '../../../config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Необходима авторизация']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['photo_id']) || !isset($data['comment'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Не все данные предоставлены']);
    exit;
}

$photo_id = (int)$data['photo_id'];
$user_id = $_SESSION['user_id'];
$comment = trim($data['comment']);

if (empty($comment)) {
    http_response_code(400);
    echo json_encode(['error' => 'Комментарий не может быть пустым']);
    exit;
}

$query = "INSERT INTO gallery_comments (photo_id, user_id, comment) VALUES (?, ?, ?)";
$stmt = $conn->prepare($query);
$stmt->bind_param("iis", $photo_id, $user_id, $comment);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'comment_id' => $stmt->insert_id]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Ошибка при добавлении комментария']);
} 