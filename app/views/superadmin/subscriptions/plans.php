<div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between gap-3 mb-4">
    <div>
        <h2 class="text-dark mb-0 fw-bold">Subscription Plans</h2>
        <p class="text-muted mb-0 mt-1 small">Configure plan pricing and the modules each company receives.</p>
    </div>
    <a href="<?= e(base_url('superadmin/subscription/index')) ?>" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-credit-card me-1"></i>Subscriptions
    </a>
</div>

<?php if (!empty($flash)): ?>
<div class="alert alert-success"><?= e((string) $flash) ?></div>
<?php endif; ?>
<?php if (!empty($flashErr)): ?>
<div class="alert alert-danger"><?= e((string) $flashErr) ?></div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Plan</th>
                        <th>Description</th>
                        <th class="text-end">Default Rate</th>
                        <th>Billing</th>
                        <th class="text-center">Modules</th>
                        <th class="text-center">Status</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach (($plans ?? []) as $plan): ?>
                    <tr>
                        <td class="fw-semibold"><?= e((string) $plan['name']) ?></td>
                        <td class="text-muted" style="max-width:320px"><?= e((string) ($plan['description'] ?? '')) ?></td>
                        <td class="text-end"><?= e((string) $plan['currency']) ?> <?= number_format((float) $plan['default_monthly_rate'], 2) ?></td>
                        <td><?= e((string) $plan['default_billing_cycle']) ?></td>
                        <td class="text-center"><?= (int) $plan['module_count'] ?></td>
                        <td class="text-center">
                            <span class="badge bg-<?= (int) $plan['is_active'] === 1 ? 'success' : 'secondary' ?>">
                                <?= (int) $plan['is_active'] === 1 ? 'Active' : 'Inactive' ?>
                            </span>
                        </td>
                        <td class="text-end">
                            <a href="<?= e(base_url('superadmin/subscription/editPlan/' . (string) $plan['id'])) ?>" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-sliders me-1"></i>Edit
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
