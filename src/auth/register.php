<?php
ob_start();
session_start();
$page_title = 'Reģistrācija';
$custom_css = 'auth.css';
include '../includes/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    include '../config.php';
    
    $name = $conn->real_escape_string($_POST['name']);
    $surname = $conn->real_escape_string($_POST['surname']);
    $email = $conn->real_escape_string($_POST['email']);
    $phone = $conn->real_escape_string($_POST['phone']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = 'fan';
    $can_comment = true;
    
    $check_sql = "SELECT id FROM users WHERE email = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param('s', $email);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows > 0) {
        $error = "Šis e-pasts jau ir reģistrēts sistēmā";
    } else {
        $sql = "INSERT INTO users (name, surname, email, phone, password, role, can_comment) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ssssssi', $name, $surname, $email, $phone, $password, $role, $can_comment);
        
        if ($stmt->execute()) {
            $_SESSION['userId'] = $conn->insert_id;
            $_SESSION['userName'] = $name;
            $_SESSION['userRole'] = 'fan';
            
            ob_end_clean();
            header('Location: /index.php');
            exit();
        } else {
            $error = "Reģistrācijas kļūda. Lūdzu, mēģiniet vēlreiz.";
        }
    }
}
?>

<main>
    <div class="auth-container">
        <h1>Reģistrācija</h1>
        
        <div id="notifications"></div>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-error">
                <span class="material-icons">error</span>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" class="auth-form" id="registerForm" novalidate>
            <div class="form-group">
                <label for="name">Vārds</label>
                <input type="text" id="name" name="name" 
                    value="<?= isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '' ?>"
                    required
                    pattern="^[A-Za-zĀāČčĒēĢģĪīĶķĻļŅņŠšŪūŽž]{2,}$"
                    data-error="Vārds nedrīkst saturēt ciparus un speciālos simbolus">
                <div class="error-message"></div>
            </div>

            <div class="form-group">
                <label for="surname">Uzvārds</label>
                <input type="text" id="surname" name="surname" 
                    value="<?= isset($_POST['surname']) ? htmlspecialchars($_POST['surname']) : '' ?>"
                    required
                    pattern="^[A-Za-zĀāČčĒēĢģĪīĶķĻļŅņŠšŪūŽž]{2,}$"
                    data-error="Uzvārds nedrīkst saturēt ciparus un speciālos simbolus">
                <div class="error-message"></div>
            </div>

            <div class="form-group">
                <label for="email">E-pasts</label>
                <input type="email" id="email" name="email" 
                    value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>"
                    required
                    pattern="[a-z0-9.]+@[a-z0-9.]+\.[a-z]{2,}"
                    data-error="Lūdzu, ievadiet derīgu e-pasta adresi">
                <div class="error-message"></div>
            </div>

            <div class="form-group">
                <label for="phone">Tālrunis</label>
                <input type="tel" id="phone" name="phone" 
                    value="<?= isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : '' ?>"
                    pattern="^\+?[0-9]{8,12}$"
                    data-error="Lūdzu, ievadiet derīgu tālruņa numuru">
                <div class="error-message"></div>
            </div>
            
            <div class="form-group">
                <label for="password">Parole</label>
                <input type="password" id="password" name="password" required
                    pattern="^(?=.*[a-zA-Z])(?=.*\d).{8,}$"
                    data-error="Parolei jābūt vismaz 8 rakstzīmes garai un jāsatur vismaz viens cipars un viens burts">
                <div class="error-message"></div>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Apstiprināt paroli</label>
                <input type="password" id="confirm_password" name="confirm_password" required
                    class="confirm-password"
                    data-error="Paroles nesakrīt">
                <div class="error-message"></div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-success">
                    <span class="material-icons">person_add</span>
                    <span class="button-text">Reģistrēties</span>
                </button>
                <div class="auth-links">
                    <p>Jau ir konts? <a href="/auth/login.php">Ieiet</a></p>
                </div>
            </div>
        </form>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('registerForm');
    initFormValidation(form);
});
</script>

<?php include '../includes/footer.php'; ?>