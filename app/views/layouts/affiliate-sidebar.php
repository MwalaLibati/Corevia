<?php $aff = current_affiliate() ?? []; ?>
<div class="sa-overlay" id="saOverlay"></div>
<div class="sa-sidebar" id="saSidebar">
    <div class="p-4 pb-2">
        <div class="d-flex align-items-center gap-2 mb-1">
            <span style="background:#14b8a6;color:#fff;width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center"><i class="bi bi-diagram-3-fill"></i></span>
            <span style="color:#fff;font-weight:800;font-size:.95rem">Corevia</span>
        </div>
        <div class="sa-badge mt-1">AFFILIATES</div>
    </div>
    <nav class="flex-grow-1 mt-2">
        <div style="color:rgba(255,255,255,.42);font-size:.65rem;font-weight:800;letter-spacing:.8px;padding:8px 22px 4px">OVERVIEW</div>
        <a href="<?= e(base_url('affiliate/dashboard/index')) ?>" class="nav-link d-flex align-items-center"><i class="bi bi-speedometer2"></i>Dashboard</a>
        <a href="<?= e(base_url('affiliate/dashboard/leads')) ?>" class="nav-link d-flex align-items-center"><i class="bi bi-funnel"></i>Referral Leads</a>
        <a href="<?= e(base_url('affiliate/dashboard/companies')) ?>" class="nav-link d-flex align-items-center"><i class="bi bi-buildings"></i>Companies</a>
        <a href="<?= e(base_url('affiliate/dashboard/commissions')) ?>" class="nav-link d-flex align-items-center"><i class="bi bi-wallet2"></i>Commissions</a>
        <a href="<?= e(base_url('affiliate/dashboard/statement')) ?>" class="nav-link d-flex align-items-center"><i class="bi bi-file-earmark-spreadsheet"></i>Statement</a>
        <a href="<?= e(base_url('affiliate/dashboard/agreements')) ?>" class="nav-link d-flex align-items-center"><i class="bi bi-file-earmark-text"></i>Agreements</a>
        <a href="<?= e(base_url('affiliate/dashboard/support')) ?>" class="nav-link d-flex align-items-center"><i class="bi bi-chat-square-text"></i>Support</a>
        <a href="<?= e(base_url('affiliate/dashboard/profile')) ?>" class="nav-link d-flex align-items-center"><i class="bi bi-person-badge"></i>Profile & KYC</a>
    </nav>
    <div class="p-3 border-top" style="border-color:rgba(255,255,255,.1)!important">
        <div style="color:rgba(255,255,255,.7);font-size:.76rem;margin-bottom:6px"><i class="bi bi-person-circle me-1"></i><?= e((string)($aff['full_name'] ?? 'Affiliate')) ?></div>
        <a href="<?= e(base_url('affiliate/auth/logout')) ?>" class="btn btn-sm w-100" style="background:rgba(255,255,255,.12);color:#fff;font-size:.75rem"><i class="bi bi-box-arrow-right me-1"></i>Sign Out</a>
    </div>
</div>
<div class="sa-main">
<div class="sa-topbar">
    <button class="sa-hamburger" id="saHamburger" aria-label="Toggle menu"><i class="bi bi-list"></i></button>
    <span class="sa-badge"><i class="bi bi-stars me-1"></i>Referral Partner</span>
    <span class="text-muted ms-auto d-none d-md-inline" style="font-size:.78rem"><?= e(date('D, d M Y')) ?></span>
</div>
<div class="sa-content">
