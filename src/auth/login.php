<?php
session_start();
$page_title = 'Login';

// Если пользователь уже авторизован, перенаправляем на главную страницу
if (isset($_SESSION['user_id'])) {
    header('Location: /index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    include '../config.php';

    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE email = '$email'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            // Устанавливаем сессионные переменные
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];

            // Перенаправление на главную страницу после успешного входа
            header('Location: /index.php');
            exit();
        } else {
            $error = "Неверный пароль.";
        }
    } else {
        $error = "Пользователь с таким email не найден.";
    }
}
?>

<?php include '../includes/header.php'; ?>
<h2>Вход</h2>
<form action="login.php" method="POST">
    <label for="email">Email:</label>
    <input type="email" id="email" name="email" required>

    <label for="password">Пароль:</label>
    <input type="password" id="password" name="password" required>

    <button type="submit" name="login">Войти</button>
</form>
<?php if (isset($error)): ?>
    <p style="color: red;"><?= htmlspecialchars($error); ?></p>
<?php endif; ?>
<?php include '../includes/footer.php'; ?>
