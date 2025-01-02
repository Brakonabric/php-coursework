<?php
session_start();
require_once '/var/www/html/config.php';
require_once '/var/www/html/includes/access.php';

header('Content-Type: application/json');

if (!isset($_SESSION['userName']) || !hasAccess('admin', $_SESSION['userRole'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Piekļuve liegta']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

error_log('Received data in toggleComments.php: ' . print_r($data, true));

if (!isset($data['userId']) || !isset($data['canComment'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Trūkst nepieciešamo parametru']);
    exit;
}

$userId = (int)$data['userId'];
$canComment = (bool)$data['canComment'];

try {
    $sql = "UPDATE users SET can_comment = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $canCommentInt = $canComment ? 1 : 0;
    $stmt->bind_param('ii', $canCommentInt, $userId);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        throw new Exception($conn->error);
    }
} catch (Exception $e) {
    error_log('Error in toggleComments.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Kļūda mainot komentēšanas atļauju: ' . $e->getMessage()]);
} 