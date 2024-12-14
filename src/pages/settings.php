<?php
$page_title = "Iestatījumi";
include '../includes/header.php';
include '../config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /auth/login.php');
    exit;
}


$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Получаем данные пользователя
$stmt = $conn->prepare("SELECT name, surname, email, phone FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Обработка формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $surname = trim($_POST['surname']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    try {
        // Проверяем текущий пароль только если пользователь хочет изменить пароль
        if (!empty($new_password)) {
            $pwd_stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
            $pwd_stmt->bind_param("i", $user_id);
            $pwd_stmt->execute();
            $pwd_result = $pwd_stmt->get_result();
            $stored_password = $pwd_result->fetch_row()[0];

            if (!password_verify($current_password, $stored_password)) {
                throw new Exception("Nepareiza pašreizējā parole");
            }

            if ($new_password !== $confirm_password) {
                throw new Exception("Jaunās paroles nesakrīt");
            }

            if (strlen($new_password) < 8) {
                throw new Exception("Parolei jābūt vismaz 8 rakstzīmes garai");
            }
        }

        // Начинаем транзакцию
        $conn->begin_transaction();

        // Обновляем основные данные
        $update_stmt = $conn->prepare("UPDATE users SET name = ?, surname = ?, email = ?, phone = ? WHERE id = ?");
        $update_stmt->bind_param("ssssi", $name, $surname, $email, $phone, $user_id);
        $update_stmt->execute();

        // Обновляем пароль, если указан новый
        if (!empty($new_password)) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $pwd_update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $pwd_update_stmt->bind_param("si", $hashed_password, $user_id);
            $pwd_update_stmt->execute();
        }

        $conn->commit();
        $_SESSION['user_name'] = $name;
        $success_message = "Iestatījumi veiksmīgi atjaunināti";
        
        // Обновляем данные пользователя для отображения в форме
        $user['name'] = $name;
        $user['surname'] = $surname;
        $user['email'] = $email;
        $user['phone'] = $phone;

    } catch (Exception $e) {
        $conn->rollback();
        $error_message = $e->getMessage();
    }
}
?>

