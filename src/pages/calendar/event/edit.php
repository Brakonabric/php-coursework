<?php
ob_start();
$page_title = 'Редактирование события';
include '../../../includes/header.php';
include '../../../config.php';
require_once '../../../modules/events/templates.php';

// Проверка прав доступа
if (!hasAccess('coach', $_SESSION['user_role'])) {
    header('Location: /404.php');
    exit();
}

// Получение ID события
if (!isset($_GET['id'])) {
    header('Location: /404.php');
    exit();
}

$event_id = (int)$_GET['id'];

// Получение информации о событии
$sql = "SELECT * FROM events WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $event_id);
$stmt->execute();
$result = $stmt->get_result();
$event = $result->fetch_assoc();

if (!$event) {
    header('Location: /404.php');
    exit();
}

// Получение типов событий и их шаблонов
$templates = getEventTemplates();

// Получение информации о правах доступа для типов событий
$sql = "SELECT id, visible_roles FROM event_types";
$result = $conn->query($sql);
$event_access = [];
while ($row = $result->fetch_assoc()) {
    $event_access[$row['id']] = json_decode($row['visible_roles'], true);
}

// Обработка удаления события
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_event'])) {
    try {
        // Начинаем транзакцию
        $conn->begin_transaction();
        
        // Удаляем событие
        $sql = "DELETE FROM events WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $event_id);
        
        if ($stmt->execute()) {
            $conn->commit();
            ob_end_clean();
            header('Location: /pages/calendar.php');
            exit();
        } else {
            throw new Exception("Ошибка при удалении события");
        }
    } catch (Exception $e) {
        $conn->rollback();
        $error = "Ошибка: " . $e->getMessage();
    }
}

// Обработка отправки формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $title = $conn->real_escape_string($_POST['title']);
        $description = $conn->real_escape_string($_POST['description']);
        $location = $conn->real_escape_string($_POST['location']);
        
        // Обработка типа события и видимости
        if ($_POST['event_type'] === 'other') {
            $event_type = $conn->real_escape_string($_POST['custom_type']);
            $visible_roles = isset($_POST['visible_roles']) ? $_POST['visible_roles'] : [];
            $event_visibility = json_encode($visible_roles);
        } else {
            $event_type = $conn->real_escape_string($_POST['event_type']);
            $event_visibility = json_encode($event_access[$event_type]);
        }
        
        $start_date = $_POST['start_date'];
        $start_time = $_POST['start_time'];
        
        // Формируем полную дату и время начала
        $start_datetime = $start_date . ' ' . $start_time;
        
        // Обрабатываем дату и время окончания только если включен чекбокс
        if (isset($_POST['has_end_date']) && $_POST['has_end_date'] === '1') {
            $end_date = $_POST['end_date'];
            $end_time = $_POST['end_time'];
            $end_datetime = $end_date . ' ' . $end_time;
        } else {
            $end_datetime = $start_datetime;
        }
        
        $sql = "UPDATE events SET title = ?, description = ?, event_type = ?, start_date = ?, end_date = ?, location = ?, event_visibility = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('sssssssi', $title, $description, $event_type, $start_datetime, $end_datetime, $location, $event_visibility, $event_id);
        
        if ($stmt->execute()) {
            ob_end_clean();
            header('Location: /pages/calendar.php');
            exit();
        } else {
            throw new Exception("Ошибка при обновлении события");
        }
    } catch (Exception $e) {
        $error = "Ошибка: " . $e->getMessage();
    }
}

// Разбиваем даты на компоненты для формы
$start_date = date('Y-m-d', strtotime($event['start_date']));
$start_time = date('H:i', strtotime($event['start_date']));
$end_date = date('Y-m-d', strtotime($event['end_date']));
$end_time = date('H:i', strtotime($event['end_date']));
$has_end_date = $event['start_date'] !== $event['end_date'];
?>

