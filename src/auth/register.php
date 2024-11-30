<?php
$page_title = 'Registration';
include '../includes/header.php';
?>
<h2>Регистрация</h2>
<form action="register.php" method="POST">
    <label for="name">Имя:</label>
    <input type="text" id="name" name="name" required>

    <label for="email">Email:</label>
    <input type="email" id="email" name="email" required>

    <label for="password">Пароль:</label>
    <input type="password" id="password" name="password" required>

    <button type="submit" name="register">Зарегистрироваться</button>
</form>

<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    include '../config.php';

    $name = $conn->real_escape_string($_POST['name']);
    $email = $conn->real_escape_string($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

    // По умолчанию устанавливаем роль fan
    $role = 'fan';

    $sql = "INSERT INTO users (name, email, password, role) VALUES ('$name', '$email', '$password', '$role')";

    if ($conn->query($sql) === TRUE) {
        echo "<p>Регистрация успешна! <a href='login.php'>Войти</a></p>";
    } else {
        if ($conn->errno === 1062) { // Duplicate entry error
            echo "<p>Этот email уже зарегистрирован.</p>";
        } else {
            echo "<p>Ошибка: " . $conn->error . "</p>";
        }
    }
}
?>
<?php include '../includes/footer.php'; ?>