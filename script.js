const loginUsers = document.querySelector('.login-users');
const loginLink = document.querySelector('.login-link');
const registerLink = document.querySelector('.register-link');
const alertContainer = document.querySelector('.alert-container');

registerLink.addEventListener('click', () => loginUsers.classList.add('slide'));
loginLink.addEventListener('click', () => loginUsers.classList.remove('slide'));

if (alertContainer) {
    const alerts = alertContainer.querySelectorAll('.alert');

    alerts.forEach((alert, i) => {
        // Delay appearance (optional)
        setTimeout(() => {
            alert.style.opacity = '1';
        }, 100);

        // Auto fade and remove
        setTimeout(() => {
            alert.classList.add('fade-out');
            setTimeout(() => alert.remove(), 500); // Remove after transition
        }, 3000 + i * 300); // Delay per alert
    });
}



