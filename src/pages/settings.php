<?php
$page_title = "Iestatījumi";
$custom_css = 'auth.css';
include '../includes/header.php';
include '../config.php';

if (!isset($_SESSION['userId'])) {
    header('Location: /auth/login.php');
    exit();
}

$userId = $_SESSION['userId'];
$success_message = '';
$error_message = '';

$stmt = $conn->prepare("SELECT name, surname, email, phone FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

$validationErrors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $surname = trim($_POST['surname']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    try {
        if (!empty($new_password)) {
            $pwd_stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
            $pwd_stmt->bind_param("i", $userId);
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

        $conn->begin_transaction();

        $update_stmt = $conn->prepare("UPDATE users SET name = ?, surname = ?, email = ?, phone = ? WHERE id = ?");
        $update_stmt->bind_param("ssssi", $name, $surname, $email, $phone, $userId);
        $update_stmt->execute();

        if (!empty($new_password)) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $pwd_update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $pwd_update_stmt->bind_param("si", $hashed_password, $userId);
            $pwd_update_stmt->execute();
        }

        $conn->commit();
        $_SESSION['userName'] = $name;
        $success_message = "Iestatījumi veiksmīgi atjaunināti";
        
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
                    pattern="^[A-Za-zĀāČčĒēĢģĪīĶķĻļŅņŠšŪūŽž]{2,}$"
                    data-error="Vārds nedrīkst saturēt ciparus un speciālos simbolus">
                <div class="error-message"></div>
            </div>

            <div class="form-group">
                <label for="surname">Uzvārds</label>
                <input type="text" id="surname" name="surname" 
                    value="<?= htmlspecialchars($user['surname'] ?? '') ?>" 
                    required readonly
                    pattern="^[A-Za-zĀāČčĒēĢģĪīĶķĻļŅņŠšŪūŽž]{2,}$"
                    data-error="Uzvārds nedrīkst saturēt ciparus un speciālos simbolus">
                <div class="error-message"></div>
            </div>

            <div class="form-group">
                <label for="email">E-pasts</label>
                <input type="email" id="email" name="email" 
                    value="<?= htmlspecialchars($user['email'] ?? '') ?>" 
                    required readonly
                    pattern="[a-z0-9.]+@[a-z0-9.]+\.[a-z]{2,}"
                    data-error="Lūdzu, ievadiet derīgu e-pasta adresi (piemēram: lietotajs@domena.com)">
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
                <h1>Mainīt paroli</h1>
                <div class="form-group">
                    <label for="current_password">Pašreizējā parole</label>
                    <input type="password" id="current_password" name="current_password" readonly>
                    <div class="error-message"></div>
                </div>

                <div class="form-group">
                    <label for="new_password">Jaunā parole</label>
                    <input type="password" id="new_password" name="new_password" 
                        readonly
                        pattern="^(?=.*[a-zA-Z])(?=.*\d).{8,}$"
                        data-error="Parolei jābūt vismaz 8 rakstzīmes garai un jāsatur vismaz viens cipars un viens burts">
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
                <button type="submit" class="btn btn-success" id="saveButton" style="display: none;">
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
    let isEditing = false;
    let originalValues = {};

    inputs.forEach(input => {
        originalValues[input.name] = input.value;
    });

    editButton.addEventListener('click', function() {
        isEditing = !isEditing;
        
        if (isEditing) {
            editButton.innerHTML = '<span class="material-icons">close</span><span class="button-text">Atcelt</span>';
            editButton.classList.add('btn-danger');
            saveButton.style.display = 'flex';
            inputs.forEach(input => {
                input.readOnly = false;
            });
        } else {
            editButton.innerHTML = '<span class="material-icons">edit</span><span class="button-text">Rediģēt</span>';
            editButton.classList.remove('btn-danger');
            saveButton.style.display = 'none';
            inputs.forEach(input => {
                input.readOnly = true;
                input.value = originalValues[input.name] || '';
                clearFieldError(input);
            });
        }
    });

    initFormValidation(form);
});
</script>

<?php include '../includes/footer.php'; ?>