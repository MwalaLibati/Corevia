<div class="kleon-vertical-nav">
    <div class="logo d-flex align-items-center justify-content-between">
        <a href="<?= e(base_url('portal/dashboard')) ?>" class="d-flex align-items-center gap-2 flex-shrink-0 text-decoration-none">
            <img src="<?= e(asset('assets/img/Logo.png')) ?>" alt="<?= e(app_product_name()) ?>" style="height:36px;width:auto;border-radius:6px;">
            <div>
                <span class="fw-bold text-nowrap" style="color:#f1f5f9;font-size:.88rem;line-height:1.2;display:block">Employee Portal</span>
                <span style="color:#64748b;font-size:.7rem;font-weight:500">Stonesoft Self-Service</span>
            </div>
        </a>
        <button type="button" class="kleon-vertical-nav-toggle"><i class="bi bi-list"></i></button>
    </div>

    <div class="kleon-navmenu">
        <ul class="main-menu" id="portal-nav" style="padding-top:8px">

            <li class="menu-section-title">Overview</li>
            <li class="menu-item"><a href="<?= e(base_url('portal/dashboard')) ?>"><span class="nav-icon"><i class="bi bi-house-door"></i></span><span class="nav-text">Dashboard</span></a></li>

            <li class="menu-section-title" style="margin-top:8px">My Records</li>
            <li class="menu-item"><a href="<?= e(base_url('portal/payslips')) ?>"><span class="nav-icon"><i class="bi bi-receipt"></i></span><span class="nav-text">My Payslips</span></a></li>
            <li class="menu-item"><a href="<?= e(base_url('portal/contract')) ?>"><span class="nav-icon"><i class="bi bi-file-earmark-text"></i></span><span class="nav-text">My Contract</span></a></li>
            <li class="menu-item"><a href="<?= e(base_url('portal/leave')) ?>"><span class="nav-icon"><i class="bi bi-calendar-heart"></i></span><span class="nav-text">My Leave</span></a></li>
            <li class="menu-item"><a href="<?= e(base_url('portal/salaryAdvance')) ?>"><span class="nav-icon"><i class="bi bi-cash-coin"></i></span><span class="nav-text">Salary Advance</span></a></li>
            <li class="menu-item"><a href="<?= e(base_url('portal/documents')) ?>"><span class="nav-icon"><i class="bi bi-folder"></i></span><span class="nav-text">My Documents</span></a></li>
            <li class="menu-item"><a href="<?= e(base_url('portal/lifecycle')) ?>"><span class="nav-icon"><i class="bi bi-diagram-3"></i></span><span class="nav-text">Lifecycle</span></a></li>
            <li class="menu-item"><a href="<?= e(base_url('portal/notifications')) ?>"><span class="nav-icon"><i class="bi bi-bell"></i></span><span class="nav-text">Notifications</span></a></li>
            <li class="menu-item"><a href="<?= e(base_url('portal/announcements')) ?>"><span class="nav-icon"><i class="bi bi-megaphone"></i></span><span class="nav-text">Noticeboard</span></a></li>

            <li class="menu-section-title" style="margin-top:8px">Account</li>
            <li class="menu-item"><a href="<?= e(base_url('portal/profile')) ?>"><span class="nav-icon"><i class="bi bi-person"></i></span><span class="nav-text">My Profile</span></a></li>
            <li class="menu-item"><a href="<?= e(base_url('portal/changePassword')) ?>"><span class="nav-icon"><i class="bi bi-key"></i></span><span class="nav-text">Change Password</span></a></li>
            <li class="menu-item"><a href="<?= e(base_url('portal/logout')) ?>"><span class="nav-icon"><i class="bi bi-box-arrow-right"></i></span><span class="nav-text">Sign Out</span></a></li>

        </ul>
    </div>
</div>

<script>
(function(){
    var path = window.location.pathname.replace(/\/+$/, '');
    document.querySelectorAll('#portal-nav .menu-item a').forEach(function(a){
        var href = a.getAttribute('href').replace(/\/+$/, '');
        if (path === href || path.startsWith(href + '/')) {
            a.setAttribute('data-active','true');
            a.closest('.menu-item').classList.add('active');
        }
    });
})();
</script>
