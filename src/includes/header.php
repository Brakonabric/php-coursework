<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include_once $_SERVER['DOCUMENT_ROOT'] . '/includes/access.php';

if (!isset($_SESSION['user_role'])) {
    $_SESSION['user_role'] = 'guest';
}

setlocale(LC_TIME, 'lv_LV.UTF-8');
?>

<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/assets/css/variables.css">
    <link rel="stylesheet" href="/assets/css/style.css">
    <?php 
    $current_page = basename($_SERVER['PHP_SELF'], '.php');
    $css_file = match($current_page) {
        'index' => '/assets/css/pages/home.css',
        'news' => '/assets/css/pages/news.css',
        'team' => '/assets/css/pages/team.css',
        'contact' => '/assets/css/pages/contact.css',
        'calendar' => '/assets/css/pages/calendar.css',
        'gallery' => '/assets/css/pages/gallery.css',
        'settings' => '/assets/css/pages/settings.css',
        default => null
    };
    
    if ($css_file) {
        echo '<link rel="stylesheet" href="' . $css_file . '">';
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
            <a href="/index.php">Sākums</a>
            <a href="/pages/news.php">Ziņas</a>
            <a href="/pages/team.php">Komanda</a>
            <a href="/pages/contact.php">Kontakti</a>
            <a href="/pages/calendar.php">Kalendārs</a>
            <a href="/pages/gallery.php">Galerija</a>
        </div>
        <div class="user-menu">
            <?php if (isset($_SESSION['user_name'])): ?>
                <div class="dropdown">
                    <button class="dropdown-toggle">
                        <span class="user-name"><?= htmlspecialchars($_SESSION['user_name']); ?></span>
                        <span class="material-icons">menu</span>
                    </button>
                    <div class="dropdown-menu">
                        <a href="/pages/settings.php" class="dropdown-item">
                            <span class="material-icons">settings</span>
                            Iestatījumi
                        </a>
                        <?php if ($_SESSION['user_role'] === 'admin' || $_SESSION['user_role'] === 'trainer'): ?>
                        <a href="/admin/dashboard.php" class="dropdown-item">
                            <span class="material-icons">dashboard</span>
                            Vadības panelis
                        </a>
                        <?php endif; ?>
                        <a href="/auth/logout.php" class="dropdown-item">
                            <span class="material-icons">logout</span>
                            Iziet
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <a href="/auth/login.php">Ieiet</a>
            <?php endif; ?>
        </div>
    </nav>
</header>
