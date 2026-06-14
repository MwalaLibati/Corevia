</div>
</div>
</div>
<script src="<?= e(asset('assets/js/vendor/bootstrap.bundle.min.js')) ?>"></script>
<script src="<?= e(asset('assets/js/table-pagination.js') . '?v=affiliate') ?>"></script>
<script>
(function(){
    const path = window.location.pathname;
    document.querySelectorAll('.sa-sidebar .nav-link').forEach(function(a){
        try { if (path.includes(new URL(a.href).pathname.replace(/\/+$/, ''))) a.classList.add('active'); } catch(e) {}
    });
    const hamburger=document.getElementById('saHamburger'), sidebar=document.getElementById('saSidebar'), overlay=document.getElementById('saOverlay');
    function close(){sidebar?.classList.remove('open');overlay?.classList.remove('open');}
    hamburger?.addEventListener('click',function(){sidebar?.classList.toggle('open');overlay?.classList.toggle('open');});
    overlay?.addEventListener('click',close);
})();
</script>
</body>
</html>
