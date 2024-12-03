<?php
$page_title = "Галерея";
include '../includes/header.php';
require_once '../config.php';

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
        <form action="/pages/gallery/actions/upload_photo.php" method="post" enctype="multipart/form-data">
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
        <?php if ($result->num_rows > 0): ?>
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
        <?php else: ?>
            <div class="no-content">
                <p>В галерее пока нет фотографий</p>
            </div>
        <?php endif; ?>
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

<script>
let currentPhotoId = null;
let photoIds = [];

// Собираем все ID фотографий
document.querySelectorAll('.gallery-item').forEach(item => {
    photoIds.push(item.dataset.id);
});

function openPhoto(id) {
    currentPhotoId = id;
    fetch(`/pages/gallery/actions/get_photo.php?id=${id}`)
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
    fetch(`/pages/gallery/actions/get_comments.php?photo_id=${photoId}`)
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
    fetch('/pages/gallery/actions/like_photo.php', {
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

    fetch('/pages/gallery/actions/add_comment.php', {
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

<?php include '../includes/footer.php'; ?> 