<?php
$page_title = "Галерея";
include_once $_SERVER['DOCUMENT_ROOT'] . '/includes/header.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config.php';

// Проверка прав доступа для загрузки фото
$can_manage = isset($_SESSION['user_role']) && ($_SESSION['user_role'] === 'trainer' || $_SESSION['user_role'] === 'admin');

// Параметры сортировки и пагинации
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'date_desc';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 12;
$offset = ($page - 1) * $per_page;

// Определение порядка сортировки
$order_by = match($sort) {
    'date_asc' => 'g.created_at ASC',
    'likes' => '(SELECT COUNT(*) FROM gallery_likes gl WHERE gl.photo_id = g.id) DESC',
    'comments' => '(SELECT COUNT(*) FROM gallery_comments gc WHERE gc.photo_id = g.id) DESC',
    default => 'g.created_at DESC'
};

// Получение общего количества фотографий
$total_query = "SELECT COUNT(*) as total FROM gallery";
$total_result = $conn->query($total_query);
$total_photos = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_photos / $per_page);

// Получение фотографий с количеством лайков и комментариев
$query = "SELECT g.*, 
    (SELECT COUNT(*) FROM gallery_likes gl WHERE gl.photo_id = g.id) as likes_count,
    (SELECT COUNT(*) FROM gallery_comments gc WHERE gc.photo_id = g.id) as comments_count
    FROM gallery g
    ORDER BY $order_by
    LIMIT $per_page OFFSET $offset";

$result = $conn->query($query);
?>

<div class="gallery-container">
    <?php if ($can_manage): ?>
    <div class="upload-section">
        <form action="/actions/upload_photo.php" method="post" enctype="multipart/form-data">
            <input type="file" name="photo" required accept="image/*">
            <input type="text" name="title" placeholder="Название фото (необязательно)">
            <button type="submit">Загрузить</button>
        </form>
    </div>
    <?php endif; ?>

    <div class="gallery-filters">
        <select onchange="window.location.href='?sort=' + this.value">
            <option value="date_desc" <?= $sort === 'date_desc' ? 'selected' : '' ?>>Сначала новые</option>
            <option value="date_asc" <?= $sort === 'date_asc' ? 'selected' : '' ?>>Сначала старые</option>
            <option value="likes" <?= $sort === 'likes' ? 'selected' : '' ?>>По лайкам</option>
            <option value="comments" <?= $sort === 'comments' ? 'selected' : '' ?>>По комментариям</option>
        </select>
    </div>

    <div class="gallery-grid">
        <?php while ($photo = $result->fetch_assoc()): ?>
        <div class="gallery-item" data-id="<?= $photo['id'] ?>" onclick="openPhoto(<?= $photo['id'] ?>)">
            <img src="<?= htmlspecialchars($photo['image_path']) ?>" alt="<?= htmlspecialchars($photo['title'] ?? '') ?>">
            <div class="photo-info">
                <?php if ($photo['title']): ?>
                    <div class="photo-title"><?= htmlspecialchars($photo['title']) ?></div>
                <?php endif; ?>
                <div class="photo-stats">
                    <span>❤ <?= $photo['likes_count'] ?></span>
                    <span>💬 <?= $photo['comments_count'] ?></span>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    </div>

    <?php if ($total_pages > 1): ?>
    <div class="pagination">
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <a href="?page=<?= $i ?>&sort=<?= $sort ?>" 
               class="<?= $i === $page ? 'active' : '' ?>">
                <?= $i ?>
            </a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Модальное окно для просмотра фото -->
<div id="photoModal" class="modal">
    <span class="close">&times;</span>
    <div class="modal-content">
        <button class="nav-btn prev" onclick="navigatePhoto(-1)">&#10094;</button>
        <div class="photo-container">
            <img id="modalImg" src="" alt="">
            <div class="photo-details">
                <h3 id="modalTitle"></h3>
                <div id="modalDate"></div>
                <div class="likes-section">
                    <button onclick="likePhoto(currentPhotoId)" id="likeBtn">❤</button>
                    <span id="likesCount">0</span>
                </div>
                <div class="comments-section">
                    <div id="comments"></div>
                    <form id="commentForm" onsubmit="submitComment(event)">
                        <textarea name="comment" required></textarea>
                        <button type="submit">Отправить</button>
                    </form>
                </div>
            </div>
        </div>
        <button class="nav-btn next" onclick="navigatePhoto(1)">&#10095;</button>
    </div>
</div>

<style>
.gallery-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.upload-section {
    margin-bottom: 20px;
    padding: 20px;
    background: #f5f5f5;
    border-radius: 8px;
}

