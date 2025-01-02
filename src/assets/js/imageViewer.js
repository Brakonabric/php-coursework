let currentImageIndex = 0;
let images = [];
let modalType = '';

const modal = document.createElement('div');
modal.className = 'iv-modal';
modal.innerHTML = `
    <div class="iv-modal-overlay">
        <div class="iv-modal-header">
            <span class="iv-close-button material-icons">close</span>
        </div>
        
        <div class="iv-modal-main">
            <button class="iv-nav-button prev material-icons">chevron_left</button>
            
            <div class="iv-modal-content">
                <div class="iv-image-container">
                    <img src="" alt="Attēls">
                </div>
                
                <div class="iv-image-info">
                    <div class="iv-photo-actions">
                        <span class="iv-likes-count">
                            <span class="material-icons">favorite</span>
                            <span class="count">0</span>
                        </span>
                        <span class="iv-comments-count">
                            <span class="material-icons">comment</span>
                            <span class="count">0</span>
                        </span>
                    </div>
                </div>
            </div>
            
            <button class="iv-nav-button next material-icons">chevron_right</button>
        </div>
        
        <div class="iv-comments-sidebar">
            <div class="iv-comments-header">
                <h3>Komentāri</h3>
                <button class="btn btn-danger delete-photo" style="display: none;">
                    <span class="material-icons">delete</span>
                    Dzēst
                </button>
            </div>
            <div class="iv-comments-list"></div>
            <div class="iv-comment-form"></div>
        </div>
    </div>
`;

document.body.appendChild(modal);

const modalImg = modal.querySelector('img');
const closeBtn = modal.querySelector('.iv-close-button');
const prevBtn = modal.querySelector('.iv-nav-button.prev');
const nextBtn = modal.querySelector('.iv-nav-button.next');
const commentsSidebar = modal.querySelector('.iv-comments-sidebar');
const commentsList = modal.querySelector('.iv-comments-list');
const commentForm = modal.querySelector('.iv-comment-form');
const commentInput = modal.querySelector('textarea');
const submitCommentBtn = modal.querySelector('.iv-submit-comment');
const imageInfo = modal.querySelector('.iv-image-info');
const likesCount = modal.querySelector('.iv-likes-count .count');
const commentsCount = modal.querySelector('.iv-comments-count .count');
const likeButton = modal.querySelector('.iv-likes-count');
const commentsButton = modal.querySelector('.iv-comments-count');
let isSidebarVisible = false;

function openGalleryImage(element) {
    modalType = 'gallery';
    const galleryGrid = element.closest('.gallery-grid');
    images = Array.from(galleryGrid.querySelectorAll('.gallery-item'));
    currentImageIndex = images.indexOf(element.closest('.gallery-item'));

    const deleteBtn = modal.querySelector('.delete-photo');
    const hasAccess = document.body.dataset.userRole === 'admin' || document.body.dataset.userRole === 'coach';
    if (deleteBtn) {
        const currentItem = images[currentImageIndex];
        const isNewsPhoto = currentItem.dataset.source === 'news';
        deleteBtn.style.display = hasAccess && !isNewsPhoto ? 'inline-flex' : 'none';
    }
    
    showModal(true, true);
    loadComments(getCurrentPhotoId());
    loadLikes(getCurrentPhotoId());
    document.body.style.overflow = 'hidden';
}

function openNewsImage(element) {
    modalType = 'news';
    const newsContainer = element.closest('.post-gallery');
    if (!newsContainer) return;
    
    images = Array.from(newsContainer.querySelectorAll('.gallery-item'));
    currentImageIndex = images.indexOf(element.closest('.gallery-item'));

    const deleteBtn = modal.querySelector('.delete-photo');
    if (deleteBtn) {
        deleteBtn.style.display = 'none';
    }
    
    showModal(false, false);
    document.body.style.overflow = 'hidden';
}

function openHomeImage(element) {
    modalType = 'home';
    const container = element.closest('.gallery-grid');
    images = Array.from(container.querySelectorAll('.gallery-item'));
    currentImageIndex = images.indexOf(element.closest('.gallery-item'));
    
    showModal(false, true);
    loadComments(getCurrentPhotoId());
    loadLikes(getCurrentPhotoId());
    document.body.style.overflow = 'hidden';
}

