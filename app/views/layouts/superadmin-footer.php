</div><!-- /.sa-content -->
</div><!-- /.sa-main -->
</div><!-- /.sa-root -->

<script src="<?= e(asset('assets/js/vendor/jquery-3.7.1.min.js')) ?>"></script>
<script src="<?= e(asset('assets/js/vendor/bootstrap.bundle.min.js')) ?>"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= e(asset('assets/js/table-pagination.js') . '?v=20260521-analytics') ?>"></script>
<script>
(function(){
    var loader = document.createElement('div');
    loader.className = 'ent-page-loader';
    loader.setAttribute('aria-hidden', 'true');
    document.body.appendChild(loader);
    document.querySelectorAll('form').forEach(function(form){
        form.addEventListener('submit', function(e){
            setTimeout(function(){
                if (!e.defaultPrevented && !form.dataset.noLoader) loader.classList.add('is-visible');
            }, 0);
        });
    });
    const path = window.location.pathname + window.location.search;
    document.querySelectorAll('.sa-sidebar .nav-link').forEach(function(a) {
        try {
            if (a.href && path.includes(new URL(a.href).pathname.replace(/\/+$/, ''))) {
                a.classList.add('active');
            }
        } catch(e) {}
    });

    const hamburger = document.getElementById('saHamburger');
    const sidebar = document.getElementById('saSidebar');
    const overlay = document.getElementById('saOverlay');

    function openSidebar() {
        sidebar?.classList.add('open');
        overlay?.classList.add('open');
        document.body.style.overflow = 'hidden';
        const icon = hamburger?.querySelector('i');
        if (icon) icon.className = 'bi bi-x-lg';
    }

    function closeSidebar() {
        sidebar?.classList.remove('open');
        overlay?.classList.remove('open');
        document.body.style.overflow = '';
        const icon = hamburger?.querySelector('i');
        if (icon) icon.className = 'bi bi-list';
    }

    hamburger?.addEventListener('click', function(e) {
        e.stopPropagation();
        sidebar?.classList.contains('open') ? closeSidebar() : openSidebar();
    });

    overlay?.addEventListener('click', closeSidebar);
    sidebar?.querySelectorAll('.nav-link').forEach(function(a) {
        a.addEventListener('click', function() {
            if (window.innerWidth < 768) closeSidebar();
        });
    });

    window.addEventListener('resize', function() {
        if (window.innerWidth >= 768) closeSidebar();
    });

    if (typeof Swal !== 'undefined') {
        const toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 4500,
            timerProgressBar: true,
            didOpen: function(t) {
                t.addEventListener('mouseenter', Swal.stopTimer);
                t.addEventListener('mouseleave', Swal.resumeTimer);
            }
        });

        document.querySelectorAll('.alert-success, .alert-danger, .alert-warning, .alert-info').forEach(function(el) {
            if (el.querySelector('table') || el.querySelector('ul') || el.querySelector('ol')) return;
            const text = el.innerText.trim();
            if (!text) return;
            let icon = 'info';
            if (el.classList.contains('alert-success')) icon = 'success';
            if (el.classList.contains('alert-danger')) icon = 'error';
            if (el.classList.contains('alert-warning')) icon = 'warning';
            el.style.display = 'none';
            toast.fire({icon: icon, title: text});
        });

        function sweetConfirm(message, onConfirm) {
            Swal.fire({
                title: 'Confirm Action',
                text: message || 'Are you sure?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#7c3aed',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Yes, proceed',
                cancelButtonText: 'Cancel',
                reverseButtons: true,
                focusCancel: true
            }).then(function(result) {
                if (result.isConfirmed && typeof onConfirm === 'function') {
                    onConfirm();
                }
            });
        }

        function extractConfirmMessage(source) {
            const match = String(source || '').match(/confirm\((['"])(.*?)\1\)/);
            return match ? match[2] : 'Are you sure?';
        }

        function proceedFromTrigger(trigger) {
            if (!trigger) return;
            const form = trigger.closest ? trigger.closest('form') : null;
            if (form) {
                trigger.removeAttribute('onclick');
                form.removeAttribute('onsubmit');
                form.submit();
            } else if (trigger.tagName === 'A' && trigger.getAttribute('href') && trigger.getAttribute('href') !== '#') {
                window.location.href = trigger.getAttribute('href');
            }
        }

        window.confirm = function(message) {
            const trigger = document.activeElement;
            setTimeout(function() {
                sweetConfirm(message, function() { proceedFromTrigger(trigger); });
            }, 0);
            return false;
        };

        document.querySelectorAll('[onclick*="confirm("]').forEach(function(el) {
            const message = extractConfirmMessage(el.getAttribute('onclick'));
            el.removeAttribute('onclick');
            el.addEventListener('click', function(e) {
                e.preventDefault();
                sweetConfirm(message, function() { proceedFromTrigger(el); });
            });
        });

        document.querySelectorAll('form[onsubmit*="confirm("]').forEach(function(form) {
            const message = extractConfirmMessage(form.getAttribute('onsubmit'));
            form.removeAttribute('onsubmit');
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                Swal.fire({
                    title: 'Confirm Action',
                    text: message,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#7c3aed',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Yes, proceed',
                    cancelButtonText: 'Cancel',
                    reverseButtons: true,
                    focusCancel: true
                }).then(function(result) {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
        });
    }
})();
</script>
</body>
</html>
