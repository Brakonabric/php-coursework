<?php
$page_title = "Galerija";
include '../includes/header.php';
require_once '../config.php';

$can_manage = isset($_SESSION['userRole']) && hasAccess('coach', $_SESSION['userRole']);

$sort = isset($_GET['sort']) ? $_GET['sort'] : 'date_desc';

$order_by = match($sort) {
    'date_asc' => 'g.created_at ASC',
    'likes' => '(SELECT COUNT(*) FROM gallery_likes gl WHERE gl.photo_id = g.id) DESC',
    'comments' => '(SELECT COUNT(*) FROM gallery_comments gc WHERE gc.photo_id = g.id) DESC',
    default => 'g.created_at DESC'
};

$query = "SELECT g.*, 
    (SELECT COUNT(*) FROM gallery_likes gl WHERE gl.photo_id = g.id) as likes_count,
    (SELECT COUNT(*) FROM gallery_comments gc WHERE gc.photo_id = g.id) as comments_count,
    CASE WHEN g.source_type = 'news' THEN 1 ELSE 0 END as is_news_photo
    FROM gallery g
    ORDER BY $order_by";

$result = $conn->query($query);
?>

<main class="gallery-page">
    <div class="gallery-container">
        <div class="gallery-header">
            <?php if ($can_manage): ?>
            <div class="upload-section">
                <form action="/pages/gallery/actions/uploadPhoto.php" method="post" enctype="multipart/form-data" id="uploadForm">
                    <label for="photo" class="link-btn btn-primary">
                        <span class="material-icons">add_photo_alternate</span>
                        Pievienot attēlu
                    </label>
                    <input type="file" id="photo" name="photo" required accept="image/*">
                </form>
                <button onclick="importNewsPhotos()" class="link-btn btn-primary">
                    <span class="material-icons">content_copy</span>
                    Importēt fotoattēlus no ziņām
                </button>
            </div>
            <?php endif; ?>

            <div class="gallery-filters">
                <select onchange="window.location.href='?sort=' + this.value">
                    <option value="date_desc" <?= $sort === 'date_desc' ? 'selected' : '' ?>>Jaunākie vispirms</option>
                    <option value="date_asc" <?= $sort === 'date_asc' ? 'selected' : '' ?>>Vecākie vispirms</option>
                    <option value="likes" <?= $sort === 'likes' ? 'selected' : '' ?>>Pēc novērtējuma</option>
                    <option value="comments" <?= $sort === 'comments' ? 'selected' : '' ?>>Pēc komentāriem</option>
                </select>
            </div>
        </div>

        <div class="gallery-grid">
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($photo = $result->fetch_assoc()): ?>
                    <div class="gallery-item" data-id="<?= $photo['id'] ?>" data-source="<?= $photo['is_news_photo'] > 0 ? 'news' : 'gallery' ?>" onclick="openGalleryImage(this)">
                        <img src="<?= htmlspecialchars($photo['image_path']) ?>" alt="Galerijas attēls">
                        <div class="photo-overlay">
                            <span class="likes-count">
                                <span class="material-icons">favorite</span>
                                <?= $photo['likes_count'] ?>
                            </span>
                            <span class="comments-count">
                                <span class="material-icons">comment</span>
                                <?= $photo['comments_count'] ?>
                            </span>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-content">
                    <span class="material-icons">collections</span>
                    <p>Galerijā pagaidām nav fotoattēlu</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<script>
document.getElementById('photo').addEventListener('change', function() {
    if (this.files.length > 0) {
        document.getElementById('uploadForm').submit();
    }
});

async function importNewsPhotos() {
    try {
        const response = await fetch('/pages/gallery/actions/importNewsPhotos.php', {
            method: 'POST'
        });
        const data = await response.json();
        
        if (data.success) {
            alert(data.message);
            window.location.reload();
        } else {
            alert(data.error || 'Kļūda importējot fotoattēlus');
        }
    } catch (error) {
        console.error('Import error:', error);
        alert('Kļūda importējot fotoattēlus');
    }
}
</script>

<?php include '../includes/footer.php'; ?> 