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
    $data = json_decode(file_get_contents('php://input'), true);
    $newsId = $data['id'] ?? null;

    if (!$newsId) {
        throw new Exception('Nav norādīts ziņas ID');
    }

    $conn->begin_transaction();

    $stmt = $conn->prepare("SELECT image_path_preview, image_path_extra FROM news WHERE id = ?");
    $stmt->bind_param('i', $newsId);
    $stmt->execute();
    $result = $stmt->get_result();
    $news = $result->fetch_assoc();

    if (!$news) {
        throw new Exception('Ziņas nav atrastas');
    }

    if ($news['image_path_preview']) {
        $previewPath = $_SERVER['DOCUMENT_ROOT'] . $news['image_path_preview'];
        if (file_exists($previewPath)) {
            unlink($previewPath);
        }
    }

    if ($news['image_path_extra']) {
        $extraImages = explode(',', $news['image_path_extra']);
        foreach ($extraImages as $image) {
            $imagePath = $_SERVER['DOCUMENT_ROOT'] . trim($image);
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
        }
    }

    $stmt = $conn->prepare("DELETE FROM gallery WHERE source_type = 'news' AND source_id = ?");
    $stmt->bind_param('i', $newsId);
    $stmt->execute();

    $stmt = $conn->prepare("DELETE FROM news_comments WHERE news_id = ?");
    $stmt->bind_param('i', $newsId);
    $stmt->execute();

    $stmt = $conn->prepare("DELETE FROM news WHERE id = ?");
    $stmt->bind_param('i', $newsId);
    $stmt->execute();

    $conn->commit();
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    if ($conn->connect_error === null) {
        $conn->rollback();
    }
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?> 