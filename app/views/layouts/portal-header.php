<?php
$portalEmp     = current_employee();
$portalName    = (string) ($portalEmp['full_name']      ?? 'Employee');
$portalDesig   = (string) ($portalEmp['designation']    ?? 'Employee');
$portalInitial = mb_strtoupper(mb_substr($portalName, 0, 1));
$portalEmpNo   = (string) ($portalEmp['employee_number'] ?? '');
$portalCompany = current_company();
$portalCompanyName = (string) ($portalCompany['name'] ?? app_product_name());
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">
    <title><?= isset($title) ? e((string)$title) . ' | ' : '' ?>Employee Portal | <?= e(app_product_name()) ?></title>
    <link rel="shortcut icon" href="<?= e(asset('assets/img/favicon.png')) ?>" type="image/png">
    <link rel="stylesheet" href="<?= e(asset('assets/css/main.css')) ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="<?= e(asset('assets/css/enterprise.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('assets/css/portal.css')) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="bg-light layout-vertical-nav kleon-vertical-nav--fullwidth kleon-vertical-nav--active">

<div id="preloader">
    <div class="preloader-inner">
        <div class="spinner"></div>
        <div class="logo"><img src="<?= e(asset('assets/img/Logo.png')) ?>" alt="<?= e(app_product_name()) ?>" style="height:64px;width:auto;"></div>
    </div>
</div>

<div class="ent-sidebar-overlay" id="entSidebarOverlay"></div>

<header class="header kleon-default-nav">

    <!-- Mobile bar -->
    <div class="ent-mobile-bar">
        <button type="button" class="ent-mob-hamburger" id="entHamburger" aria-label="Toggle menu">
            <i class="bi bi-list"></i>
        </button>
        <span style="font-weight:600;font-size:.88rem;color:var(--ent-text)">Employee Portal</span>
        <div class="d-flex align-items-center gap-2">
            <div class="ent-header-avatar" title="<?= e($portalName) ?>"><?= e($portalInitial) ?></div>
            <a href="<?= e(base_url('portal/logout')) ?>" style="color:var(--ent-text-muted);font-size:1.1rem;line-height:1" title="Sign out"><i class="bi bi-box-arrow-right"></i></a>
        </div>
    </div>

    <!-- Desktop bar -->
    <div class="header-mobile-option">
        <div class="header-inner d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-2" style="font-size:.8rem">
                <i class="bi bi-building" style="color:var(--ent-blue)"></i>
                <span style="color:var(--ent-text-muted);font-weight:500"><?= e($portalCompanyName) ?></span>
                <span style="color:var(--ent-border)">|</span>
                <span style="color:var(--ent-text-muted)"><?= date('D, d M Y') ?></span>
            </div>
            <div class="d-flex align-items-center gap-3">
                <div class="ent-header-user">
                    <div class="ent-header-avatar"><?= e($portalInitial) ?></div>
                    <div>
                        <div class="ent-header-name"><?= e($portalName) ?></div>
                        <div class="ent-header-role"><?= e($portalDesig ?: $portalEmpNo) ?></div>
                    </div>
                </div>
                <a href="<?= e(base_url('portal/logout')) ?>" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
                    <i class="bi bi-box-arrow-right"></i> Sign out
                </a>
            </div>
        </div>
    </div>

</header>
