(function () {
    'use strict';

    document.querySelectorAll('[data-password-toggle]').forEach(function (button) {
        button.addEventListener('click', function () {
            var selector = button.getAttribute('data-password-toggle');
            var input = selector ? document.querySelector(selector) : null;
            if (!input) return;

            var showing = input.type === 'text';
            input.type = showing ? 'password' : 'text';
            button.setAttribute('aria-pressed', showing ? 'false' : 'true');
            button.setAttribute('aria-label', showing ? 'Show password' : 'Hide password');

            var icon = button.querySelector('i');
            if (icon) icon.className = showing ? 'bi bi-eye' : 'bi bi-eye-slash';
            input.focus();
        });
    });

    document.querySelectorAll('[data-uppercase]').forEach(function (input) {
        input.addEventListener('input', function () {
            input.value = input.value.toUpperCase();
        });
    });

    if (typeof Swal !== 'undefined') {
        var toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 4500,
            timerProgressBar: true
        });

        document.querySelectorAll('[data-auth-alert]').forEach(function (alert) {
            var text = alert.textContent.trim();
            if (!text) return;
            alert.hidden = true;
            toast.fire({
                icon: alert.classList.contains('auth-alert-danger') ? 'error' : 'success',
                title: text
            });
        });
    }

    document.querySelectorAll('[data-auth-form]').forEach(function (form) {
        form.addEventListener('submit', function () {
            var button = form.querySelector('.auth-submit');
            if (!button || button.disabled) return;
            button.disabled = true;
            var loadingLabel = form.getAttribute('data-loading-label') || 'Signing in...';
            button.innerHTML = '<i class="bi bi-arrow-repeat"></i><span>' + loadingLabel + '</span>';
        });
    });
})();
