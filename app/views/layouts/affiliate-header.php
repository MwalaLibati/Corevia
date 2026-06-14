<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e((string)($title ?? 'Affiliate Portal')) ?> | Corevia Affiliates</title>
    <link rel="stylesheet" href="<?= e(asset('assets/css/main.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('assets/css/enterprise.css')) ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root { --sa-accent:#0f766e; --sa-sidebar-w:240px; }
        html,body{height:100%;margin:0} body{background:#f1f5f9;font-family:'Inter',system-ui,sans-serif;overflow:hidden}
        .sa-root{display:flex;height:100vh;overflow:hidden}.sa-sidebar{background:linear-gradient(180deg,#0f172a 0%,#134e4a 100%);width:var(--sa-sidebar-w);flex-shrink:0;display:flex;flex-direction:column;height:100vh;overflow-y:auto}
        .sa-sidebar .nav-link{color:rgba(255,255,255,.76);border-radius:8px;margin:2px 8px;padding:9px 14px;font-size:.84rem;text-decoration:none}.sa-sidebar .nav-link:hover,.sa-sidebar .nav-link.active{color:#fff;background:rgba(255,255,255,.14)}.sa-sidebar .nav-link i{width:18px;margin-right:8px}
        .sa-main{flex:1;display:flex;flex-direction:column;overflow:hidden;min-width:0}.sa-topbar{background:#fff;border-bottom:1px solid #e5e7eb;padding:0 28px;height:52px;display:flex;align-items:center;gap:12px;flex-shrink:0}.sa-content{flex:1;overflow-y:auto;padding:28px 32px}
        .sa-badge{display:inline-flex;align-items:center;gap:4px;background:rgba(15,118,110,.1);color:#0f766e;font-size:.68rem;font-weight:800;padding:3px 10px;border-radius:20px;letter-spacing:.5px}
        .card{border-radius:12px!important}.sa-hamburger{display:none;background:none;border:0;font-size:1.25rem}.sa-overlay{display:none}
        @media(max-width:767.98px){.sa-sidebar{position:fixed;z-index:200;transform:translateX(-100%);transition:.25s}.sa-sidebar.open{transform:translateX(0)}.sa-overlay.open{display:block;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:190}.sa-hamburger{display:block}.sa-content{padding:14px}.sa-topbar{padding:0 14px}}
    </style>
</head>
<body>
<div class="sa-root">
