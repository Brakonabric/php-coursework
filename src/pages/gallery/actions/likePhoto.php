<?php
session_start();
require_once '../../../config.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['photo_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Не указан ID фотографии']);
    exit;
}

$photoId = (int)$data['photo_id'];
$userId = isset($_SESSION['userId']) ? $_SESSION['userId'] : null;
$action = isset($data['action']) ? $data['action'] : 'toggle';

$isLiked = false;
if ($userId) {
    $checkQuery = "SELECT id FROM gallery_likes WHERE photo_id = ? AND user_id = ?";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bind_param("ii", $photoId, $userId);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    $isLiked = $result->num_rows > 0;
}

if ($action === 'toggle' && $userId) {
    if ($isLiked) {
        $deleteQuery = "DELETE FROM gallery_likes WHERE photo_id = ? AND user_id = ?";
        $stmt = $conn->prepare($deleteQuery);
        $stmt->bind_param("ii", $photoId, $userId);
        $stmt->execute();
        $isLiked = false;
    } else {
        $insertQuery = "INSERT INTO gallery_likes (photo_id, user_id) VALUES (?, ?)";
        $stmt = $conn->prepare($insertQuery);
        $stmt->bind_param("ii", $photoId, $userId);
        $stmt->execute();
        $isLiked = true;
    }
}

$countQuery = "SELECT COUNT(*) as count FROM gallery_likes WHERE photo_id = ?";
$countStmt = $conn->prepare($countQuery);
$countStmt->bind_param("i", $photoId);
$countStmt->execute();
$countResult = $countStmt->get_result();
$likesCount = $countResult->fetch_assoc()['count'];

echo json_encode([
    'success' => true,
    'is_liked' => $isLiked,
    'likes_count' => $likesCount
]); 