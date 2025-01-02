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
    $data = json_decode(file_get_contents('php://input'), true);
    $keepUsers = isset($data['keepUsers']) ? $data['keepUsers'] : false;
    
    $conn->begin_transaction();
    
    $directories = [
        $_SERVER['DOCUMENT_ROOT'] . '/assets/images/uploads/news',
        $_SERVER['DOCUMENT_ROOT'] . '/assets/images/uploads/gallery'
    ];
    
    foreach ($directories as $dir) {
        if (file_exists($dir)) {
            $files = glob($dir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        } else {
            mkdir($dir, 0777, true);
        }
    }
    
    $conn->query('SET FOREIGN_KEY_CHECKS = 0');
    
    $tables = [
        'gallery_likes',
        'gallery_comments',
        'gallery',
        'news_comments',
        'news',
        'events'
    ];
    
    if (!$keepUsers) {
        $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->bind_param('i', $_SESSION['userId']);
        $stmt->execute();
        $adminData = $stmt->get_result()->fetch_assoc();
        
        $tables[] = 'users';
    }
    
    foreach ($tables as $table) {
        $conn->query("TRUNCATE TABLE $table");
    }
    
    if (!$keepUsers) {
        $sql = "INSERT INTO users (name, surname, email, password, role) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('sssss', 
            $adminData['name'],
            $adminData['surname'],
            $adminData['email'],
            $adminData['password'],
            $adminData['role']
        );
        $stmt->execute();

        $_SESSION['userId'] = $conn->insert_id;
    }
    
    $conn->query('SET FOREIGN_KEY_CHECKS = 1');
    
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => $keepUsers ? 
            'Datubāze atiestatīta, saglabājot lietotāju datus' : 
            'Datubāze pilnībā atiestatīta'
    ]);
    exit;
} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}
?> 