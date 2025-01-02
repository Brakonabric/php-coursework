<?php
session_start();
require_once '../../../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['userId'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Lai pievienotu komentāru, jums jāpiesakās sistēmā']);
    exit;
}

$check_sql = "SELECT can_comment FROM users WHERE id = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param('i', $_SESSION['userId']);
$check_stmt->execute();
$check_result = $check_stmt->get_result();
$user_data = $check_result->fetch_assoc();

if (!$user_data['can_comment']) {
    http_response_code(403);
    echo json_encode(['error' => 'Jums nav atļauts pievienot komentārus']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

error_log('Received data in addComment.php: ' . print_r($data, true));

if (!isset($data['photoId']) || !isset($data['text'])) {
    error_log('Missing required parameters. Data: ' . print_r($data, true));
    http_response_code(400);
    echo json_encode(['error' => 'Trūkst nepieciešamo parametru']);
    exit;
}

$photoId = (int)$data['photoId'];
$text = trim($data['text']);

if (empty($text)) {
    http_response_code(400);
    echo json_encode(['error' => 'Komentārs nevar būt tukšs']);
    exit;
}

try {
    $sql = "INSERT INTO gallery_comments (photo_id, user_id, comment) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('iis', $photoId, $_SESSION['userId'], $text);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        throw new Exception($conn->error);
    }
} catch (Exception $e) {
    error_log('Error in addComment.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Kļūda pievienojot komentāru: ' . $e->getMessage()]);
} 