function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function clearFieldError(input) {
    const errorDiv = input.nextElementSibling;
    errorDiv.textContent = '';
    input.classList.remove('invalid');
}

function validateField(input) {
    const errorDiv = input.nextElementSibling;
    let isValid = true;

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
    }

    if (input.type === 'email' && input.value) {
        const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
        if (!emailRegex.test(input.value)) {
            errorDiv.textContent = input.dataset.error || 'Lūdzu, ievadiet derīgu e-pasta adresi';
            input.classList.add('invalid');
            isValid = false;
        }
    }

    if ((input.id === 'name' || input.id === 'surname') && input.value) {
        const nameRegex = /^[A-Za-zĀāČčĒēĢģĪīĶķĻļŅņŠšŪūŽž]{2,}$/;
        if (!nameRegex.test(input.value)) {
            errorDiv.textContent = input.dataset.error || 'Vārds nedrīkst saturēt ciparus un speciālos simbolus';
            input.classList.add('invalid');
            isValid = false;
        }
    }

    if (input.type === 'password' && input.value && !input.classList.contains('confirm-password')) {
        const passwordRegex = /^(?=.*[a-zA-Z])(?=.*\d).{8,}$/;
        if (!passwordRegex.test(input.value)) {
            errorDiv.textContent = input.dataset.error || 'Parolei jābūt vismaz 8 rakstzīmes garai un jāsatur vismaz viens cipars un viens burts';
            input.classList.add('invalid');
            isValid = false;
        }
    }

    if (input.classList.contains('confirm-password')) {
        const password = document.querySelector('input[type="password"]:not(.confirm-password)');
        if (input.value !== password.value) {
            errorDiv.textContent = input.dataset.error || 'Paroles nesakrīt';
            input.classList.add('invalid');
            isValid = false;
        }
    }

    return isValid;
}

function showNotification(message, type = 'error') {
    const notifications = document.getElementById('notifications');
    if (!notifications) return;

    const notification = document.createElement('div');
    notification.className = `alert alert-${type}`;
    notification.innerHTML = `
        <span class="material-icons">${type === 'success' ? 'check_circle' : 'error'}</span>
        ${message}
    `;
    notifications.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 5000);
}

function initFormValidation(form) {
    const inputs = form.querySelectorAll('input');
    const debouncedValidate = debounce((input) => validateField(input), 500);

    inputs.forEach(input => {
        input.addEventListener('focus', () => clearFieldError(input));

        input.addEventListener('blur', () => validateField(input));

        input.addEventListener('input', () => {
            if (!input.readOnly) {
                debouncedValidate(input);
            }
        });
    });

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        let isValid = true;

        inputs.forEach(input => {
            if (!input.readOnly && !validateField(input)) {
                isValid = false;
            }
        });

        if (isValid) {
            this.submit();
        }
    });
}

function initLoginFormValidation(form) {
    const inputs = form.querySelectorAll('input');
    const debouncedValidate = debounce((input) => validateField(input), 500);

    inputs.forEach(input => {
        input.addEventListener('focus', () => clearFieldError(input));

        if (input.type === 'email') {
            input.addEventListener('blur', () => validateField(input));
            input.addEventListener('input', () => {
                if (!input.readOnly) {
                    debouncedValidate(input);
                }
            });
        } else if (input.type === 'password') {
            input.addEventListener('blur', () => {
                if (!input.value) {
                    const errorDiv = input.nextElementSibling;
                    errorDiv.textContent = 'Šis lauks ir obligāts';
                    input.classList.add('invalid');
                }
            });
            input.addEventListener('input', () => {
                clearFieldError(input);
            });
        }
    });

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        let isValid = true;

        inputs.forEach(input => {
            if (input.type === 'email') {
                if (!validateField(input)) {
                    isValid = false;
                }
            } else if (input.type === 'password' && !input.value) {
                const errorDiv = input.nextElementSibling;
                errorDiv.textContent = 'Šis lauks ir obligāts';
                input.classList.add('invalid');
                isValid = false;
            }
        });

        if (isValid) {
            this.submit();
        }
    });
}