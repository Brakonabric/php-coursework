<?php
// Начинаем буферизацию вывода
ob_start();

session_start();
$page_title = 'Регистрация';
include '../includes/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    include '../config.php';
    
    $name = $conn->real_escape_string($_POST['name']);
    $email = $conn->real_escape_string($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = 'fan';
    
    // Сначала проверяем, существует ли уже такой email
    $check_sql = "SELECT id FROM users WHERE email = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param('s', $email);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows > 0) {
        $error = "Этот email уже зарегистрирован в системе";
    } else {
        // Если email свободен, выполняем регистрацию
        $sql = "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ssss', $name, $email, $password, $role);
        
        if ($stmt->execute()) {
            $_SESSION['user_id'] = $conn->insert_id;
            $_SESSION['user_name'] = $name;
            $_SESSION['user_role'] = $role;
            
            // Очищаем буфер перед перенаправлением
            ob_end_clean();
            header('Location: /index.php');
            exit();
        } else {
            $error = "Ошибка при регистрации. Пожалуйста, попробуйте позже.";
        }
    }
}
?>

<main>
    <div class="auth-container">
        <h1>Регистрация</h1>
        
        <?php if (isset($error)): ?>
            <div class="error-message"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <form method="POST" class="auth-form">
            <div class="form-group">
                <label for="name">Имя:</label>
                <input type="text" id="name" name="name" required 
                       value="<?= isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '' ?>">
            </div>
            
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required
                       value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
            </div>
            
            <div class="form-group">
                <label for="password">Пароль:</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Зарегистрироваться</button>
            </div>
        </form>
        
        <div class="auth-links">
            <p>Уже есть аккаунт? <a href="/auth/login.php">Войти</a></p>
        </div>
    </div>
</main>

<?php include '../includes/footer.php'; ?>