function getCurrentPhotoId() {
    const currentItem = images[currentImageIndex];
    return currentItem ? currentItem.dataset.id : null;
}

function showModal(showSidebar = false, showMeta = true) {
    updateImage();
    modal.style.display = 'flex';
    isSidebarVisible = showSidebar;
    modal.classList.toggle('with-sidebar', isSidebarVisible);
    imageInfo.style.display = showMeta ? 'block' : 'none';
    updateNavigationButtons();
}

function updateImage() {
    if (images.length > 0 && currentImageIndex >= 0 && currentImageIndex < images.length) {
        const currentImage = images[currentImageIndex].querySelector('img');
        modalImg.src = currentImage.src;
        const photoId = getCurrentPhotoId();
        if (photoId && imageInfo.style.display !== 'none') {
            loadComments(photoId);
            loadLikes(photoId);
        }
    }
}

function updateNavigationButtons() {
    prevBtn.style.display = currentImageIndex > 0 ? 'block' : 'none';
    nextBtn.style.display = currentImageIndex < images.length - 1 ? 'block' : 'none';
}

async function loadLikes(photoId) {
    if (!photoId) return;
    
    try {
        const response = await fetch('/pages/gallery/actions/likePhoto.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ 
                photo_id: photoId,
                action: 'get'
            })
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        if (data.success) {
            likesCount.textContent = data.likes_count;
            likeButton.classList.toggle('liked', data.is_liked);
            likeButton.querySelector('.material-icons').textContent = 
                data.is_liked ? 'favorite' : 'favorite_border';
        }
    } catch (error) {
        console.error('Error loading likes:', error);
    }
}

async function toggleLike() {
    const photoId = getCurrentPhotoId();
    if (!photoId) return;

    try {
        const response = await fetch('/pages/gallery/actions/likePhoto.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ 
                photo_id: photoId,
                action: 'toggle'
            })
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        if (data.success) {
            likesCount.textContent = data.likes_count;
            likeButton.classList.toggle('liked', data.is_liked);
            likeButton.querySelector('.material-icons').textContent = 
                data.is_liked ? 'favorite' : 'favorite_border';
        }
    } catch (error) {
        console.error('Error toggling like:', error);
    }
}

async function loadComments(photoId) {
    if (!photoId) return;
    
    try {
        const response = await fetch(`/pages/gallery/actions/getComments.php?photoId=${photoId}`);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        if (data.success) {
            commentsCount.textContent = data.comments.length;
            
            if (data.comments.length > 0) {
                commentsList.innerHTML = data.comments.map(comment => `
                    <div class="iv-comment">
                        <div class="iv-comment-header">
                            <span class="iv-comment-author">${comment.author}</span>
                            <span class="iv-comment-date">${comment.date}</span>
                        </div>
                        <div class="iv-comment-text">${comment.text}</div>
                    </div>
                `).join('');
            } else {
                commentsList.innerHTML = `
                    <div class="no-comments">
                        <span class="material-icons">chat_bubble_outline</span>
                        <p>Pagaidām nav neviena komentāra. Esiet pirmais, kas komentē!</p>
                    </div>
                `;
            }
            
            if (data.can_comment) {
                commentForm.innerHTML = `
                    <textarea placeholder="Pievienot komentāru"></textarea>
                    <button class="iv-submit-comment">
                        <span class="material-icons">send</span>
                        Nosūtīt
                    </button>
                `;
                
                const newCommentInput = commentForm.querySelector('textarea');
                const newSubmitBtn = commentForm.querySelector('.iv-submit-comment');
                
                newSubmitBtn.addEventListener('click', async () => {
                    const text = newCommentInput.value.trim();
                    if (!text) return;

                    try {
                        const response = await fetch('/pages/gallery/actions/addComment.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                photoId,
                                text
                            })
                        });

                        const data = await response.json();
                        
                        if (!response.ok) {
                            throw new Error(data.error || 'Kļūda pievienojot komentāru');
                        }

                        if (data.success) {
                            newCommentInput.value = '';
                            loadComments(photoId);
                        }
                    } catch (error) {
                        console.error('Error adding comment:', error);
                    }
                });
            } else if (!data.is_logged_in) {
                commentForm.innerHTML = `
                    <div class="auth-prompt">
                        <span class="material-icons">info</span>
                        <p>Lai pievienotu komentāru, lūdzu, <a href="/auth/login.php">piesakieties</a> vai <a href="/auth/register.php">reģistrējieties</a>.</p>
                    </div>
                `;
            } else {
                commentForm.innerHTML = `
                    <div class="auth-prompt">
                        <div class="auth-prompt-icon">
                            <span class="material-icons">info</span>
                        </div>
                        <div class="auth-prompt-message">
                            <p>Jums nav atļauts pievienot komentārus.</p>
                        </div>
                    </div>
                `;
            }
        }
    } catch (error) {
        console.error('Error loading comments:', error);
    }
}

