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

error_log('Received data: ' . print_r($data, true));

if (!isset($data['userId']) || !isset($data['role'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Trūkst nepieciešamo parametru']);
    exit;
}

$userId = (int)$data['userId'];
$role = $conn->real_escape_string($data['role']);

$allowedRoles = ['admin', 'coach', 'teamMember', 'fan', 'guest'];
if (!in_array($role, $allowedRoles)) {
    http_response_code(400);
    echo json_encode(['error' => 'Nederīga loma']);
    exit;
}

try {
    $sql = "UPDATE users SET role = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('si', $role, $userId);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        throw new Exception($conn->error);
    }
} catch (Exception $e) {
    error_log('Error in changeRole.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Kļūda mainot lomu: ' . $e->getMessage()]);
} 