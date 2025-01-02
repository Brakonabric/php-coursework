document.addEventListener('DOMContentLoaded', function() {
    const tabs = document.querySelectorAll('.nav-pills a');
    const tabContents = document.querySelectorAll('.tab-pane');
    
    tabs.forEach(tab => {
        tab.addEventListener('click', function(e) {
            e.preventDefault();
            
            tabs.forEach(t => t.classList.remove('active'));
            tabContents.forEach(c => c.classList.remove('active'));
            
            this.classList.add('active');
            const tabId = this.getAttribute('data-tab');
            document.getElementById(tabId).classList.add('active');
            
            switch(tabId) {
                case 'users':
                    loadUsers();
                    break;
                case 'comments':
                    loadComments();
                    break;
                case 'news':
                    loadNews();
                    break;
                case 'database':
                    checkDatabase();
                    break;
            }
        });
    });
    
    loadUsers();
});

function initTabs() {
    document.querySelectorAll('.nav-pills a').forEach(tab => {
        tab.addEventListener('click', function(e) {
            e.preventDefault();

            document.querySelectorAll('.nav-pills a').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));

            this.classList.add('active');
            const tabId = this.getAttribute('data-tab');
            document.getElementById(tabId).classList.add('active');
        });
    });
}

function loadUsers() {
    fetch('/pages/admin/features/getUsers.php')
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                throw new Error(data.error);
            }
            
            const tbody = document.getElementById('usersTableBody');
            tbody.innerHTML = '';
            
            data.forEach(user => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${user.name}</td>
                    <td>${user.email}</td>
                    <td>
                        ${isAdmin ? `
                            <select class="form-select form-select-sm" data-user-id="${user.id}">
                                <option value="admin" ${user.role === 'admin' ? 'selected' : ''}>Administrators</option>
                                <option value="coach" ${user.role === 'coach' ? 'selected' : ''}>Treneris</option>
                                <option value="teamMember" ${user.role === 'teamMember' ? 'selected' : ''}>Komandas biedrs</option>
                                <option value="fan" ${user.role === 'fan' ? 'selected' : ''}>Fans</option>
                                <option value="guest" ${user.role === 'guest' ? 'selected' : ''}>Viesis</option>
                            </select>
                        ` : `<span>${user.role}</span>`}
                    </td>
                    <td>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" data-user-id="${user.id}" 
                                ${user.can_comment ? 'checked' : ''}>
                        </div>
                    </td>
                    <td>
                        ${isAdmin ? `
                            <button class="btn btn-success btn-sm save-changes" data-user-id="${user.id}">
                                <span class="material-icons">save</span>
                                Saglabāt
                            </button>
                            <button class="btn btn-danger btn-sm" onclick="deleteUser(${user.id})">
                                <span class="material-icons">delete</span>
                                Dzēst
                            </button>
                        ` : ''}
                    </td>
                `;
                tbody.appendChild(tr);
            });

            document.querySelectorAll('.save-changes').forEach(button => {
                button.addEventListener('click', function() {
                    const userId = this.dataset.userId;
                    const row = this.closest('tr');
                    const role = row.querySelector('select').value;
                    const canComment = row.querySelector('.form-check-input').checked;
                    
                    saveUserChanges(userId, role, canComment);
                });
            });
        })
        .catch(error => {
            console.error('Error loading users:', error);
            alert('Kļūda ielādējot lietotājus: ' + error.message);
        });
}

function loadComments() {
    fetch('/pages/admin/features/getComments.php')
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                throw new Error(data.error);
            }
            
            const tbody = document.getElementById('commentsTableBody');
            tbody.innerHTML = '';
            
            if (!data.comments || data.comments.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="4" class="text-center">Nav komentāru</td>
                    </tr>
                `;
                return;
            }
            
            data.comments.forEach(comment => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${comment.user_name}</td>
                    <td>${comment.content}</td>
                    <td>${new Date(comment.created_at).toLocaleString('lv-LV')}</td>
                    <td>
                        <button class="btn btn-danger btn-sm" onclick="deleteComment(${comment.id}, '${comment.type}')">
                            <span class="material-icons">delete</span>
                            Dzēst
                        </button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        })
        .catch(error => {
            console.error('Error loading comments:', error);
            alert('Kļūda ielādējot komentārus: ' + error.message);
        });
}

function saveUserChanges(userId, role, canComment) {
    console.log('Saving changes for user:', { userId, role, canComment });

    fetch('/pages/admin/features/changeRole.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            userId: userId,
            role: role
        })
    })
    .then(response => {
        if (!response.ok) {
            return response.json().then(data => {
                throw new Error(data.error || 'Failed to update role');
            });
        }
        return response.json();
    })
    .then(data => {
        return fetch('/pages/admin/features/toggleComments.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                userId: userId,
                canComment: canComment
            })
        });
    })
    .then(response => {
        if (!response.ok) {
            return response.json().then(data => {
                throw new Error(data.error || 'Failed to update comment permission');
            });
        }
        return response.json();
    })
    .then(data => {
        alert('Izmaiņas saglabātas');
        loadUsers();
    })
    .catch(error => {
        console.error('Error saving changes:', error);
        alert('Kļūda saglabājot izmaiņas: ' + error.message);
    });
}