closeBtn.addEventListener('click', () => {
    modal.style.display = 'none';
    document.body.style.overflow = 'auto';
});

prevBtn.addEventListener('click', (e) => {
    e.stopPropagation();
    if (currentImageIndex > 0) {
        currentImageIndex--;
        updateImage();
        updateNavigationButtons();
    }
});

nextBtn.addEventListener('click', (e) => {
    e.stopPropagation();
    if (currentImageIndex < images.length - 1) {
        currentImageIndex++;
        updateImage();
        updateNavigationButtons();
    }
});

modal.addEventListener('click', (e) => {
    if (e.target.classList.contains('iv-modal-overlay') || e.target.classList.contains('iv-modal-main')) {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
});

modal.querySelector('.iv-modal-content').addEventListener('click', (e) => {
    e.stopPropagation();
});

commentsSidebar.addEventListener('click', (e) => {
    e.stopPropagation();
});

likeButton.addEventListener('click', toggleLike);

const oldSubmitCommentBtn = modal.querySelector('.iv-submit-comment');
if (oldSubmitCommentBtn) {
    oldSubmitCommentBtn.removeEventListener('click', submitCommentBtn.onclick);
}

document.addEventListener('keydown', (e) => {
    if (modal.style.display === 'flex') {
        if (e.key === 'Escape') {
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
        } else if (e.key === 'ArrowLeft' && currentImageIndex > 0) {
            currentImageIndex--;
            updateImage();
            updateNavigationButtons();
        } else if (e.key === 'ArrowRight' && currentImageIndex < images.length - 1) {
            currentImageIndex++;
            updateImage();
            updateNavigationButtons();
        }
    }
});

function toggleSidebar() {
    if (modalType === 'gallery') {
        isSidebarVisible = !isSidebarVisible;
        modal.classList.toggle('with-sidebar', isSidebarVisible);
    }
}

commentsButton.addEventListener('click', (e) => {
    e.stopPropagation();
    toggleSidebar();
});

function closeModal() {
    modal.style.display = 'none';
    document.body.style.overflow = 'auto';
}

async function deletePhoto() {
    const photoId = getCurrentPhotoId();
    if (!photoId) return;
    
    if (!confirm('Vai tiešām vēlaties dzēst šo fotoattēlu?')) {
        return;
    }

    try {
        const response = await fetch('/pages/gallery/actions/deletePhoto.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ photo_id: photoId })
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        if (data.success) {
            const currentItem = images[currentImageIndex];
            currentItem.remove();

            closeModal();

            images = Array.from(document.querySelectorAll('.gallery-item'));

            if (images.length === 0) {
                window.location.reload();
            }
        } else {
            throw new Error(data.error || 'Failed to delete photo');
        }
    } catch (error) {
        console.error('Error deleting photo:', error);
        alert('Kļūda dzēšot fotoattēlu: ' + error.message);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const deleteBtn = modal.querySelector('.delete-photo');
    if (deleteBtn) {
        deleteBtn.addEventListener('click', deletePhoto);
    }
}); 