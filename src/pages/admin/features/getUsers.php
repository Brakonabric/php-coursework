<?php
session_start();
require_once '/var/www/html/config.php';
require_once '/var/www/html/includes/access.php';

error_log("getUsers.php started");
error_log("User role: " . ($_SESSION['userRole'] ?? 'not set'));

header('Content-Type: application/json');

if (!isset($_SESSION['userName']) || (!hasAccess('admin', $_SESSION['userRole']) && !hasAccess('coach', $_SESSION['userRole']))) {
    error_log("Access denied for user: " . ($_SESSION['userName'] ?? 'not set'));
    http_response_code(403);
    echo json_encode(['error' => 'Piekļuve liegta']);
    exit;
}

try {
    error_log("Executing SQL query for users");
    $sql = "SELECT id, name, surname, email, role, can_comment FROM users ORDER BY id";
    $result = $conn->query($sql);
    
    if (!$result) {
        error_log("SQL Error: " . $conn->error);
        throw new Exception($conn->error);
    }
    
    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = [
            'id' => $row['id'],
            'name' => $row['name'] . ' ' . $row['surname'],
            'email' => $row['email'],
            'role' => $row['role'],
            'can_comment' => (bool)$row['can_comment']
        ];
    }
    
    error_log("Found " . count($users) . " users");
    echo json_encode($users);
} catch (Exception $e) {
    error_log("Error in getUsers.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Kļūda, ielādējot lietotājus: ' . $e->getMessage()]);
} 