function deleteUser(userId) {
    if (confirm('Vai tiešām vēlaties dzēst šo lietotāju?')) {
        fetch('/pages/admin/features/deleteUser.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ userId: userId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                throw new Error(data.error);
            }
            loadUsers();
        })
        .catch(error => {
            console.error('Error deleting user:', error);
            alert('Kļūda dzēšot lietotāju: ' + error.message);
        });
    }
}

function deleteComment(commentId, type) {
    if (confirm('Vai tiešām vēlaties dzēst šo komentāru?')) {
        fetch('/pages/admin/features/deleteComment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ 
                commentId: commentId,
                type: type
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                throw new Error(data.error);
            }
            loadComments();
        })
        .catch(error => {
            console.error('Error deleting comment:', error);
            alert('Kļūda dzēšot komentāru: ' + error.message);
        });
    }
}

function resetDatabase() {
    if (!confirm('Vai tiešām vēlaties atiestatīt datubāzi? Visi dati tiks dzēsti!')) {
        return;
    }
    
    const keepUsers = confirm('Vai vēlaties saglabāt lietotāju tabulu?');
    
    fetch('/pages/admin/features/resetDatabase.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            keepUsers: keepUsers
        })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Servera kļūda');
        }
        return response.json();
    })
    .then(data => {
        if (data.error) {
            throw new Error(data.error);
        }
        document.getElementById('databaseStatus').innerHTML = `
            <div class="alert alert-success">
                <span class="material-icons">check_circle</span>
                ${data.message || 'Datubāze veiksmīgi atiestatīta'}
            </div>
        `;
        setTimeout(() => {
            window.location.reload();
        }, 2000);
    })
    .catch(error => {
        console.error('Error resetting database:', error);
        document.getElementById('databaseStatus').innerHTML = `
            <div class="alert alert-danger">
                <span class="material-icons">error</span>
                Kļūda atiestatot datubāzi: ${error.message}
            </div>
        `;
    });
}

function checkDatabase() {
    fetch('/pages/admin/features/checkDatabase.php')
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                throw new Error(data.error);
            }
            
            let statusHtml = '<div class="database-status">';
            statusHtml += '<h6>Datubāzes statistika</h6>';
            statusHtml += '<ul>';
            
            for (const [table, count] of Object.entries(data.tables)) {
                statusHtml += `
                    <li>
                        <span class="material-icons">table_chart</span>
                        <span class="table-name">${table}</span>
                        <span class="table-count">${count}</span>
                    </li>
                `;
            }
            
            statusHtml += '</ul>';
            
            if (data.issues && data.issues.length > 0) {
                statusHtml += '<div class="alert alert-warning">';
                statusHtml += '<span class="material-icons">warning</span>';
                statusHtml += '<div>';
                statusHtml += '<h6>Atrāstās problēmas</h6>';
                statusHtml += '<ul>';
                data.issues.forEach(issue => {
                    statusHtml += `<li>${issue}</li>`;
                });
                statusHtml += '</ul>';
                statusHtml += '</div>';
                statusHtml += '</div>';
            } else {
                statusHtml += `
                    <div class="alert alert-success">
                        <span class="material-icons">check_circle</span>
                        <span>Datubāzes struktūra ir kārtībā</span>
                    </div>
                `;
            }
            
            statusHtml += '</div>';
            
            document.getElementById('databaseStatus').innerHTML = statusHtml;
        })
        .catch(error => {
            console.error('Error checking database:', error);
            document.getElementById('databaseStatus').innerHTML = `
                <div class="alert alert-danger">
                    <span class="material-icons">error</span>
                    <span>Kļūda pārbaudot datubāzi: ${error.message}</span>
                </div>
            `;
        });
}

function loadNews() {
    fetch('/pages/admin/features/getNews.php')
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                throw new Error(data.error);
            }
            
            const tbody = document.getElementById('newsTableBody');
            tbody.innerHTML = '';
            
            if (!data.length) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="5" class="text-center">Nav pievienotu ziņu</td>
                    </tr>
                `;
                return;
            }
            
            data.forEach(news => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${news.title}</td>
                    <td>${formatDate(news.created_at)}</td>
                    <td>${news.image_count || 0}</td>
                    <td>${news.comment_count || 0}</td>
                    <td>
                        <button class="btn btn-danger btn-sm" onclick="deleteNews(${news.id})">
                            <span class="material-icons">delete</span>
                            Dzēst
                        </button>
                    </td>
                `;
                tbody.appendChild(row);
            });
        })
        .catch(error => {
            console.error('Error loading news:', error);
            const tbody = document.getElementById('newsTableBody');
            tbody.innerHTML = `
                <tr>
                    <td colspan="5" class="text-center">Kļūda ielādējot ziņas: ${error.message}</td>
                </tr>
            `;
        });
}

function deleteNews(newsId) {
    if (!confirm('Vai tiešām vēlaties dzēst šo ziņu?')) {
        return;
    }

    fetch('/pages/admin/features/deleteNews.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ id: newsId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.error) {
            throw new Error(data.error);
        }
        loadNews();
    })
    .catch(error => {
        console.error('Error deleting news:', error);
        alert('Kļūda dzēšot ziņu: ' + error.message);
    });
}

function formatDate(dateStr) {
    const date = new Date(dateStr);
    return date.toLocaleDateString('lv-LV', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}