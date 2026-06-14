<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">
    <meta name="description" content="<?= e(app_product_name() . ' - ' . app_product_tagline()) ?>">
    <title><?= isset($title) ? e((string) $title) . ' | ' : '' ?><?= e(app_product_name()) ?></title>
    <link rel="shortcut icon" href="<?= e(asset('assets/img/favicon.png')) ?>" type="image/png">
    <link rel="stylesheet" href="<?= e(asset('assets/css/main.css')) ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="<?= e(asset('assets/css/enterprise.css')) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="bg-light layout-vertical-nav kleon-vertical-nav--fullwidth kleon-vertical-nav--active">
<div id="preloader">
    <div class="preloader-inner">
        <div class="spinner"></div>
        <div class="logo"><img src="<?= e(asset('assets/img/Logo.png')) ?>" alt="<?= e(app_product_name()) ?>" style="height: 64px; width: auto;"></div>
    </div>
</div>

<?php
    $authUser    = $_SESSION['auth_user'] ?? [];
    $userName    = (string) ($authUser['name'] ?? 'User');
    $userRole    = (string) ($authUser['role'] ?? '');
    $userInitial = strtoupper(substr($userName, 0, 1));
    $activeCompany = current_company();
    $activeCompanyName = (string) ($activeCompany['name'] ?? 'Company');
    $companyMemberships = $_SESSION['company_memberships'] ?? [];
?>
<!-- Sidebar overlay (mobile) -->
<div class="ent-sidebar-overlay" id="entSidebarOverlay"></div>

<header class="header kleon-default-nav">

    <!-- Mobile bar: visible only on mobile -->
    <div class="ent-mobile-bar">
        <button type="button" class="ent-mob-hamburger" id="entHamburger" aria-label="Toggle menu">
            <i class="bi bi-list"></i>
        </button>
        <span style="font-weight:600;font-size:.88rem;color:var(--ent-text)"><?= e($activeCompanyName) ?></span>
        <div class="d-flex align-items-center gap-2">
            <?php $mobNotifCount = (int) ($notifCount ?? 0); ?>
            <div class="position-relative ent-bell-wrap">
                <button class="btn p-0 border-0 bg-transparent ent-bell-btn" style="color:var(--ent-text-muted);font-size:1.2rem;line-height:1" title="Notifications" id="mobBellBtn" aria-label="Notifications">
                    <i class="bi bi-bell"></i>
                    <?php if ($mobNotifCount > 0): ?><span class="ent-notif-badge"><?= $mobNotifCount > 9 ? '9+' : $mobNotifCount ?></span><?php endif; ?>
                </button>
            </div>
            <div class="ent-header-avatar" title="<?= e($userName) ?>"><?= e($userInitial) ?></div>
            <a href="<?= e(base_url('auth/logout')) ?>" style="color:var(--ent-text-muted);font-size:1.1rem;line-height:1" title="Sign out"><i class="bi bi-box-arrow-right"></i></a>
        </div>
    </div>

    <!-- Desktop bar: visible only on desktop (≥992px) -->
    <div class="header-mobile-option">
        <div class="header-inner d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-2" style="font-size:.8rem">
                <i class="bi bi-building" style="color:var(--ent-blue)"></i>
                <span style="color:var(--ent-text-muted);font-weight:500"><?= e($activeCompanyName) ?></span>
                <span style="color:var(--ent-border)">|</span>
                <span style="color:var(--ent-text-muted)"><?= date('D, d M Y') ?></span>
                <?php if (is_array($companyMemberships) && count($companyMemberships) > 1): ?>
                    <a href="<?= e(base_url('auth/chooseCompany')) ?>" class="btn btn-outline-secondary btn-sm ms-2" style="padding:3px 9px;font-size:.72rem">
                        <i class="bi bi-arrow-left-right"></i> Switch Company
                    </a>
                <?php endif; ?>
            </div>
            <div class="d-flex align-items-center gap-3">
                <?php
                    $deskNotifCount  = (int) ($notifCount ?? 0);
                    $deskNotifList   = $notifRecent ?? [];
                    $typeIconMap     = ['info'=>'bi-info-circle text-primary','success'=>'bi-check-circle text-success','warning'=>'bi-exclamation-triangle text-warning','danger'=>'bi-x-circle text-danger'];
                ?>
                <div class="ent-bell-wrap position-relative" id="deskBellWrap">
                    <button class="btn p-0 border-0 bg-transparent ent-bell-btn" id="deskBellBtn" aria-label="Notifications" title="Notifications">
                        <i class="bi bi-bell" style="font-size:1.15rem;color:var(--ent-text-muted)"></i>
                        <?php if ($deskNotifCount > 0): ?>
                            <span class="ent-notif-badge"><?= $deskNotifCount > 9 ? '9+' : $deskNotifCount ?></span>
                        <?php endif; ?>
                    </button>
                    <div class="ent-notif-dropdown" id="deskNotifDropdown">
                        <div class="ent-notif-header">
                            <span>Notifications</span>
                            <?php if ($deskNotifCount > 0): ?>
                                <form method="post" action="<?= e(base_url('notification/markAll')) ?>" class="m-0">
                                    <input type="hidden" name="_csrf" value="<?= e(Session::csrfToken()) ?>">
                                    <button type="submit" class="ent-notif-markall border-0 bg-transparent p-0">Mark all read</button>
                                </form>
                            <?php endif; ?>
                        </div>
                        <?php if (empty($deskNotifList)): ?>
                            <div class="ent-notif-empty"><i class="bi bi-bell-slash me-2"></i>No notifications</div>
                        <?php else: ?>
                            <ul class="ent-notif-list">
                            <?php foreach ($deskNotifList as $n): ?>
                                <?php $iconClass = $typeIconMap[$n['type']] ?? 'bi-info-circle text-primary'; ?>
                                <li class="ent-notif-item<?= (int)$n['is_read'] === 0 ? ' unread' : '' ?>">
                                    <a href="<?= e(internal_app_url($n['link'] ?? null)) ?>" onclick="fetch('<?= e(base_url('notification/markRead/' . (string)$n['id'])) ?>', {method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: '_csrf=<?= e(rawurlencode(Session::csrfToken())) ?>'})">
                                        <i class="bi <?= $iconClass ?> ent-notif-icon"></i>
                                        <div class="ent-notif-body">
                                            <div class="ent-notif-msg"><?= e((string) $n['message']) ?></div>
                                            <div class="ent-notif-time"><?= e((string) $n['created_at']) ?></div>
                                        </div>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="ent-header-user">
                    <div class="ent-header-avatar"><?= e($userInitial) ?></div>
                    <div>
                        <div class="ent-header-name"><?= e($userName) ?></div>
                        <?php if ($userRole !== ''): ?>
                            <div class="ent-header-role"><?= e($userRole) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                <a href="<?= e(base_url('auth/logout')) ?>" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
                    <i class="bi bi-box-arrow-right"></i> Sign out
                </a>
            </div>
        </div>
    </div>

</header>
