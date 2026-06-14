<script src="<?= e(asset('assets/js/jquery-3.6.0.min.js')) ?>"></script>
<script src="<?= e(asset('assets/js/bootstrap.bundle.min.js')) ?>"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= e(asset('assets/js/snippets.js')) ?>"></script>
<script src="<?= e(asset('assets/js/theme.js')) ?>"></script>
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
    /* ── Active nav highlight ── */
    var path = window.location.pathname;
    document.querySelectorAll('#portal-nav .menu-item a').forEach(function(a){
        try {
            var aPath = new URL(a.getAttribute('href')||'', window.location.origin).pathname.replace(/\/+$/,'');
            var cPath = path.replace(/\/+$/,'');
            if (aPath.length > 1 && (cPath === aPath || cPath.startsWith(aPath + '/'))) {
                a.setAttribute('data-active','true');
                a.closest('.menu-item').classList.add('active');
            }
        } catch(e){}
    });

    /* ── Mobile sidebar ── */
    var MOBILE_BP = 992;
    var body      = document.body;
    var hamburger = document.getElementById('entHamburger');
    var overlay   = document.getElementById('entSidebarOverlay');
    var nav       = document.querySelector('.kleon-vertical-nav');

    function isMobile(){ return window.innerWidth < MOBILE_BP; }
    function openSidebar(){ body.classList.add('ent-sidebar-open'); if(hamburger) hamburger.querySelector('i').className='bi bi-x-lg'; }
    function closeSidebar(){ body.classList.remove('ent-sidebar-open'); if(hamburger) hamburger.querySelector('i').className='bi bi-list'; }

    if(isMobile()){ closeSidebar(); }
    if(hamburger){ hamburger.addEventListener('click', function(e){ e.stopPropagation(); body.classList.contains('ent-sidebar-open') ? closeSidebar() : openSidebar(); }); }
    if(overlay){ overlay.addEventListener('click', closeSidebar); }
    if(nav){ nav.querySelectorAll('.menu-item a').forEach(function(a){ a.addEventListener('click', function(){ if(isMobile()){ closeSidebar(); } }); }); }
    window.addEventListener('resize', function(){ if(!isMobile()){ closeSidebar(); } });

    /* ── SweetAlert2 flash toasts ── */
    if (typeof Swal !== 'undefined') {
        var entToast = Swal.mixin({
            toast: true, position: 'top-end', showConfirmButton: false,
            timer: 5000, timerProgressBar: true,
            customClass: { popup: 'ent-swal-toast' },
            didOpen: function(t){ t.addEventListener('mouseenter', Swal.stopTimer); t.addEventListener('mouseleave', Swal.resumeTimer); }
        });

        document.querySelectorAll('.alert-success, .alert-danger, .alert-warning, .alert-info').forEach(function(el){
            if (el.querySelector('table') || el.querySelector('ul') || el.querySelector('ol')) return;
            var text = el.innerText.trim();
            if (!text) return;
            var icon = 'info';
            if (el.classList.contains('alert-success')) icon = 'success';
            if (el.classList.contains('alert-danger'))  icon = 'error';
            if (el.classList.contains('alert-warning')) icon = 'warning';
            el.style.display = 'none';
            entToast.fire({ icon: icon, title: text });
        });

        function sweetConfirm(message, onConfirm) {
            Swal.fire({
                title: 'Confirm Action', text: message || 'Are you sure?', icon: 'question',
                showCancelButton: true, confirmButtonColor: '#1d4ed8', cancelButtonColor: '#64748b',
                confirmButtonText: 'Yes, proceed', cancelButtonText: 'Cancel',
                reverseButtons: true, focusCancel: true, customClass: { popup: 'ent-swal-popup' }
            }).then(function(result){
                if (result.isConfirmed && typeof onConfirm === 'function') {
                    onConfirm();
                }
            });
        }

        function extractConfirmMessage(source) {
            var match = String(source || '').match(/confirm\((['"])(.*?)\1\)/);
            return match ? match[2] : 'Are you sure?';
        }

        function proceedFromTrigger(trigger) {
            if (!trigger) return;
            var form = trigger.closest ? trigger.closest('form') : null;
            if (form) {
                trigger.removeAttribute('onclick');
                form.removeAttribute('onsubmit');
                form.submit();
            } else if (trigger.tagName === 'A' && trigger.getAttribute('href') && trigger.getAttribute('href') !== '#') {
                window.location.href = trigger.getAttribute('href');
            }
        }

        window.confirm = function(message) {
            var trigger = document.activeElement;
            setTimeout(function(){
                sweetConfirm(message, function(){ proceedFromTrigger(trigger); });
            }, 0);
            return false;
        };

        document.querySelectorAll('[onclick*="confirm("]').forEach(function(el){
            var message = extractConfirmMessage(el.getAttribute('onclick'));
            el.removeAttribute('onclick');
            el.addEventListener('click', function(e){
                e.preventDefault();
                sweetConfirm(message, function(){ proceedFromTrigger(el); });
            });
        });

        document.querySelectorAll('form[onsubmit*="confirm("]').forEach(function(form){
            var message = extractConfirmMessage(form.getAttribute('onsubmit'));
            form.removeAttribute('onsubmit');
            form.addEventListener('submit', function(e){
                e.preventDefault();
                Swal.fire({
                    title: 'Confirm Action', text: message, icon: 'question',
                    showCancelButton: true, confirmButtonColor: '#1d4ed8', cancelButtonColor: '#64748b',
                    confirmButtonText: 'Yes, proceed', cancelButtonText: 'Cancel',
                    reverseButtons: true, focusCancel: true, customClass: { popup: 'ent-swal-popup' }
                }).then(function(result){
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
