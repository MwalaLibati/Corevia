<div class="d-flex align-items-center justify-content-between mr-bottom-30">
    <div>
        <h2 class="text-dark">Deductions & Tax</h2>
        <p class="text-gray mb-0">Manage statutory and custom deductions.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= e(base_url('tax-year/index')) ?>" class="btn btn-outline-primary">Tax Years</a>
        <a href="<?= e(base_url('deduction/create')) ?>" class="btn btn-primary">Add Deduction Type</a>
    </div>
</div>

<?php if (!empty($flashSuccess)): ?><div class="alert alert-success"><?= e((string) $flashSuccess) ?></div><?php endif; ?>
<?php if (!empty($flashError)): ?><div class="alert alert-danger"><?= e((string) $flashError) ?></div><?php endif; ?>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="get" action="<?= e(base_url('deduction/index')) ?>" class="row g-3 align-items-end">
            <div class="col-md-8">
                <label class="form-label">Search</label>
                <input type="text" class="form-control" name="search" value="<?= e((string) ($search ?? '')) ?>" placeholder="Name or code">
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button class="btn btn-outline-primary" type="submit">Search</button>
                <a href="<?= e(base_url('deduction/index')) ?>" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body table-responsive">
        <table class="table table-striped align-middle">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Code</th>
                    <th>Statutory</th>
                    <th>Auto-apply</th>
                    <th class="text-center">Active</th>
                    <th>Type</th>
                    <th>Default Value</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($types)): ?>
                <tr><td colspan="8" class="text-center text-gray">No deduction types found.</td></tr>
            <?php else: ?>
                <?php foreach ($types as $type): ?>
                    <tr>
                        <td><?= e((string) ($type['name'] ?? '')) ?></td>
                        <td><?= e((string) ($type['code'] ?? '-')) ?></td>
                        <td><?= (int) ($type['is_statutory'] ?? 0) === 1 ? '<span class="badge bg-warning text-dark">Statutory</span>' : '<span class="text-muted">—</span>' ?></td>
                        <td>
                            <?php if ((int) ($type['auto_apply'] ?? 0) === 1): ?>
                                <span class="badge bg-success"><i class="bi bi-arrow-repeat me-1"></i>Auto</span>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <?php if ((int)($type['is_active'] ?? 1) === 1): ?>
                                <span class="badge bg-success">Active</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td><?= e((string) ($type['calculation_type'] ?? '')) ?></td>
                        <td><?= e((string) ($type['default_value'] ?? '0')) ?><?= (string)($type['calculation_type'] ?? '') === 'Percent' ? '%' : '' ?></td>
                        <td class="text-end">
                            <a href="<?= e(base_url('deduction/edit/' . (string) $type['id'])) ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                            <form method="post" action="<?= e(base_url('deduction/delete/' . (string) $type['id'])) ?>" class="d-inline">
                                <input type="hidden" name="_csrf" value="<?= e((string) $csrf) ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this deduction type?');">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
