<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e((string)($title ?? 'Platform Admin')) ?> | Stonesoft SuperAdmin</title>
    <link rel="stylesheet" href="<?= e(asset('assets/css/main.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('assets/css/enterprise.css')) ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= e(asset('vendor/sweetalert2/sweetalert2.min.css')) ?>">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
    <style>
        /* ── Variables ─────────────────────────────────── */
        :root { --sa-accent: #7c3aed; --sa-accent-dark: #5b21b6; --sa-sidebar-w: 240px; }

        /* ── Base ───────────────────────────────────────── */
        html, body { height: 100%; margin: 0; }
        body { background: #f1f5f9; font-family: 'Inter', system-ui, sans-serif; overflow: hidden; }

        /* ── Root flex shell ────────────────────────────── */
        .sa-root { display: flex; height: 100vh; overflow: hidden; position: relative; }

        /* ── Sidebar ────────────────────────────────────── */
        .sa-sidebar {
            background: linear-gradient(180deg, #1e1b4b 0%, #312e81 100%);
            width: var(--sa-sidebar-w); flex-shrink: 0;
            display: flex; flex-direction: column;
            height: 100vh; overflow-y: auto;
            transition: transform .28s cubic-bezier(.4,0,.2,1);
            z-index: 200;
        }
        .sa-sidebar .nav-link {
            color: rgba(255,255,255,.75); border-radius: 8px;
            margin: 2px 8px; padding: 8px 14px; font-size: .83rem;
            display: flex; align-items: center; text-decoration: none;
            transition: background .15s;
        }
        .sa-sidebar .nav-link:hover,
        .sa-sidebar .nav-link.active { color: #fff; background: rgba(255,255,255,.14); }
        .sa-sidebar .nav-link i { width: 18px; margin-right: 8px; flex-shrink: 0; }

        /* ── Main area ──────────────────────────────────── */
        .sa-main { flex: 1; display: flex; flex-direction: column; overflow: hidden; min-width: 0; }

        /* ── Topbar ─────────────────────────────────────── */
        .sa-topbar {
            background: #fff; border-bottom: 1px solid #e5e7eb;
            padding: 0 28px; height: 52px;
            display: flex; align-items: center; gap: 12px;
            flex-shrink: 0; position: sticky; top: 0; z-index: 150;
        }

        /* ── Content ────────────────────────────────────── */
        .sa-content { flex: 1; overflow-y: auto; padding: 28px 32px; }

        /* ── Misc ───────────────────────────────────────── */
        .sa-badge {
            display: inline-flex; align-items: center; gap: 4px;
            background: rgba(124,58,237,.1); color: #7c3aed;
            font-size: .68rem; font-weight: 700; padding: 2px 10px;
            border-radius: 20px; letter-spacing: .5px; white-space: nowrap;
        }
        .card { border-radius: 12px !important; }

        /* ── Hamburger (hidden on desktop) ──────────────── */
        .sa-hamburger {
            display: none; align-items: center; justify-content: center;
            width: 36px; height: 36px; flex-shrink: 0;
            background: none; border: none; border-radius: 8px;
            cursor: pointer; color: #374151; font-size: 1.25rem;
            transition: background .15s;
        }
        .sa-hamburger:hover { background: #f1f5f9; }

        /* ── Mobile overlay ─────────────────────────────── */
        .sa-overlay {
            display: none; position: fixed; inset: 0;
            background: rgba(0,0,0,.45); z-index: 190;
            backdrop-filter: blur(1px);
        }
        .sa-overlay.open { display: block; }

        /* ── Mobile breakpoint (≤ 767px) ────────────────── */
        @media (max-width: 767.98px) {
            .sa-sidebar {
                position: fixed; top: 0; left: 0;
                transform: translateX(-100%);
                height: 100vh; box-shadow: none;
            }
            .sa-sidebar.open {
                transform: translateX(0);
                box-shadow: 6px 0 32px rgba(0,0,0,.3);
            }
            .sa-hamburger { display: flex; }
            .sa-topbar { padding: 0 14px; gap: 8px; }
            .sa-content { padding: 14px; }

            /* Stack columns on xs */
            .sa-mobile-stack .col-6 { flex: 0 0 50%; max-width: 50%; }
        }

        /* ── Tablet breakpoint (768px – 991px) ──────────── */
        @media (min-width: 768px) and (max-width: 991.98px) {
            :root { --sa-sidebar-w: 220px; }
            .sa-content { padding: 20px 22px; }
            .sa-topbar { padding: 0 20px; }
        }

        /* ── Responsive table fix ───────────────────────── */
        @media (max-width: 767.98px) {
            .table-responsive-sa table { font-size: .73rem; }
            .table-responsive-sa th,
            .table-responsive-sa td { padding: 8px 10px !important; }
        }
    </style>
</head>
<body>
<div class="sa-root">
