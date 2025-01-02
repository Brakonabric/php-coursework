<?php
define('DB_HOST', 'mysql');
define('DB_PORT', 3306);
define('DB_USER', 'root');
define('DB_PASS', 'root');
define('DB_NAME', 'php_coursework');

error_log("Connecting to database: " . DB_HOST . ":" . DB_PORT);

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);

if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    die("Connection failed: " . $conn->connect_error);
}

error_log("Database connection successful");
$conn->set_charset("utf8mb4");
?>
