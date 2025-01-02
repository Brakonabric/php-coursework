<?php
session_start();
require_once '/var/www/html/config.php';
require_once '/var/www/html/includes/access.php';

error_log("getComments.php started");
error_log("User role: " . ($_SESSION['userRole'] ?? 'not set'));

header('Content-Type: application/json');

if (!isset($_SESSION['userName']) || (!hasAccess('admin', $_SESSION['userRole']) && !hasAccess('coach', $_SESSION['userRole']))) {
    error_log("Access denied for user: " . ($_SESSION['userName'] ?? 'not set'));
    http_response_code(403);
    echo json_encode(['error' => 'Piekļuve liegta']);
    exit;
}

try {
    error_log("Executing SQL query for comments");
    $sql = "SELECT gc.*, u.name, u.surname 
            FROM gallery_comments gc 
            LEFT JOIN users u ON gc.user_id = u.id 
            ORDER BY gc.created_at DESC";
            
    $result = $conn->query($sql);
    
    if (!$result) {
        error_log("SQL Error: " . $conn->error);
        throw new Exception($conn->error);
    }
    
    $comments = [];
    while ($row = $result->fetch_assoc()) {
        $comments[] = [
            'id' => $row['id'],
            'user_name' => $row['name'] . ' ' . $row['surname'],
            'content' => $row['comment'],
            'created_at' => $row['created_at'],
            'type' => 'gallery'
        ];
    }
    
    error_log("Found " . count($comments) . " comments");
    echo json_encode(['success' => true, 'comments' => $comments]);
} catch (Exception $e) {
    error_log("Error in getComments.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Kļūda, ielādējot komentārus: ' . $e->getMessage()]);
}