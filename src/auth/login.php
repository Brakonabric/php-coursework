<?php
// Начинаем буферизацию вывода
ob_start();

session_start();
$page_title = 'Login';
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
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];
        
        // Очищаем буфер перед перенаправлением
        ob_end_clean();
        header('Location: /index.php');
        exit();
    } else {
        $error = "Неверный email или пароль";
    }
}
?>

<main>
    <div class="auth-container">
        <h1>Вход в систему</h1>
        
        <?php if (isset($error)): ?>
            <div class="error-message"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <form method="POST" class="auth-form">
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label for="password">Пароль:</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Войти</button>
            </div>
        </form>
        
        <div class="auth-links">
            <p>Еще нет аккаунта? <a href="/auth/register.php">Зарегистрироваться</a></p>
        </div>
    </div>
</main>

<?php include '../includes/footer.php'; ?>