<main class="settings-page">
    <div class="settings-container">
        <h1>Profila iestatījumi</h1>
        
        <div id="notifications"></div>
        
        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <span class="material-icons">check_circle</span>
                <?= htmlspecialchars($success_message) ?>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-error">
                <span class="material-icons">error</span>
                <?= htmlspecialchars($error_message) ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="settings-form" id="settingsForm" novalidate>
            <div class="form-group">
                <label for="name">Vārds</label>
                <input type="text" id="name" name="name" 
                    value="<?= htmlspecialchars($user['name'] ?? '') ?>" 
                    required readonly
                    pattern="[A-Za-zĀ-ž\s]{2,}"
                    data-error="Vārdam jābūt vismaz 2 rakstzīmes garam un jāsatur tikai burti">
                <div class="error-message"></div>
            </div>

            <div class="form-group">
                <label for="surname">Uzvārds</label>
                <input type="text" id="surname" name="surname" 
                    value="<?= htmlspecialchars($user['surname'] ?? '') ?>" 
                    required readonly
                    pattern="[A-Za-zĀ-ž\s]{2,}"
                    data-error="Uzvārdam jābūt vismaz 2 rakstzīmes garam un jāsatur tikai burti">
                <div class="error-message"></div>
            </div>

            <div class="form-group">
                <label for="email">E-pasts</label>
                <input type="email" id="email" name="email" 
                    value="<?= htmlspecialchars($user['email'] ?? '') ?>" 
                    required readonly
                    data-error="Lūdzu, ievadiet derīgu e-pasta adresi">
                <div class="error-message"></div>
            </div>

            <div class="form-group">
                <label for="phone">Tālrunis</label>
                <input type="tel" id="phone" name="phone" 
                    value="<?= htmlspecialchars($user['phone'] ?? '') ?>" 
                    readonly
                    pattern="^\+?[0-9]{8,12}$"
                    data-error="Lūdzu, ievadiet derīgu tālruņa numuru">
                <div class="error-message"></div>
            </div>

            <div class="password-section">
                <h2>Mainīt paroli</h2>
                <div class="form-group">
                    <label for="current_password">Pašreizējā parole</label>
                    <input type="password" id="current_password" name="current_password" readonly>
                    <div class="error-message"></div>
                </div>

                <div class="form-group">
                    <label for="new_password">Jaunā parole</label>
                    <input type="password" id="new_password" name="new_password" 
                        readonly
                        pattern="^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,}$"
                        data-error="Parolei jābūt vismaz 8 rakstzīmes garai un jāsatur vismaz viens cipars">
                    <div class="error-message"></div>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Apstiprināt jauno paroli</label>
                    <input type="password" id="confirm_password" name="confirm_password" readonly
                        data-error="Paroles nesakrīt">
                    <div class="error-message"></div>
                </div>
            </div>

            <div class="form-actions">
                <button type="button" id="editButton" class="btn btn-secondary">
                    <span class="material-icons">edit</span>
                    <span class="button-text">Rediģēt</span>
                </button>
                <button type="submit" class="btn btn-primary" id="saveButton" style="display: none;">
                    <span class="material-icons">save</span>
                    <span class="button-text">Saglabāt izmaiņas</span>
                </button>
            </div>
        </form>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('settingsForm');
    const editButton = document.getElementById('editButton');
    const saveButton = document.getElementById('saveButton');
    const inputs = form.querySelectorAll('input');
    const notifications = document.getElementById('notifications');
    let isEditing = false;
    let originalValues = {};

    // Сохраняем оригинальные значения
    inputs.forEach(input => {
        originalValues[input.name] = input.value;
    });

    // Функция для показа уведомления
    function showNotification(message, type = 'error') {
        const notification = document.createElement('div');
        notification.className = `alert alert-${type}`;
        notification.innerHTML = `
            <span class="material-icons">${type === 'success' ? 'check_circle' : 'error'}</span>
            ${message}
        `;
        notifications.appendChild(notification);
        
        // Удаляем уведомление через 5 секунд
        setTimeout(() => {
            notification.remove();
        }, 5000);
    }

    // Функция валидации поля
    function validateField(input) {
        const errorDiv = input.nextElementSibling;
        let isValid = true;

        // Очищаем предыдущую ошибку
        errorDiv.textContent = '';
        input.classList.remove('invalid');

        if (input.hasAttribute('required') && !input.value) {
            errorDiv.textContent = 'Šis lauks ir obligāts';
            input.classList.add('invalid');
            isValid = false;
        } else if (input.pattern && input.value) {
            const regex = new RegExp(input.pattern);
            if (!regex.test(input.value)) {
                errorDiv.textContent = input.dataset.error;
                input.classList.add('invalid');
                isValid = false;
            }
        } else if (input.type === 'email' && input.value) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(input.value)) {
                errorDiv.textContent = input.dataset.error;
                input.classList.add('invalid');
                isValid = false;
            }
        } else if (input.id === 'confirm_password' && input.value) {
            const newPassword = document.getElementById('new_password');
            if (input.value !== newPassword.value) {
                errorDiv.textContent = input.dataset.error;
                input.classList.add('invalid');
                isValid = false;
            }
        }

        return isValid;
    }

    // Валидация при вводе
    inputs.forEach(input => {
        input.addEventListener('input', () => {
            if (!input.readOnly) {
                validateField(input);
            }
        });
    });

    editButton.addEventListener('click', function() {
        isEditing = !isEditing;
        
        if (isEditing) {
            // Включаем режим редактирования
            editButton.innerHTML = '<span class="material-icons">close</span><span class="button-text">Atcelt</span>';
            editButton.classList.add('btn-danger');
            saveButton.style.display = 'flex';
            inputs.forEach(input => {
                input.readOnly = false;
            });
        } else {
            // Отменяем редактирование и возвращаем оригинальные значения
            editButton.innerHTML = '<span class="material-icons">edit</span><span class="button-text">Rediģēt</span>';
            editButton.classList.remove('btn-danger');
            saveButton.style.display = 'none';
            inputs.forEach(input => {
                input.readOnly = true;
                if (originalValues[input.name]) {
                    input.value = originalValues[input.name];
                } else {
                    input.value = '';
                }
                // Очищаем ошибки
                input.classList.remove('invalid');
                const errorDiv = input.nextElementSibling;
                if (errorDiv && errorDiv.className === 'error-message') {
                    errorDiv.textContent = '';
                }
            });
        }
    });

    // Обработка отправки формы
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        let isValid = true;
        const activeInputs = Array.from(inputs).filter(input => !input.readOnly);
        
        // Валидируем только активные поля
        activeInputs.forEach(input => {
            if (!validateField(input)) {
                isValid = false;
            }
        });

        // Проверяем пароли
        const newPassword = document.getElementById('new_password');
        const confirmPassword = document.getElementById('confirm_password');
        const currentPassword = document.getElementById('current_password');

        if (newPassword.value || confirmPassword.value || currentPassword.value) {
            if (!currentPassword.value) {
                showNotification('Lai mainītu paroli, jāievada pašreizējā parole');
                isValid = false;
            }
            if (newPassword.value !== confirmPassword.value) {
                showNotification('Jaunās paroles nesakrīt');
                isValid = false;
            }
        }

        if (isValid) {
            this.submit();
        }
    });
});
</script>

<?php include '../includes/footer.php'; ?>