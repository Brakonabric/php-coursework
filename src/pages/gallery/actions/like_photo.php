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
if (!isset($data['photo_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'ID фото не указан']);
    exit;
}

$photo_id = (int)$data['photo_id'];
$user_id = $_SESSION['user_id'];

// Проверяем, существует ли лайк
$check_query = "SELECT id FROM gallery_likes WHERE photo_id = ? AND user_id = ?";
$check_stmt = $conn->prepare($check_query);
$check_stmt->bind_param("ii", $photo_id, $user_id);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($result->num_rows > 0) {
    // Если лайк существует - удаляем его
    $delete_query = "DELETE FROM gallery_likes WHERE photo_id = ? AND user_id = ?";
    $stmt = $conn->prepare($delete_query);
    $stmt->bind_param("ii", $photo_id, $user_id);
    $stmt->execute();
    $is_liked = false;
} else {
    // Если лайка нет - добавляем
    $insert_query = "INSERT INTO gallery_likes (photo_id, user_id) VALUES (?, ?)";
    $stmt = $conn->prepare($insert_query);
    $stmt->bind_param("ii", $photo_id, $user_id);
    $stmt->execute();
    $is_liked = true;
}

// Получаем общее количество лайков
$count_query = "SELECT COUNT(*) as count FROM gallery_likes WHERE photo_id = ?";
$count_stmt = $conn->prepare($count_query);
$count_stmt->bind_param("i", $photo_id);
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$likes_count = $count_result->fetch_assoc()['count'];

echo json_encode([
    'success' => true,
    'is_liked' => $is_liked,
    'likes_count' => $likes_count
]);