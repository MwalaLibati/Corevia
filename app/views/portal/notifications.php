<div class="portal-page-header">
    <h2><i class="bi bi-bell me-2"></i>Notifications</h2>
    <p>Recent payroll, leave, contract, and profile update alerts.</p>
</div>

<div class="portal-card">
    <?php if (empty($notifications)): ?>
        <div class="text-center py-5 text-muted">
            <i class="bi bi-bell-slash fs-1 d-block mb-3"></i>
            No notifications yet.
        </div>
    <?php else: ?>
        <div class="list-group list-group-flush">
            <?php foreach ($notifications as $item): ?>
                <?php
                $type = (string) ($item['type'] ?? 'info');
                $icon = match ($type) {
                    'success' => 'bi-check-circle text-success',
                    'warning' => 'bi-exclamation-triangle text-warning',
                    'danger' => 'bi-x-circle text-danger',
                    default => 'bi-info-circle text-primary',
                };
                ?>
                <a class="list-group-item list-group-item-action px-0" href="<?= e((string) ($item['link'] ?? '#')) ?>">
                    <div class="d-flex align-items-start gap-3">
                        <i class="bi <?= e($icon) ?> fs-5"></i>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between gap-3 flex-wrap">
                                <strong><?= e((string) ($item['title'] ?? 'Notification')) ?></strong>
                                <span class="text-muted small"><?= e(!empty($item['date']) ? date('d M Y', strtotime((string) $item['date'])) : '') ?></span>
                            </div>
                            <div class="text-muted small"><?= e((string) ($item['message'] ?? '')) ?></div>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
