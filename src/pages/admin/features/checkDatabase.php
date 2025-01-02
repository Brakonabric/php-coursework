<?php
session_start();
require_once '../../../config.php';
require_once '../../../includes/access.php';

header('Content-Type: application/json');

if (!isset($_SESSION['userRole']) || !hasAccess('admin', $_SESSION['userRole'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Piekļuve liegta']);
    exit;
}

try {
    $tables = [
        'users' => 'Lietotāji',
        'news' => 'Ziņas',
        'news_comments' => 'Ziņu komentāri',
        'gallery' => 'Galerija',
        'gallery_comments' => 'Galerijas komentāri',
        'gallery_likes' => 'Galerijas novērtējumi',
        'events' => 'Notikumi'
    ];
    
    $stats = [];
    $issues = [];
    
    foreach ($tables as $table => $label) {
        $result = $conn->query("SELECT COUNT(*) as count FROM $table");
        $row = $result->fetch_assoc();
        $stats[$label] = $row['count'];

        $result = $conn->query("SHOW CREATE TABLE $table");
        $row = $result->fetch_assoc();

        if ($table === 'gallery' && !strpos($row['Create Table'], 'KEY `source_idx`')) {
            $issues[] = "Tabulā 'gallery' trūkst indeksa source_idx";
        }

        if ($table === 'news_comments') {
            $result = $conn->query("SELECT news_id FROM $table WHERE news_id NOT IN (SELECT id FROM news)");
            if ($result->num_rows > 0) {
                $issues[] = "Atrasti ziņu komentāri ar neeksistējošām ziņām";
            }
        }
        
        if ($table === 'gallery_comments') {
            $result = $conn->query("SELECT photo_id FROM $table WHERE photo_id NOT IN (SELECT id FROM gallery)");
            if ($result->num_rows > 0) {
                $issues[] = "Atrasti galerijas komentāri ar neeksistējošiem attēliem";
            }
        }
    }

    $result = $conn->query("SELECT image_path FROM gallery");
    while ($row = $result->fetch_assoc()) {
        if (!file_exists($_SERVER['DOCUMENT_ROOT'] . $row['image_path'])) {
            $issues[] = "Trūkst faila: " . $row['image_path'];
        }
    }
    
    echo json_encode([
        'tables' => $stats,
        'issues' => $issues
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
} 