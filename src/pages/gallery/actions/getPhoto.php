<?php
session_start();
require_once '../../../config.php';
header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Nav norādīts ID']);
    exit;
}

$photo_id = (int)$_GET['id'];
$user_id = $_SESSION['userId'] ?? null;

$query = "SELECT g.*, 
    (SELECT COUNT(*) FROM gallery_likes gl WHERE gl.photo_id = g.id) as likes_count,
    " . ($user_id ? "(SELECT COUNT(*) > 0 FROM gallery_likes gl WHERE gl.photo_id = g.id AND gl.user_id = ?) as is_liked" : "FALSE as is_liked") . "
    FROM gallery g
    WHERE g.id = ?";

$stmt = $conn->prepare($query);

if ($user_id) {
    $stmt->bind_param("ii", $user_id, $photo_id);
} else {
    $stmt->bind_param("i", $photo_id);
}

$stmt->execute();
$result = $stmt->get_result();
$photo = $result->fetch_assoc();

if (!$photo) {
    http_response_code(404);
    echo json_encode(['error' => 'Fotoattēls nav atrasts']);
    exit;
}

echo json_encode($photo); 