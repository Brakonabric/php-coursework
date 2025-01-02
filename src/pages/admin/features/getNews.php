<?php
session_start();
require_once '../../../config.php';
require_once '../../../includes/access.php';

header('Content-Type: application/json');

if (!isset($_SESSION['userRole']) || (!hasAccess('admin', $_SESSION['userRole']) && !hasAccess('coach', $_SESSION['userRole']))) {
    http_response_code(403);
    echo json_encode(['error' => 'Piekļuve liegta']);
    exit;
}

try {
    $sql = "SELECT n.*, 
            (SELECT COUNT(*) FROM news_comments WHERE news_id = n.id) as comment_count,
            (SELECT COUNT(*) FROM gallery WHERE source_type = 'news' AND source_id = n.id) as image_count
            FROM news n
            ORDER BY n.created_at DESC";
            
    $result = $conn->query($sql);
    $news = [];
    
    while ($row = $result->fetch_assoc()) {
        $news[] = $row;
    }
    
    echo json_encode($news);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?> 