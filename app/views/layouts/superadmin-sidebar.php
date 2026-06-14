<?php $sa = current_superadmin() ?? []; ?>
<!-- Mobile overlay -->
<div class="sa-overlay" id="saOverlay"></div>
<!-- SuperAdmin Sidebar -->
<div class="sa-sidebar" id="saSidebar">
    <div class="p-4 pb-2">
        <div class="d-flex align-items-center gap-2 mb-1">
            <span style="background:#7c3aed;color:#fff;width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:1rem"><i class="bi bi-shield-lock-fill"></i></span>
            <span style="color:#fff;font-weight:700;font-size:.95rem">Stonesoft</span>
        </div>
        <div class="sa-badge mt-1">PLATFORM ADMIN</div>
    </div>
    <nav class="flex-grow-1 mt-2">
        <div style="color:rgba(255,255,255,.4);font-size:.65rem;font-weight:700;letter-spacing:.8px;padding:8px 22px 4px">OVERVIEW</div>
        <a href="<?= e(base_url('superadmin/dashboard/index')) ?>" class="nav-link d-flex align-items-center"><i class="bi bi-speedometer2"></i>Dashboard</a>

        <div style="color:rgba(255,255,255,.4);font-size:.65rem;font-weight:700;letter-spacing:.8px;padding:12px 22px 4px">TENANTS</div>
        <a href="<?= e(base_url('superadmin/client-entity/index')) ?>" class="nav-link d-flex align-items-center"><i class="bi bi-diagram-3"></i>Client Entities</a>
        <a href="<?= e(base_url('superadmin/company/index')) ?>" class="nav-link d-flex align-items-center"><i class="bi bi-buildings"></i>Companies</a>
        <a href="<?= e(base_url('superadmin/company/create')) ?>" class="nav-link d-flex align-items-center"><i class="bi bi-plus-circle"></i>Add Company</a>

        <div style="color:rgba(255,255,255,.4);font-size:.65rem;font-weight:700;letter-spacing:.8px;padding:12px 22px 4px">BILLING</div>
        <a href="<?= e(base_url('superadmin/saas/index')) ?>" class="nav-link d-flex align-items-center"><i class="bi bi-command"></i>SaaS Operations</a>
        <a href="<?= e(base_url('superadmin/subscription/index')) ?>" class="nav-link d-flex align-items-center"><i class="bi bi-credit-card"></i>Subscriptions</a>
        <a href="<?= e(base_url('superadmin/invoice/index')) ?>" class="nav-link d-flex align-items-center"><i class="bi bi-receipt"></i>Invoices & Payments</a>
        <a href="<?= e(base_url('superadmin/subscription/plans')) ?>" class="nav-link d-flex align-items-center"><i class="bi bi-sliders"></i>Plans & Modules</a>
        <a href="<?= e(base_url('superadmin/subscription/financial')) ?>" class="nav-link d-flex align-items-center"><i class="bi bi-graph-up-arrow"></i>Financial Report</a>
        <a href="<?= e(base_url('superadmin/subscription/create')) ?>" class="nav-link d-flex align-items-center"><i class="bi bi-plus-circle"></i>New Subscription</a>

        <a href="<?= e(base_url('superadmin/invoice/affiliates')) ?>" class="nav-link d-flex align-items-center"><i class="bi bi-people"></i>Corevia Affiliates</a>

        <div style="color:rgba(255,255,255,.4);font-size:.65rem;font-weight:700;letter-spacing:.8px;padding:12px 22px 4px">SECURITY</div>
        <a href="<?= e(base_url('superadmin/platform-admin/index')) ?>" class="nav-link d-flex align-items-center"><i class="bi bi-person-lock"></i>Platform Admins</a>
    </nav>
    <div class="p-3 border-top" style="border-color:rgba(255,255,255,.1)!important">
        <div style="color:rgba(255,255,255,.6);font-size:.76rem;margin-bottom:6px"><i class="bi bi-person-circle me-1"></i><?= e((string)($sa['full_name'] ?? 'Admin')) ?></div>
        <a href="<?= e(base_url('superadmin/auth/logout')) ?>" class="btn btn-sm w-100" style="background:rgba(255,255,255,.12);color:#fff;font-size:.75rem">
            <i class="bi bi-box-arrow-right me-1"></i>Sign Out
        </a>
    </div>
</div>
<!-- Main content wrapper -->
<div class="sa-main">
<div class="sa-topbar">
    <button class="sa-hamburger" id="saHamburger" aria-label="Toggle menu">
        <i class="bi bi-list"></i>
    </button>
    <span class="sa-badge"><i class="bi bi-shield-check me-1"></i>Platform Admin</span>
    <span class="text-muted ms-auto d-none d-md-inline" style="font-size:.78rem"><?= date('D, d M Y') ?></span>
    <a href="<?= e(base_url('superadmin/auth/logout')) ?>"
       class="btn btn-sm d-md-none ms-auto"
       style="background:rgba(124,58,237,.08);color:#7c3aed;font-size:.75rem;border:1px solid rgba(124,58,237,.2)"
       title="Sign Out">
        <i class="bi bi-box-arrow-right"></i>
    </a>
</div>
<div class="sa-content">
