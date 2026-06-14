<div class="d-flex align-items-center justify-content-between mr-bottom-30">
    <div>
        <h2 class="text-dark">Workflow Settings</h2>
        <p class="text-gray mb-0">Configure company approval steps for HR, payroll, and document workflows.</p>
    </div>
</div>

<?php if (!empty($flashSuccess)): ?><div class="alert alert-success"><?= e((string)$flashSuccess) ?></div><?php endif; ?>
<?php if (!empty($flashError)): ?><div class="alert alert-danger"><?= e((string)$flashError) ?></div><?php endif; ?>

<div class="row g-3">
    <?php foreach (($definitions ?? []) as $definition): ?>
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h5 class="mb-1"><?= e((string)$definition['name']) ?></h5>
                            <p class="text-gray mb-0 small"><?= e((string)($definition['description'] ?? '')) ?></p>
                        </div>
                        <span class="badge bg-<?= (int)$definition['is_active'] === 1 ? 'success' : 'secondary' ?>">
                            <?= (int)$definition['is_active'] === 1 ? 'Active' : 'Inactive' ?>
                        </span>
                    </div>

                    <?php foreach (($definition['steps'] ?? []) as $step): ?>
                        <div class="d-flex justify-content-between border-bottom py-2">
                            <div>
                                <strong><?= (int)$step['step_order'] ?>. <?= e((string)$step['step_name']) ?></strong>
                                <div class="text-gray small"><?= e((string)$step['action_label']) ?></div>
                            </div>
                            <span class="badge bg-light text-dark border align-self-start"><?= e((string)$step['required_role']) ?></span>
                        </div>
                    <?php endforeach; ?>

                    <div class="mt-3">
                        <a href="<?= e(base_url('workflow/edit/' . (string)$definition['id'])) ?>" class="btn btn-outline-primary btn-sm">Edit Workflow</a>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
