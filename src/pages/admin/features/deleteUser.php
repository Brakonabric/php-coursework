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
$userId = $data['userId'] ?? null;

if (!$userId) {
    http_response_code(400);
    echo json_encode(['error' => 'Trūkst lietotāja ID']);
    exit;
}

try {
    $conn->begin_transaction();
    
    $stmt = $conn->prepare('DELETE FROM comments WHERE user_id = ?');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    
    $stmt = $conn->prepare('DELETE FROM users WHERE id = ?');
    $stmt->bind_param('i', $userId);
    $success = $stmt->execute();
    
    if ($success) {
        $conn->commit();
        echo json_encode(['success' => true]);
    } else {
        $conn->rollback();
        http_response_code(500);
        echo json_encode(['error' => 'Kļūda dzēšot lietotāju']);
    }
} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['error' => 'Kļūda dzēšot lietotāju']);
} 