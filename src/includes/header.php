<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Подключение файла access.php
include_once $_SERVER['DOCUMENT_ROOT'] . '/includes/access.php';

// Установка роли по умолчанию
if (!isset($_SESSION['user_role'])) {
    $_SESSION['user_role'] = 'guest';
}

// Вывод текущей роли (для отладки)
echo "<p>Ваша роль: {$_SESSION['user_role']}</p>";
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/assets/css/style.css">
    <title><?= isset($page_title) ? htmlspecialchars($page_title) : 'Nonames Team'; ?></title>
    <link rel="icon" href="/favicon.ico" type="image/x-icon">
</head>
<body>
<header>
    <nav class="nav-bar">
        <a href="/index.php">NONAMES</a>
        <div class="navigation">
            <a href="/index.php">Главная</a>
            <a href="/pages/news.php">Новости</a>
            <a href="/pages/contact.php">Команда</a>
            <a href="/pages/contact.php">Контакты</a>
            <a href="/pages/calendar.php">Календарь</a>
        </div>
        <div class="auth-links">
            <?php if (isset($_SESSION['user_name'])): ?>
                <span>Привет, <?= htmlspecialchars($_SESSION['user_name']); ?>!</span>
                <a href="/auth/logout.php">Log Out</a>
            <?php else: ?>
                <a href="/auth/login.php">Log In</a>
            <?php endif; ?>
        </div>
    </nav>
</header>
