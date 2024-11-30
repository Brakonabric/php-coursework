<?php
define('DB_HOST', 'mysql'); // Имя сервиса из docker-compose.yml
define('DB_USER', 'root');  // Пользователь базы данных
define('DB_PASS', 'root');  // Пароль базы данных
define('DB_NAME', 'php_coursework'); // Имя базы данных

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("Ошибка подключения: " . $conn->connect_error);
}
?>