.gallery-filters {
    margin-bottom: 20px;
}

.gallery-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.gallery-item {
    position: relative;
    aspect-ratio: 1;
    overflow: hidden;
    border-radius: 8px;
    cursor: pointer;
    transition: transform 0.3s;
}

.gallery-item:hover {
    transform: scale(1.02);
}

.gallery-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.photo-info {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 10px;
    background: rgba(0, 0, 0, 0.7);
    color: white;
    opacity: 0;
    transition: opacity 0.3s;
}

.gallery-item:hover .photo-info {
    opacity: 1;
}

.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.9);
    z-index: 1000;
}

.modal-content {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100%;
    padding: 20px;
}

.photo-container {
    display: flex;
    max-width: 90%;
    max-height: 90vh;
    background: white;
    border-radius: 8px;
}

.photo-container img {
    max-height: 90vh;
    max-width: 70%;
    object-fit: contain;
}

.photo-details {
    padding: 20px;
    width: 300px;
    background: #f5f5f5;
    border-top-right-radius: 8px;
    border-bottom-right-radius: 8px;
    overflow-y: auto;
}

.nav-btn {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    background: rgba(255, 255, 255, 0.5);
    border: none;
    padding: 15px;
    cursor: pointer;
    border-radius: 50%;
}

.prev { left: 20px; }
.next { right: 20px; }

.close {
    position: absolute;
    top: 20px;
    right: 20px;
    color: white;
    font-size: 30px;
    cursor: pointer;
}

.pagination {
    display: flex;
    justify-content: center;
    gap: 10px;
    margin-top: 20px;
}

.pagination a {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    text-decoration: none;
    color: #333;
}

.pagination a.active {
    background: #007bff;
    color: white;
    border-color: #007bff;
}

.likes-section {
    margin: 20px 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.comments-section {
    margin-top: 20px;
}

.comment {
    margin-bottom: 15px;
    padding: 10px;
    background: white;
    border-radius: 4px;
}

#commentForm {
    margin-top: 20px;
}

#commentForm textarea {
    width: 100%;
    min-height: 100px;
    margin-bottom: 10px;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.liked {
    color: red;
}
</style>

<script>
let currentPhotoId = null;
let photoIds = [];

// Собираем все ID фотографий
document.querySelectorAll('.gallery-item').forEach(item => {
    photoIds.push(item.dataset.id);
});

function openPhoto(id) {
    currentPhotoId = id;
    fetch(`/actions/get_photo.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('modalImg').src = data.image_path;
            document.getElementById('modalTitle').textContent = data.title || '';
            document.getElementById('modalDate').textContent = new Date(data.created_at).toLocaleDateString();
            document.getElementById('likesCount').textContent = data.likes_count;
            document.getElementById('likeBtn').classList.toggle('liked', data.is_liked);
            
            // Загрузка комментариев
            loadComments(id);
            
            document.getElementById('photoModal').style.display = 'block';
        });
}

function navigatePhoto(direction) {
    const currentIndex = photoIds.indexOf(currentPhotoId.toString());
    const newIndex = currentIndex + direction;
    
    if (newIndex >= 0 && newIndex < photoIds.length) {
        openPhoto(photoIds[newIndex]);
    }
}

function loadComments(photoId) {
    fetch(`/actions/get_comments.php?photo_id=${photoId}`)
        .then(response => response.json())
        .then(comments => {
            const commentsHtml = comments.map(comment => `
                <div class="comment">
                    <strong>${comment.user_name}</strong>
                    <span>${new Date(comment.created_at).toLocaleDateString()}</span>
                    <p>${comment.comment}</p>
                </div>
            `).join('');
            document.getElementById('comments').innerHTML = commentsHtml;
        });
}

function likePhoto(photoId) {
    fetch('/actions/like_photo.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ photo_id: photoId })
    })
    .then(response => response.json())
    .then(data => {
        document.getElementById('likesCount').textContent = data.likes_count;
        document.getElementById('likeBtn').classList.toggle('liked', data.is_liked);
    });
}

function submitComment(event) {
    event.preventDefault();
    const form = event.target;
    const comment = form.comment.value;

    fetch('/actions/add_comment.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            photo_id: currentPhotoId,
            comment: comment
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            form.reset();
            loadComments(currentPhotoId);
        }
    });
}

// Закрытие модального окна
document.querySelector('.close').onclick = function() {
    document.getElementById('photoModal').style.display = 'none';
}
</script>

<?php include_once $_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'; ?> 