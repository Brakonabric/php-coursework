<?php
session_start();
require_once '/var/www/html/config.php';
require_once '/var/www/html/includes/access.php';

header('Content-Type: application/json');

if (!isset($_SESSION['userName']) || (!hasAccess('admin', $_SESSION['userRole']) && !hasAccess('coach', $_SESSION['userRole']))) {
    http_response_code(403);
    echo json_encode(['error' => 'Piekļuve liegta']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$commentId = $data['commentId'] ?? null;

if (!$commentId) {
    http_response_code(400);
    echo json_encode(['error' => 'Trūkst komentāra ID']);
    exit;
}

try {
    $stmt = $conn->prepare('DELETE FROM gallery_comments WHERE id = ?');
    $stmt->bind_param('i', $commentId);
    $success = $stmt->execute();
    
    if ($success) {
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Kļūda dzēšot komentāru']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Kļūda dzēšot komentāru']);
} 