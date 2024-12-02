<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/config.php';

// Проверка прав доступа
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['trainer', 'admin'])) {
    header('HTTP/1.1 403 Forbidden');
    exit('Доступ запрещен');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    exit('Метод не разрешен');
}

// Проверка загруженного файла
if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
    header('HTTP/1.1 400 Bad Request');
    exit('Ошибка загрузки файла');
}

$file = $_FILES['photo'];
$allowed_types = ['image/jpeg', 'image/png', 'image/gif'];

if (!in_array($file['type'], $allowed_types)) {
    header('HTTP/1.1 400 Bad Request');
    exit('Неподдерживаемый тип файла');
}

// Создание директории для загрузок, если она не существует
$upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/gallery/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Генерация уникального имени файла
$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = uniqid() . '.' . $extension;
$filepath = $upload_dir . $filename;

// Перемещение загруженного файла
if (!move_uploaded_file($file['tmp_name'], $filepath)) {
    header('HTTP/1.1 500 Internal Server Error');
    exit('Ошибка сохранения файла');
}

// Сохранение информации в базе данных
$title = isset($_POST['title']) ? trim($_POST['title']) : null;
$relative_path = '/uploads/gallery/' . $filename;
$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("INSERT INTO gallery (title, image_path, user_id) VALUES (?, ?, ?)");
$stmt->bind_param("ssi", $title, $relative_path, $user_id);

if (!$stmt->execute()) {
    unlink($filepath); // Удаляем файл, если не удалось сохранить в БД
    header('HTTP/1.1 500 Internal Server Error');
    exit('Ошибка сохранения в базе данных');
}

header('Location: /pages/gallery.php');
exit();