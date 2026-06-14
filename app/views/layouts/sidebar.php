<?php
$allowedModules = array_flip(user_allowed_modules());
$catalog = module_catalog();
$sections = [];
foreach ($catalog as $key => $module) {
    if (!isset($allowedModules[$key])) {
        continue;
    }
    $sections[(string) $module['section']][$key] = $module;
}
?>

<div class="kleon-vertical-nav">
    <div class="logo d-flex align-items-center justify-content-between">
        <a href="<?= e(base_url('dashboard/index')) ?>" class="d-flex align-items-center gap-2 flex-shrink-0 text-decoration-none">
            <img src="<?= e(asset('assets/img/Logo.png')) ?>" alt="<?= e(app_product_name()) ?>" style="height:36px;width:auto;border-radius:6px;">
            <div>
                <span class="fw-bold text-nowrap" style="color:#f1f5f9;font-size:.88rem;line-height:1.2;display:block">Stonesoft</span>
                <span style="color:#64748b;font-size:.7rem;font-weight:500">Payroll &amp; HR</span>
            </div>
        </a>
        <button type="button" class="kleon-vertical-nav-toggle"><i class="bi bi-list"></i></button>
    </div>

    <div class="kleon-navmenu">
        <ul class="main-menu" id="ent-nav" style="padding-top:8px">
            <?php foreach ($sections as $section => $items): ?>
                <li class="menu-section-title" style="<?= $section !== 'Overview' ? 'margin-top:8px' : '' ?>"><?= e($section) ?></li>
                <?php foreach ($items as $module): ?>
                    <li class="menu-item">
                        <a href="<?= e(base_url((string) $module['url'])) ?>">
                            <span class="nav-icon"><i class="bi <?= e((string) $module['icon']) ?>"></i></span>
                            <span class="nav-text"><?= e((string) $module['label']) ?></span>
                        </a>
                    </li>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </ul>
    </div>
</div>

<script>
(function(){
    var path = window.location.pathname.replace(/\/+$/, '');
    document.querySelectorAll('#ent-nav .menu-item a').forEach(function(a){
        var href = a.getAttribute('href').replace(/\/+$/, '');
        if (path === href || path.startsWith(href + '/')) {
            a.setAttribute('data-active','true');
            a.closest('.menu-item').classList.add('active');
        }
    });
})();
</script>
