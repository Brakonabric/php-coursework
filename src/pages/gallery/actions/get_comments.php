<?php
session_start();
require_once '../../../config.php';
header('Content-Type: application/json');

if (!isset($_GET['photo_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'ID фото не указан']);
    exit;
}

$photo_id = (int)$_GET['photo_id'];

$query = "SELECT gc.*, u.name as user_name 
    FROM gallery_comments gc
    LEFT JOIN users u ON gc.user_id = u.id
    WHERE gc.photo_id = ?
    ORDER BY gc.created_at DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $photo_id);
$stmt->execute();
$result = $stmt->get_result();

$comments = [];
while ($comment = $result->fetch_assoc()) {
    $comments[] = $comment;
}

echo json_encode($comments);