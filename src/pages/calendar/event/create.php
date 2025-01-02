<?php
ob_start();
$page_title = 'Izveidot notikumu';
include '../../../includes/header.php';
include '../../../config.php';
require_once '../../../modules/events/templates.php';

if (!hasAccess('coach', $_SESSION['userRole'])) {
    header('Location: /pages/calendar.php');
    exit('Piekļuve liegta');
}

$templates = getEventTemplates();

$sql = "SELECT id, visible_roles FROM event_types";
$result = $conn->query($sql);
$event_access = [];
while ($row = $result->fetch_assoc()) {
    $event_access[$row['id']] = json_decode($row['visible_roles'], true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $title = $conn->real_escape_string($_POST['title']);
        $description = $conn->real_escape_string($_POST['description']);
        $location = $conn->real_escape_string($_POST['location']);
        
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
        $start_datetime = $start_date . ' ' . $start_time;
        
        if (isset($_POST['has_end_date']) && $_POST['has_end_date'] === '1') {
            $end_date = $_POST['end_date'];
            $end_time = $_POST['end_time'];
            $end_datetime = $end_date . ' ' . $end_time;
        } else {
            $end_datetime = $start_datetime;
        }
        
        $sql = "INSERT INTO events (title, description, event_type, start_date, end_date, location, created_by, event_visibility) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ssssssss', $title, $description, $event_type, $start_datetime, $end_datetime, $location, $_SESSION['userId'], $event_visibility);
        
        if ($stmt->execute()) {
            $event_id = $conn->insert_id;
            ob_end_clean();
            header('Location: /pages/calendar.php');
            exit();
        } else {
            throw new Exception("Kļūda izveidojot notikumu");
        }
    } catch (Exception $e) {
        $error = "Kļūda: " . $e->getMessage();
    }
}
?>

