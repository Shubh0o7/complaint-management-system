/**
 * CampusResolve shared interactions.
 */
document.addEventListener('DOMContentLoaded', function () {
    // Auto-dismiss server alerts without failing when Bootstrap is unavailable.
    document.querySelectorAll('.alert-dismissible').forEach(function (alert) {
        setTimeout(function () {
            if (window.bootstrap && bootstrap.Alert) bootstrap.Alert.getOrCreateInstance(alert).close();
            else alert.remove();
        }, 5000);
    });

    document.querySelectorAll('form[novalidate]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });

    const registerForm = document.getElementById('registerForm');
    if (registerForm) {
        const password = document.getElementById('password');
        const confirmPassword = document.getElementById('confirm_password');
        const validatePasswordMatch = function () {
            if (password && confirmPassword) confirmPassword.setCustomValidity(password.value === confirmPassword.value ? '' : 'Passwords do not match');
        };
        if (password && confirmPassword) {
            password.addEventListener('input', validatePasswordMatch);
            confirmPassword.addEventListener('input', validatePasswordMatch);
        }
    }

    // Mobile sidebar: close on navigation, backdrop click, Escape, or viewport resize.
    const sidebar = document.getElementById('primary-navigation');
    const menuButton = document.querySelector('[data-sidebar-toggle]');
    const backdrop = document.querySelector('[data-sidebar-close]');
    const setSidebar = function (open) {
        if (!sidebar || !menuButton) return;
        sidebar.classList.toggle('open', open);
        menuButton.setAttribute('aria-expanded', String(open));
        menuButton.setAttribute('aria-label', open ? 'Close navigation' : 'Open navigation');
        document.body.classList.toggle('sidebar-open', open);
    };
    if (menuButton) menuButton.addEventListener('click', function () { setSidebar(!sidebar.classList.contains('open')); });
    if (backdrop) backdrop.addEventListener('click', function () { setSidebar(false); });
    document.querySelectorAll('#primary-navigation a').forEach(function (link) { link.addEventListener('click', function () { setSidebar(false); }); });
    document.addEventListener('keydown', function (event) { if (event.key === 'Escape') setSidebar(false); });
    window.addEventListener('resize', function () { if (window.innerWidth > 760) setSidebar(false); });

    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (tooltipTriggerEl) {
        if (window.bootstrap && bootstrap.Tooltip) new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
