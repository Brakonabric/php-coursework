<?php
session_start();
require_once '/var/www/html/config.php';
require_once '/var/www/html/includes/access.php';

if (!isset($_SESSION['userName']) || (!hasAccess('admin', $_SESSION['userRole']) && !hasAccess('coach', $_SESSION['userRole']))) {
    header('Location: /auth/login.php');
    exit;
}

$isAdmin = hasAccess('admin', $_SESSION['userRole']);

require_once '/var/www/html/includes/header.php';
?>

<div class="dashboard">
    <h1>Vadības panelis</h1>
    
    <div class="dashboard-content">
        <div class="sidebar">
            <div class="nav-pills">
                <a href="#" class="active" data-tab="users">Lietotāji</a>
                <a href="#" data-tab="comments">Komentāri</a>
                <a href="#" data-tab="news">Ziņas</a>
                <?php if ($isAdmin): ?>
                    <a href="#" data-tab="database">Datubāze</a>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="main-content">
            <div class="tab-content">
                <div id="users" class="tab-pane active">
                    <div class="card">
                        <div class="card-header">
                            <h5>Lietotāju pārvaldība</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Lietotājvārds</th>
                                            <th>E-pasts</th>
                                            <th>Loma</th>
                                            <th>Komentēšana</th>
                                            <th>Darbības</th>
                                        </tr>
                                    </thead>
                                    <tbody id="usersTableBody"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div id="comments" class="tab-pane">
                    <div class="card">
                        <div class="card-header">
                            <h5>Komentāru pārvaldība</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Lietotājs</th>
                                            <th>Komentārs</th>
                                            <th>Datums</th>
                                            <th>Darbības</th>
                                        </tr>
                                    </thead>
                                    <tbody id="commentsTableBody"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="news" class="tab-pane">
                    <div class="card">
                        <div class="card-header">
                            <h5>Ziņu pārvaldība</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Nosaukums</th>
                                            <th>Datums</th>
                                            <th>Attēli</th>
                                            <th>Komentāri</th>
                                            <th>Darbības</th>
                                        </tr>
                                    </thead>
                                    <tbody id="newsTableBody"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php if ($isAdmin): ?>
                    <div id="database" class="tab-pane">
                        <div class="card">
                            <div class="card-header">
                                <h5>Datubāzes pārvaldība</h5>
                            </div>
                            <div class="card-body">
                                <div class="database-actions">
                                    <button class="btn btn-info" onclick="checkDatabase()">
                                        <span class="material-icons">analytics</span>
                                        Pārbaudīt stāvokli
                                    </button>
                                    <button class="btn btn-warning" onclick="resetDatabase()">
                                        <span class="material-icons">restart_alt</span>
                                        Dzēst datus
                                    </button>
                                </div>
                                <div id="databaseStatus"></div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
    const isAdmin = <?php echo json_encode($isAdmin); ?>;
    console.log('Dashboard initialization started');
    console.log('isAdmin:', isAdmin);
</script>

<script src="/assets/js/admin/dashboard.js"></script>

<?php require_once '/var/www/html/includes/footer.php'; ?> 