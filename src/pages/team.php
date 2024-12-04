<?php
$page_title = 'Команда';
include '../includes/header.php';
require_once '../config.php';

// Проверка прав доступа для управления игроками
$can_manage = isset($_SESSION['user_role']) && ($_SESSION['user_role'] === 'trainer' || $_SESSION['user_role'] === 'admin');

try {
    // Получение списка игроков с их статистикой
    $query = "SELECT p.*, 
        COALESCE((SELECT COUNT(*) FROM player_goals WHERE player_id = p.id AND YEAR(scored_at) = YEAR(CURRENT_DATE)), 0) as goals_count,
        COALESCE((SELECT COUNT(*) FROM player_penalties WHERE player_id = p.id AND YEAR(received_at) = YEAR(CURRENT_DATE)), 0) as penalties_count
        FROM players p
        ORDER BY p.number";
    
    $result = $conn->query($query);
    
    if (!$result) {
        throw new Exception("Error executing query: " . $conn->error);
    }
} catch (Exception $e) {
    error_log("Error in team.php: " . $e->getMessage());
    $result = null;
}
?>

<main class="team-page">
    <?php if ($can_manage): ?>
    <div class="management-panel">
        <button class="btn btn--primary" onclick="openPlayerModal()">
            <span class="material-icons">person_add</span>
            Добавить игрока
        </button>
    </div>
    <?php endif; ?>

    <div class="players-grid">
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($player = $result->fetch_assoc()): ?>
                <div class="player-card" data-player-id="<?= $player['id'] ?>">
                    <div class="player-card__header">
                        <span class="player-number"><?= htmlspecialchars($player['number']) ?></span>
                        <span class="player-position"><?= htmlspecialchars($player['position']) ?></span>
                    </div>
                    <div class="player-card__image">
                        <img src="<?= htmlspecialchars($player['photo_url'] ?? '/assets/images/player-placeholder.png') ?>" 
                             alt="<?= htmlspecialchars($player['name']) ?>">
                    </div>
                    <div class="player-card__info">
                        <h3 class="player-name"><?= htmlspecialchars($player['name']) ?></h3>
                        <div class="player-stats">
                            <div class="stat">
                                <span class="material-icons">height</span>
                                <span><?= htmlspecialchars($player['height']) ?> см</span>
                            </div>
                            <div class="stat">
                                <span class="material-icons">monitor_weight</span>
                                <span><?= htmlspecialchars($player['weight']) ?> кг</span>
                            </div>
                            <div class="stat">
                                <span class="material-icons">cake</span>
                                <span><?= calculateAge($player['birth_date']) ?> лет</span>
                            </div>
                        </div>
                        <div class="player-season-stats">
                            <div class="stat">
                                <span class="material-icons">sports_score</span>
                                <span><?= $player['goals_count'] ?> голов</span>
                            </div>
                            <div class="stat">
                                <span class="material-icons">warning</span>
                                <span><?= $player['penalties_count'] ?> штрафов</span>
                            </div>
                            <div class="stat">
                                <span class="material-icons">gps_fixed</span>
                                <span><?= htmlspecialchars($player['accuracy']) ?>% точность</span>
                            </div>
                        </div>
                        <?php if ($can_manage): ?>
                        <div class="player-actions">
                            <button onclick="editPlayer(<?= $player['id'] ?>)" class="btn btn--icon">
                                <span class="material-icons">edit</span>
                            </button>
                            <button onclick="deletePlayer(<?= $player['id'] ?>)" class="btn btn--icon btn--danger">
                                <span class="material-icons">delete</span>
                            </button>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="no-content">
                <span class="material-icons">sports_soccer</span>
                <p>В команде пока нет игроков</p>
            </div>
        <?php endif; ?>
    </div>
</main>

<!-- Модальное окно для добавления/редактирования игрока -->
<?php if ($can_manage): ?>
<div id="playerModal" class="modal">
    <div class="modal-content">
        <h2 id="modalTitle">Добавить игрока</h2>
        <form id="playerForm" onsubmit="savePlayer(event)" enctype="multipart/form-data">
            <input type="hidden" name="id" value="">
            
            <div class="form-group">
                <label for="number">Номер</label>
                <input type="number" id="number" name="number" required min="1" max="99">
            </div>
            
            <div class="form-group">
                <label for="name">Имя</label>
                <input type="text" id="name" name="name" required>
            </div>
            
            <div class="form-group">
                <label for="position">Позиция</label>
                <select id="position" name="position" required>
                    <option value="GK">Вратарь (GK)</option>
                    <option value="DF">Защитник (DF)</option>
                    <option value="MF">Полузащитник (MF)</option>
                    <option value="FW">Нападающий (FW)</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="height">Рост (см)</label>
                <input type="number" id="height" name="height" required min="150" max="220">
            </div>
            
            <div class="form-group">
                <label for="weight">Вес (кг)</label>
                <input type="number" id="weight" name="weight" required min="50" max="120">
            </div>
            
            <div class="form-group">
                <label for="birth_date">Дата рождения</label>
                <input type="date" id="birth_date" name="birth_date" required>
            </div>
            
            <div class="form-group">
                <label for="accuracy">Точность (%)</label>
                <input type="number" id="accuracy" name="accuracy" required min="0" max="100">
            </div>

            <div class="form-group">
                <label for="goals">Количество голов</label>
                <input type="number" id="goals" name="goals" min="0" value="0">
            </div>

            <div class="form-group">
                <label for="penalties">Количество штрафов</label>
                <input type="number" id="penalties" name="penalties" min="0" value="0">
            </div>
            
            <div class="form-group">
                <label for="photo">Фото</label>
                <input type="file" id="photo" name="photo" accept="image/jpeg,image/png">
            </div>
            
            <div class="form-actions">
                <button type="button" onclick="closePlayerModal()" class="btn btn--secondary">Отмена</button>
                <button type="submit" class="btn btn--primary">Сохранить</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php
