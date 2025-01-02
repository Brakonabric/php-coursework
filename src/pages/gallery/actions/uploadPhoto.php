<?php
session_start();
require_once '../../../config.php';
require_once '../../../includes/access.php';

if (!isset($_SESSION['userRole']) || !hasAccess('coach', $_SESSION['userRole'])) {
    header('HTTP/1.1 403 Forbidden');
    exit('Piekļuve liegta');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    exit('Metode nav atļauta');
}

if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
    header('HTTP/1.1 400 Bad Request');
    exit('Faila augšupielādes kļūda');
}

$file = $_FILES['photo'];
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];

if (!in_array($file['type'], $allowedTypes)) {
    header('HTTP/1.1 400 Bad Request');
    exit('Neatbalstīts faila tips');
}

$uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/assets/images/uploads/gallery/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
$fileName = uniqid() . '.' . $fileExtension;
$filePath = $uploadDir . $fileName;

if (!move_uploaded_file($file['tmp_name'], $filePath)) {
    header('HTTP/1.1 500 Internal Server Error');
    exit('Kļūda saglabājot failu');
}

$relativePath = '/assets/images/uploads/gallery/' . $fileName;
$userId = $_SESSION['userId'];

$stmt = $conn->prepare("INSERT INTO gallery (image_path, user_id) VALUES (?, ?)");
$stmt->bind_param("si", $relativePath, $userId);

if (!$stmt->execute()) {
    unlink($filePath);
    header('HTTP/1.1 500 Internal Server Error');
    exit('Kļūda saglabājot datubāzē');
}

header('Location: /pages/gallery.php');
exit();