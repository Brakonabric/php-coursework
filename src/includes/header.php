<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include_once $_SERVER['DOCUMENT_ROOT'] . '/includes/access.php';

if (!isset($_SESSION['userRole'])) {
    $_SESSION['userRole'] = 'guest';
}

setlocale(LC_TIME, 'lv_LV.UTF-8');

$current_page = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));
?>

<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/assets/css/variables.css">
    <link rel="stylesheet" href="/assets/css/style.css">
    <?php
    if (isset($custom_css)) {
        echo '<link rel="stylesheet" href="/assets/css/pages/' . $custom_css . '">';
    } else {
        $current_page = basename($_SERVER['PHP_SELF'], '.php');
        $css_file = match($current_page) {
            'index' => '/assets/css/pages/home.css',
            'news', 'post', 'create', 'edit' => '/assets/css/pages/news.css',
            'team' => '/assets/css/pages/team.css',
            'contact' => '/assets/css/pages/contact.css',
            'calendar' => '/assets/css/pages/calendar.css',
            'gallery' => '/assets/css/pages/gallery.css',
            'settings', 'login', 'register' => '/assets/css/pages/auth.css',
            'dashboard' => '/assets/css/pages/admin/dashboard.css',
            default => null
        };
        
        if ($css_file) {
            echo '<link rel="stylesheet" href="' . $css_file . '">';
        }
    }
    ?>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    
    <?php
    if (in_array($current_page, ['gallery', 'post']) || $current_page === 'index'): ?>
        <link rel="stylesheet" href="/assets/css/components/imageViewer.css">
        <script src="/assets/js/imageViewer.js" defer></script>
    <?php endif; ?>
    
    <?php
    if (in_array($current_page, ['register', 'login', 'settings'])): ?>
        <script src="/assets/js/validation.js" defer></script>
    <?php endif; ?>
    
    <title><?= isset($page_title) ? htmlspecialchars($page_title) : 'Nonames'; ?></title>
    <link rel="icon" href="/assets/images/common/favicon.ico" type="image/x-icon">
</head>
<body data-user-role="<?= isset($_SESSION['userRole']) ? htmlspecialchars($_SESSION['userRole']) : 'guest' ?>">
    <header>
        <nav>
            <a href="/index.php">NONAMES</a>
            <div class="nav-links">
                <a href="/">Sākums</a>
                <a href="/pages/news.php">Ziņas</a>
                <a href="/pages/gallery.php">Galerija</a>
                <a href="/pages/calendar.php">Kalendārs</a>
                <a href="/pages/contact.php">Kontakti</a>
            </div>
            <div class="user-menu">
                <?php if (isset($_SESSION['userName'])): ?>
                    <div class="dropdown">
                        <button class="dropdown-toggle">
                            <span class="user-name"><?= htmlspecialchars($_SESSION['userName']); ?></span>
                            <span class="material-icons">menu</span>
                        </button>
                        <div class="dropdown-menu">
                            <a href="/pages/settings.php" class="dropdown-item">
                                <span class="material-icons">settings</span>
                                Iestatījumi
                            </a>
                            <?php 
                            error_log("Current user role: " . $_SESSION['userRole']);
                            if (hasAccess('coach', $_SESSION['userRole'])): 
                            ?>
                                <a href="/pages/admin/dashboard.php" class="dropdown-item">
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
