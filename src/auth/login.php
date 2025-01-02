<?php
ob_start();

session_start();
$page_title = 'Ieiet';
$custom_css = 'auth.css';
include '../includes/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    include '../config.php';
    
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];
    
    $sql = "SELECT * FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['userId'] = $user['id'];
        $_SESSION['userName'] = $user['name'];
        $_SESSION['userRole'] = $user['role'];
        
        error_log("User logged in - ID: {$user['id']}, Name: {$user['name']}, Role: {$user['role']}");
        
        ob_end_clean();
        header('Location: /index.php');
        exit();
    } else {
        $error = "Nepareizs e-pasts vai parole";
    }
}
?>

<main>
    <div class="auth-container">
        <h1>Ieiet sistēmā</h1>
        
        <div id="notifications"></div>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-error">
                <span class="material-icons">error</span>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" class="auth-form" id="loginForm" novalidate>
            <div class="form-group">
                <label for="email">E-pasts</label>
                <input type="email" id="email" name="email" 
                    required
                    pattern="[a-z0-9.]+@[a-z0-9.]+\.[a-z]{2,}"
                    data-error="Lūdzu, ievadiet derīgu e-pasta adresi">
                <div class="error-message"></div>
            </div>
            
            <div class="form-group">
                <label for="password">Parole</label>
                <input type="password" id="password" name="password" required>
                <div class="error-message"></div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-success">
                    <span class="material-icons">login</span>
                    <span class="button-text">Ieiet</span>
                </button>
                <div class="auth-links">
                    <p>Nav konta? <a href="/auth/register.php">Reģistrēties</a></p>
                </div>
            </div>
        </form>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('loginForm');
    initLoginFormValidation(form);
});
</script>

<?php include '../includes/footer.php'; ?>
