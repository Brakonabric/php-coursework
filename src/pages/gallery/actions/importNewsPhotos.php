<?php
session_start();
require_once '../../../config.php';
require_once '../../../includes/access.php';

error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');

if (!isset($_SESSION['userId']) || !hasAccess('coach', $_SESSION['userRole'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Piekļuve liegta']);
    exit();
}

try {
    $conn->begin_transaction();

    $sql = "SELECT id, image_path_preview, image_path_extra FROM news";
    $result = $conn->query($sql);

    if (!$result) {
        throw new Exception($conn->error);
    }

    $imported = 0;
    $errors = 0;

    while ($row = $result->fetch_assoc()) {
        if ($row['image_path_preview']) {
            $checkQuery = "SELECT id FROM gallery WHERE image_path = ?";
            $checkStmt = $conn->prepare($checkQuery);
            $checkStmt->bind_param('s', $row['image_path_preview']);
            $checkStmt->execute();
            
            if ($checkStmt->get_result()->num_rows === 0) {
                $insertQuery = "INSERT INTO gallery (image_path, user_id, source_type, source_id) 
                               VALUES (?, ?, 'news', ?)";
                $stmt = $conn->prepare($insertQuery);
                $stmt->bind_param('sis', $row['image_path_preview'], $_SESSION['userId'], $row['id']);
                
                if ($stmt->execute()) {
                    $imported++;
                } else {
                    $errors++;
                }
            }
        }
        
        if ($row['image_path_extra']) {
            $extraImages = json_decode($row['image_path_extra'], true);
            if (is_array($extraImages)) {
                foreach ($extraImages as $image_path) {
                    $checkQuery = "SELECT id FROM gallery WHERE image_path = ?";
                    $checkStmt = $conn->prepare($checkQuery);
                    $checkStmt->bind_param('s', $image_path);
                    $checkStmt->execute();
                    
                    if ($checkStmt->get_result()->num_rows === 0) {
                        $insertQuery = "INSERT INTO gallery (image_path, user_id, source_type, source_id) 
                                       VALUES (?, ?, 'news', ?)";
                        $stmt = $conn->prepare($insertQuery);
                        $stmt->bind_param('sis', $image_path, $_SESSION['userId'], $row['id']);
                        
                        if ($stmt->execute()) {
                            $imported++;
                        } else {
                            $errors++;
                        }
                    }
                }
            }
        }
    }

    $conn->commit();
    echo json_encode([
        'success' => true,
        'message' => "Veiksmīgi importēti {$imported} attēli" . ($errors > 0 ? " ({$errors} kļūdas)" : "")
    ]);
    exit();

} catch (Exception $e) {
    if ($conn->connect_errno) {
        $error = 'Kļūda savienojumā ar datubāzi';
    } else {
        $error = 'Kļūda importējot fotoattēlus: ' . $e->getMessage();
    }
    
    $conn->rollback();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $error
    ]);
    exit();
} 