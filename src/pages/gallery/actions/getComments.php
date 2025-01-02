<?php
session_start();
require_once '../../../config.php';

header('Content-Type: application/json');

if (!isset($_GET['photoId'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing photo ID']);
    exit;
}

$photoId = (int)$_GET['photoId'];

$can_comment = false;
$is_logged_in = isset($_SESSION['userId']);

if ($is_logged_in) {
    $check_sql = "SELECT can_comment FROM users WHERE id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param('i', $_SESSION['userId']);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    $user_data = $check_result->fetch_assoc();
    $can_comment = $user_data['can_comment'];
}

$sql = "SELECT c.*, u.name as author 
        FROM gallery_comments c 
        LEFT JOIN users u ON c.user_id = u.id 
        WHERE c.photo_id = ? 
        ORDER BY c.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $photoId);
$stmt->execute();
$result = $stmt->get_result();

$comments = [];
while ($row = $result->fetch_assoc()) {
    $comments[] = [
        'id' => $row['id'],
        'author' => htmlspecialchars($row['author']),
        'text' => htmlspecialchars($row['comment']),
        'date' => date('d.m.Y H:i', strtotime($row['created_at']))
    ];
}

echo json_encode([
    'success' => true,
    'comments' => $comments,
    'can_comment' => $can_comment,
    'is_logged_in' => $is_logged_in
]); 