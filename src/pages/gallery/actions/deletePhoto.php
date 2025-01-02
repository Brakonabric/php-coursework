<?php
ob_start();
session_start();
header('Content-Type: application/json');

require_once '../../../config.php';
require_once '../../../includes/access.php';

error_log("DeletePhoto.php started");

if (!$conn) {
    error_log("Database connection failed");
    http_response_code(500);
    die(json_encode(['success' => false, 'error' => 'Database connection failed']));
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log("Method not allowed: " . $_SERVER['REQUEST_METHOD']);
    http_response_code(405);
    die(json_encode(['success' => false, 'error' => 'Method not allowed']));
}

if (!isset($_SESSION['userRole']) || !hasAccess('coach', $_SESSION['userRole'])) {
    error_log("Access denied for role: " . ($_SESSION['userRole'] ?? 'no role'));
    http_response_code(403);
    die(json_encode(['success' => false, 'error' => 'Access denied']));
}

$input = file_get_contents('php://input');
error_log("Received input: " . $input);

$data = json_decode($input, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    error_log("JSON decode error: " . json_last_error_msg());
    http_response_code(400);
    die(json_encode(['success' => false, 'error' => 'Invalid JSON: ' . json_last_error_msg()]));
}

if (!isset($data['photo_id'])) {
    error_log("Photo ID is missing");
    http_response_code(400);
    die(json_encode(['success' => false, 'error' => 'Photo ID is required']));
}

$photoId = (int)$data['photo_id'];
error_log("Processing photo ID: " . $photoId);

try {
    $stmt = $conn->prepare("SELECT image_path FROM gallery WHERE id = ?");
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param('i', $photoId);
    if (!$stmt->execute()) {
        error_log("Execute failed: " . $stmt->error);
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $photo = $result->fetch_assoc();

    if (!$photo) {
        error_log("Photo not found with ID: " . $photoId);
        http_response_code(404);
        die(json_encode(['success' => false, 'error' => 'Photo not found']));
    }

    error_log("Found photo with path: " . $photo['image_path']);

    if (!$conn->begin_transaction()) {
        error_log("Could not begin transaction");
        throw new Exception('Could not begin transaction');
    }

    try {
        $stmt = $conn->prepare("DELETE FROM gallery_comments WHERE photo_id = ?");
        if (!$stmt) {
            error_log("Prepare failed (comments): " . $conn->error);
            throw new Exception("Prepare failed (comments): " . $conn->error);
        }
        $stmt->bind_param('i', $photoId);
        if (!$stmt->execute()) {
            error_log("Execute failed (comments): " . $stmt->error);
            throw new Exception("Execute failed (comments): " . $stmt->error);
        }
        error_log("Successfully deleted comments");

        $stmt = $conn->prepare("DELETE FROM gallery_likes WHERE photo_id = ?");
        if (!$stmt) {
            error_log("Prepare failed (likes): " . $conn->error);
            throw new Exception("Prepare failed (likes): " . $conn->error);
        }
        $stmt->bind_param('i', $photoId);
        if (!$stmt->execute()) {
            error_log("Execute failed (likes): " . $stmt->error);
            throw new Exception("Execute failed (likes): " . $stmt->error);
        }
        error_log("Successfully deleted likes");

        $stmt = $conn->prepare("DELETE FROM gallery WHERE id = ?");
        if (!$stmt) {
            error_log("Prepare failed (gallery): " . $conn->error);
            throw new Exception("Prepare failed (gallery): " . $conn->error);
        }
        $stmt->bind_param('i', $photoId);
        if (!$stmt->execute()) {
            error_log("Execute failed (gallery): " . $stmt->error);
            throw new Exception("Execute failed (gallery): " . $stmt->error);
        }
        error_log("Successfully deleted from gallery table");

        if (!$conn->commit()) {
            error_log("Could not commit transaction");
            throw new Exception('Could not commit transaction');
        }
        error_log("Successfully committed transaction");

        $filePath = $_SERVER['DOCUMENT_ROOT'] . $photo['image_path'];
        error_log("Attempting to delete file: " . $filePath);
        
        if (file_exists($filePath)) {
            if (!unlink($filePath)) {
                error_log("Failed to delete file: " . $filePath . ". Error: " . error_get_last()['message']);
                throw new Exception('Could not delete image file: ' . error_get_last()['message']);
            }
            error_log("Successfully deleted file: " . $filePath);
        } else {
            error_log("File does not exist: " . $filePath);
        }

        ob_end_clean();
        error_log("Operation completed successfully");
        die(json_encode(['success' => true]));

    } catch (Exception $e) {
        error_log("Error in transaction: " . $e->getMessage());
        $conn->rollback();
        throw $e;
    }

} catch (Exception $e) {
    error_log("Error in deletePhoto.php: " . $e->getMessage());
    http_response_code(500);
    ob_end_clean();
    die(json_encode(['success' => false, 'error' => $e->getMessage()]));
} 