<main>
    <div class="edit-event-container">
        <h1>Редактирование события</h1>
        
        <?php if (isset($error)): ?>
            <div class="error-message"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <form method="POST" class="edit-event-form">
            <div class="form-group">
                <label for="event_type">Тип события:</label>
                <select id="event_type" name="event_type" required>
                    <?php foreach ($templates as $type_id => $type): ?>
                        <option value="<?= $type_id ?>" 
                                <?= $event['event_type'] === $type_id ? 'selected' : '' ?>>
                            <?= htmlspecialchars($type['label']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div id="event_visibility" class="event-visibility-info"></div>
            </div>
            
            <div class="form-group" id="custom_type_group" style="display: none;">
                <label for="custom_type">Укажите тип события:</label>
                <input type="text" id="custom_type" name="custom_type" 
                       value="<?= $event['event_type'] ?>">
                
                <label>Видимость события:</label>
                <div class="role-checkboxes">
                    <?php
                    $current_visibility = json_decode($event['event_visibility'], true);
                    $roles = ['guest', 'fan', 'team_member', 'coach', 'admin'];
                    foreach ($roles as $role):
                        $checked = in_array($role, $current_visibility) ? 'checked' : '';
                    ?>
                        <label>
                            <input type="checkbox" name="visible_roles[]" value="<?= $role ?>" <?= $checked ?>>
                            <?= ucfirst($role) ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="form-group">
                <label for="title">Название события:</label>
                <input type="text" id="title" name="title" required 
                       value="<?= htmlspecialchars($event['title']) ?>">
            </div>
            
            <div class="form-group">
                <label for="description">Описание:</label>
                <textarea id="description" name="description" required><?= htmlspecialchars($event['description']) ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="location">Место проведения:</label>
                <input type="text" id="location" name="location" required
                       value="<?= htmlspecialchars($event['location']) ?>">
            </div>
            
            <div class="form-group">
                <label for="start_date">Дата начала:</label>
                <input type="date" id="start_date" name="start_date" required 
                       value="<?= $start_date ?>">
            </div>
            
            <div class="form-group">
                <label for="start_time">Время начала:</label>
                <input type="time" id="start_time" name="start_time" required 
                       value="<?= $start_time ?>">
            </div>
            
            <div class="form-group">
                <label>
                    <input type="checkbox" id="has_end_date" name="has_end_date" value="1" 
                           <?= $has_end_date ? 'checked' : '' ?>>
                    Указать дату и время окончания
                </label>
            </div>
            
            <div id="end_date_group" style="display: <?= $has_end_date ? 'block' : 'none' ?>;">
                <div class="form-group">
                    <label for="end_date">Дата окончания:</label>
                    <input type="date" id="end_date" name="end_date" 
                           value="<?= $end_date ?>">
                </div>
                
                <div class="form-group">
                    <label for="end_time">Время окончания:</label>
                    <input type="time" id="end_time" name="end_time" 
                           value="<?= $end_time ?>">
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Сохранить изменения</button>
                <a href="/pages/calendar.php" class="btn btn-secondary">Отмена</a>
            </div>
        </form>

        <!-- Форма удаления события -->
        <form method="POST" class="delete-event-form" onsubmit="return confirm('Вы уверены, что хотите удалить это событие?');">
            <button type="submit" name="delete_event" class="btn btn-danger">Удалить событие</button>
        </form>
    </div>
</main>

<style>
/* Добавляем стили для формы удаления */
.delete-event-form {
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid #ddd;
}

.btn-danger {
    background-color: #dc3545;
    color: white;
}

.btn-danger:hover {
    background-color: #c82333;
}
</style>

<script>
// Функция для отображения видимости события
function updateEventVisibility(eventType) {
    const eventTypes = <?= json_encode($event_access) ?>;
    const templates = <?= json_encode($templates) ?>;
    const visibilityDiv = document.getElementById('event_visibility');
    const customTypeGroup = document.getElementById('custom_type_group');
    const roleCheckboxes = document.querySelectorAll('input[name="visible_roles[]"]');
    const titleInput = document.getElementById('title');
    const descriptionInput = document.getElementById('description');
    const locationInput = document.getElementById('location');
    
    if (eventType === 'other') {
        visibilityDiv.innerHTML = '';
        customTypeGroup.style.display = 'block';
        roleCheckboxes.forEach(cb => cb.required = true);
    } else {
        const roles = eventTypes[eventType];
        const eventData = templates[eventType];
        
        if (eventData) {
            if (eventType === 'public_event' && !titleInput.value) {
                // Для публичных событий используем плейсхолдеры только если поля пустые
                titleInput.placeholder = eventData.placeholder.title;
                descriptionInput.placeholder = eventData.placeholder.description;
                locationInput.placeholder = eventData.placeholder.location;
            }
        }
        
        // Отображаем информацию о видимости
        visibilityDiv.innerHTML = '<p>Видно для: ' + formatVisibilityText(roles) + '</p>';
        customTypeGroup.style.display = 'none';
        roleCheckboxes.forEach(cb => {
            cb.required = false;
            cb.checked = false;
        });
    }
}

function formatVisibilityText(roles) {
    const allRoles = ['guest', 'fan', 'team_member', 'coach', 'admin'];
    
    // Если включены все роли
    if (roles.length === allRoles.length && allRoles.every(role => roles.includes(role))) {
        return 'Все';
    }
    
    // Если включены все роли кроме гостя
    const registeredRoles = allRoles.filter(role => role !== 'guest');
    if (registeredRoles.every(role => roles.includes(role))) {
        return 'Зарегистрированные пользователи';
    }
    
    // В остальных случаях выводим список ролей
    const roleLabels = {
        'guest': 'Гости',
        'fan': 'Болельщики',
        'team_member': 'Члены команды',
        'coach': 'Тренеры',
        'admin': 'Администраторы'
    };
    
    return roles.map(role => roleLabels[role] || role).join(', ');
}

// Обработчики событий
document.getElementById('event_type').addEventListener('change', function() {
    updateEventVisibility(this.value);
});

document.getElementById('has_end_date').addEventListener('change', function() {
    const endDateGroup = document.getElementById('end_date_group');
    endDateGroup.style.display = this.checked ? 'block' : 'none';
    
    const endDateInput = document.getElementById('end_date');
    const endTimeInput = document.getElementById('end_time');
    
    if (this.checked) {
        endDateInput.required = true;
        endTimeInput.required = true;
        
        // Если дата окончания не установлена, устанавливаем её равной дате начала
        if (!endDateInput.value) {
            endDateInput.value = document.getElementById('start_date').value;
        }
        if (!endTimeInput.value) {
            endTimeInput.value = document.getElementById('start_time').value;
        }
    } else {
        endDateInput.required = false;
        endTimeInput.required = false;
    }
});

// Инициализация при загрузке страницы
document.addEventListener('DOMContentLoaded', function() {
    const eventType = document.getElementById('event_type').value;
    updateEventVisibility(eventType);
});
</script>

<?php include '../../../includes/footer.php'; ?> 