<main>
    <div class="create-post-container">
        <h1>Izveidot notikumu</h1>
        
        <?php if (isset($error)): ?>
            <div class="error-message"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <form method="POST" class="create-post-form">
            <div class="form-group">
                <label for="event_type">Notikuma veids:</label>
                <select id="event_type" name="event_type" required>
                    <?php foreach ($templates as $type_id => $type): ?>
                        <option value="<?= $type_id ?>" 
                                <?= isset($_POST['event_type']) && $_POST['event_type'] === $type_id ? 'selected' : '' ?>>
                            <?= htmlspecialchars($type['label']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div id="event_visibility" class="event-visibility-info"></div>
            </div>
            
            <div class="form-group" id="custom_type_group" style="display: none;">
                <label for="custom_type">Norādiet notikuma veidu:</label>
                <input type="text" id="custom_type" name="custom_type" 
                       value="<?= isset($_POST['custom_type']) ? htmlspecialchars($_POST['custom_type']) : '' ?>">
                
                <label>Notikuma redzamība:</label>
                <div class="role-checkboxes">
                    <label class="checkbox-label"><input type="checkbox" name="visible_roles[]" value="guest"> Viesi</label>
                    <label class="checkbox-label"><input type="checkbox" name="visible_roles[]" value="fan"> Fani</label>
                    <label class="checkbox-label"><input type="checkbox" name="visible_roles[]" value="teamMember"> Komandas biedri</label>
                    <label class="checkbox-label"><input type="checkbox" name="visible_roles[]" value="trainer"> Treneri</label>
                    <label class="checkbox-label"><input type="checkbox" name="visible_roles[]" value="admin"> Administratori</label>
                </div>
            </div>
            
            <div class="form-group">
                <label for="title">Notikuma nosaukums:</label>
                <input type="text" id="title" name="title" required 
                       value="<?= isset($_POST['title']) ? htmlspecialchars($_POST['title']) : '' ?>">
            </div>
            
            <div class="form-group">
                <label for="description">Apraksts:</label>
                <textarea id="description" name="description" required><?= isset($_POST['description']) ? htmlspecialchars($_POST['description']) : '' ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="location">Norises vieta:</label>
                <input type="text" id="location" name="location" required
                       value="<?= isset($_POST['location']) ? htmlspecialchars($_POST['location']) : '' ?>">
            </div>
            
            <div class="date-time-group">
                <div class="form-group">
                    <label for="start_date">Sākuma datums:</label>
                    <input type="date" id="start_date" name="start_date" required 
                           value="<?= isset($_POST['start_date']) ? htmlspecialchars($_POST['start_date']) : date('Y-m-d') ?>">
                </div>
                
                <div class="form-group">
                    <label for="start_time">Sākuma laiks:</label>
                    <input type="time" id="start_time" name="start_time" required 
                           value="<?= isset($_POST['start_time']) ? htmlspecialchars($_POST['start_time']) : '09:00' ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" id="has_end_date" name="has_end_date" value="1" 
                           <?= isset($_POST['has_end_date']) ? 'checked' : '' ?>>
                    Norādīt beigu datumu un laiku
                </label>
            </div>
            
            <div id="end_date_group" style="display: none;">
                <div class="date-time-group">
                    <div class="form-group">
                        <label for="end_date">Beigu datums:</label>
                        <input type="date" id="end_date" name="end_date" 
                               value="<?= isset($_POST['end_date']) ? htmlspecialchars($_POST['end_date']) : '' ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="end_time">Beigu laiks:</label>
                        <input type="time" id="end_time" name="end_time" 
                               value="<?= isset($_POST['end_time']) ? htmlspecialchars($_POST['end_time']) : '' ?>">
                    </div>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-success">
                    <span class="material-icons">add_circle</span>
                    Izveidot notikumu
                </button>
                <a href="/pages/calendar.php" class="link-btn btn-danger">
                    <span class="material-icons">close</span>
                    Atcelt
                </a>
            </div>
        </form>
    </div>
</main>

<script>
function updateEventVisibility(eventType) {
    const eventTypes = <?= json_encode($event_access) ?>;
    const templates = <?= json_encode($templates) ?>;
    const translations = <?= json_encode($templates) ?>;
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
        
        titleInput.placeholder = '';
        descriptionInput.placeholder = '';
        locationInput.placeholder = '';
    } else {
        const roles = eventTypes[eventType];
        const eventData = templates[eventType];
        
        if (eventData) {
            if (eventType === 'public_event') {
                titleInput.value = '';
                descriptionInput.value = '';
                locationInput.value = '';
                
                titleInput.placeholder = eventData.placeholder.title;
                descriptionInput.placeholder = eventData.placeholder.description;
                locationInput.placeholder = eventData.placeholder.location;
            } else {
                titleInput.value = eventData.title;
                descriptionInput.value = eventData.description;
                locationInput.value = eventData.location;
                
                titleInput.placeholder = '';
                descriptionInput.placeholder = '';
                locationInput.placeholder = '';
            }
        }
        
        visibilityDiv.innerHTML = '<p>Redzams: ' + formatVisibilityText(roles) + '</p>';
        customTypeGroup.style.display = 'none';
        roleCheckboxes.forEach(cb => {
            cb.required = false;
            cb.checked = false;
        });
    }
}

function formatVisibilityText(roles) {
    const allRoles = ['guest', 'fan', 'teamMember', 'trainer', 'admin'];
    
    if (roles.length === allRoles.length && allRoles.every(role => roles.includes(role))) {
        return 'Visi';
    }
    
    const registeredRoles = allRoles.filter(role => role !== 'guest');
    if (registeredRoles.every(role => roles.includes(role))) {
        return 'Reģistrētie lietotāji';
    }
    
    const roleLabels = {
        'guest': 'Viesi',
        'fan': 'Fani',
        'teamMember': 'Komandas biedri',
        'trainer': 'Treneri',
        'coach': 'Treneri',
        'admin': 'Administratori'
    };
    
    return roles.map(role => roleLabels[role] || role).join(', ');
}

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

document.addEventListener('DOMContentLoaded', function() {
    const eventType = document.getElementById('event_type').value;
    updateEventVisibility(eventType);
    
    const hasEndDate = document.getElementById('has_end_date');
    if (hasEndDate.checked) {
        document.getElementById('end_date_group').style.display = 'block';
    }
});
</script>

<?php include '../../../includes/footer.php'; ?>
