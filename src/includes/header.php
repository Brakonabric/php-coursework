<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include_once $_SERVER['DOCUMENT_ROOT'] . '/includes/access.php';

if (!isset($_SESSION['user_role'])) {
    $_SESSION['user_role'] = 'guest';
}

?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/assets/css/variables.css">
    <link rel="stylesheet" href="/assets/css/style.css">
    <?php 
    $current_page = basename($_SERVER['PHP_SELF'], '.php');
    if ($current_page === 'index') {
        echo '<link rel="stylesheet" href="/assets/css/pages/home.css">';
    } elseif ($current_page === 'news') {
        echo '<link rel="stylesheet" href="/assets/css/pages/news.css">';
    } elseif ($current_page === 'team') {
        echo '<link rel="stylesheet" href="/assets/css/pages/team.css">';
    } elseif ($current_page === 'contact') {
        echo '<link rel="stylesheet" href="/assets/css/pages/contact.css">';
    } elseif ($current_page === 'calendar') {
        echo '<link rel="stylesheet" href="/assets/css/pages/calendar.css">';
    } elseif ($current_page === 'gallery') {
        echo '<link rel="stylesheet" href="/assets/css/pages/gallery.css">';
    }
    ?>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <title><?= isset($page_title) ? htmlspecialchars($page_title) : 'Nonames Team'; ?></title>
    <link rel="icon" href="/favicon.ico" type="image/x-icon">
</head>
<body>
<header>
    <nav>
        <a href="/index.php">NONAMES</a>
        <div class="nav-links">
            <a href="/index.php">Главная</a>
            <a href="/pages/news.php">Новости</a>
            <a href="/pages/team.php">Команда</a>
            <a href="/pages/contact.php">Контакты</a>
            <a href="/pages/calendar.php">Календарь</a>
            <a href="/pages/gallery.php">Галерея</a>
        </div>
        <div>
            <?php if (isset($_SESSION['user_name'])): ?>
                <span>Привет, <?= htmlspecialchars($_SESSION['user_name']); ?>!</span>
                <a href="/auth/logout.php">Log Out</a>
            <?php else: ?>
                <a href="/auth/login.php">Log In</a>
            <?php endif; ?>
        </div>
    </nav>
</header>
