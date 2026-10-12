// Stainless Laundry Services - Main Frontend JS
document.addEventListener('DOMContentLoaded', function () {
    // Mobile navigation toggle
    const menuToggle = document.querySelector('[data-menu-toggle]');
    const siteNav = document.querySelector('[data-menu]');

    if (menuToggle && siteNav) {
        menuToggle.addEventListener('click', function () {
            const isOpen = siteNav.classList.toggle('open');
            menuToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });
    }

    // Auto-dismiss alert messages after 6 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function (alert) {
        setTimeout(function () {
            alert.style.transition = 'opacity 0.4s ease';
            alert.style.opacity = '0';
            setTimeout(function () {
                if (alert.parentNode) {
                    alert.parentNode.removeChild(alert);
                }
            }, 400);
        }, 6000);
    });

    // Confirmation on destructive actions
    const confirms = document.querySelectorAll('[data-confirm]');
    confirms.forEach(function (el) {
        el.addEventListener('click', function (e) {
            const msg = el.getAttribute('data-confirm') || 'Are you sure you want to proceed?';
            if (!confirm(msg)) {
                e.preventDefault();
            }
        });
    });
});