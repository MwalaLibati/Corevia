<div class="d-flex align-items-center justify-content-between mr-bottom-30">
    <div>
        <h2 class="text-dark">Designations</h2>
        <p class="text-gray mb-0">Manage job titles used for employees, onboarding, contracts, payroll, and reports.</p>
    </div>
    <a href="<?= e(base_url('designation/create')) ?>" class="btn btn-primary">Add Designation</a>
</div>

<?php if (!empty($flashSuccess)): ?><div class="alert alert-success"><?= e((string) $flashSuccess) ?></div><?php endif; ?>
<?php if (!empty($flashError)): ?><div class="alert alert-danger"><?= e((string) $flashError) ?></div><?php endif; ?>
<?php if (!empty($setupWarning)): ?><div class="alert alert-warning"><?= e((string) $setupWarning) ?></div><?php endif; ?>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="get" action="<?= e(base_url('designation/index')) ?>" class="row g-3 align-items-end">
            <div class="col-md-8">
                <label class="form-label">Search</label>
                <input type="text" name="search" class="form-control" value="<?= e((string) ($search ?? '')) ?>" placeholder="Designation name, code, or department">
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-outline-primary">Search</button>
                <a href="<?= e(base_url('designation/index')) ?>" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body table-responsive">
        <table class="table table-striped align-middle">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Designation</th>
                    <th>Department</th>
                    <th>Status</th>
                    <th>Description</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($designations)): ?>
                <tr><td colspan="6" class="text-center text-gray">No designations found.</td></tr>
            <?php else: ?>
                <?php foreach ($designations as $designation): ?>
                    <tr>
                        <td><?= e((string) ($designation['code'] ?? '-')) ?></td>
                        <td class="fw-semibold"><?= e((string) ($designation['name'] ?? '')) ?></td>
                        <td><?= e((string) ($designation['department_name'] ?? 'All departments')) ?></td>
                        <td>
                            <?php if ((int) ($designation['is_active'] ?? 0) === 1): ?>
                                <span class="badge bg-success">Active</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td><?= e((string) ($designation['description'] ?? '-')) ?></td>
                        <td class="text-end">
                            <a href="<?= e(base_url('designation/edit/' . (string) $designation['id'])) ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                            <?php if ((int) ($designation['is_active'] ?? 0) === 1): ?>
                                <form method="post" action="<?= e(base_url('designation/delete/' . (string) $designation['id'])) ?>" class="d-inline">
                                    <input type="hidden" name="_csrf" value="<?= e((string) $csrf) ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Deactivate this designation?');">Deactivate</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