function calculateAge($birthDate) {
    return date_diff(date_create($birthDate), date_create('today'))->y;
}
?>

<script>
// Добавляем отладочные сообщения
function openPlayerModal(playerId = null) {
    console.log('Opening modal for player:', playerId);
    const modal = document.getElementById('playerModal');
    const form = document.getElementById('playerForm');
    const title = document.getElementById('modalTitle');
    
    if (playerId) {
        title.textContent = 'Редактировать игрока';
        console.log('Fetching player data...');
        // Загрузка данных игрока
        fetch(`/pages/team/actions/get_player.php?id=${playerId}`)
            .then(response => {
                console.log('Response status:', response.status);
                return response.json();
            })
            .then(player => {
                console.log('Player data:', player);
                if (player.error) {
                    console.error('Server error:', player.error);
                    alert(player.error);
                    return;
                }
                form.elements['id'].value = player.id;
                form.elements['number'].value = player.number;
                form.elements['name'].value = player.name;
                form.elements['position'].value = player.position;
                form.elements['height'].value = player.height;
                form.elements['weight'].value = player.weight;
                form.elements['birth_date'].value = player.birth_date;
                form.elements['accuracy'].value = player.accuracy;
            })
            .catch(error => {
                console.error('Fetch error:', error);
                alert('Ошибка при загрузке данных игрока: ' + error.message);
            });
    } else {
        console.log('Creating new player');
        title.textContent = 'Добавить игрока';
        form.reset();
        form.elements['id'].value = '';
    }
    
    modal.style.display = 'flex';
}

function closePlayerModal() {
    console.log('Closing modal');
    document.getElementById('playerModal').style.display = 'none';
}

function savePlayer(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.textContent = 'Сохранение...';
    submitBtn.disabled = true;
    
    fetch('/pages/team/actions/save_player.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(text => {
        try {
            return JSON.parse(text);
        } catch (e) {
            throw new Error('Некорректный формат ответа от сервера');
        }
    })
    .then(data => {
        if (data.success) {
            closePlayerModal();
            location.reload();
        } else {
            throw new Error(data.error || 'Неизвестная ошибка');
        }
    })
    .catch(error => {
        alert('Ошибка при сохранении: ' + error.message);
    })
    .finally(() => {
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
    });
}

function editPlayer(playerId) {
    console.log('Редактирование игрока:', playerId);
    
    fetch(`/pages/team/actions/get_player.php?id=${playerId}`)
        .then(response => {
            console.log('Статус ответа:', response.status);
            console.log('Заголовки:', response.headers);
            return response.text(); // Сначала получаем текст ответа
        })
        .then(text => {
            console.log('Текст ответа:', text);
            try {
                return JSON.parse(text); // Пробуем распарсить JSON
            } catch (e) {
                console.error('Ошибка парсинга JSON:', e);
                throw new Error('Некорректный формат ответа от сервера');
            }
        })
        .then(data => {
            console.log('Данные игрока:', data);
            if (data.error) {
                throw new Error(data.error);
            }
            
            const modal = document.getElementById('playerModal');
            const form = document.getElementById('playerForm');
            const title = document.getElementById('modalTitle');
            
            title.textContent = 'Редактировать игрока';
            
            // Заполняем форму данными
            form.elements['id'].value = data.id || '';
            form.elements['number'].value = data.number || '';
            form.elements['name'].value = data.name || '';
            form.elements['position'].value = data.position || '';
            form.elements['height'].value = data.height || '';
            form.elements['weight'].value = data.weight || '';
            form.elements['birth_date'].value = data.birth_date || '';
            form.elements['accuracy'].value = data.accuracy || '';
            
            modal.style.display = 'flex';
        })
        .catch(error => {
            console.error('Ошибка:', error);
            alert('Ошибка при загрузке данных игрока: ' + error.message);
        });
}

function deletePlayer(playerId) {
    if (!confirm('Вы действительно хотите удалить этого игрока?')) {
        return;
    }
    
    const deleteBtn = event.target.closest('button');
    const originalHtml = deleteBtn.innerHTML;
    deleteBtn.innerHTML = '<span class="material-icons rotating">sync</span>';
    deleteBtn.disabled = true;
    
    fetch('/pages/team/actions/delete_player.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ id: playerId })
    })
    .then(response => response.text())
    .then(text => {
        try {
            return JSON.parse(text);
        } catch (e) {
            throw new Error('Некорректный формат ответа от сервера');
        }
    })
    .then(data => {
        if (data.success) {
            const playerCard = document.querySelector(`[data-player-id="${playerId}"]`);
            if (playerCard) {
                playerCard.remove();
            }
        } else {
            throw new Error(data.error || 'Неизвестная ошибка');
        }
    })
    .catch(error => {
        alert('Ошибка при удалении игрока: ' + error.message);
    })
    .finally(() => {
        deleteBtn.innerHTML = originalHtml;
        deleteBtn.disabled = false;
    });
}

// Закрытие модального окна по клику вне его
window.onclick = function(event) {
    const modal = document.getElementById('playerModal');
    if (event.target === modal) {
        closePlayerModal();
    }
}

// Закрытие по Escape
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closePlayerModal();
    }
});
</script>

<style>
@keyframes rotate {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
}

.rotating {
    animation: rotate 1s linear infinite;
}

.player-card {
    position: relative;
    transition: opacity 0.3s ease-out;
}

.player-card.deleting {
    opacity: 0.5;
    pointer-events: none;
}
</style>

<?php include '../includes/footer.php'; ?> 