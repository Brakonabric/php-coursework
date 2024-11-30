<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Удаляем все переменные сессии
session_unset();

// Уничтожаем сессию
session_destroy();

// Перенаправляем на главную страницу
header('Location: /index.php');
exit();
?>
