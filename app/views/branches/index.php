<div class="d-flex align-items-center justify-content-between mr-bottom-30">
    <div>
        <h2 class="text-dark">Branches</h2>
        <p class="text-gray mb-0">Manage company locations, campuses, offices, or operating branches.</p>
    </div>
    <a href="<?= e(base_url('branch/create')) ?>" class="btn btn-primary">Add Branch</a>
</div>

<?php if (!empty($flashSuccess)): ?><div class="alert alert-success"><?= e((string) $flashSuccess) ?></div><?php endif; ?>
<?php if (!empty($flashError)): ?><div class="alert alert-danger"><?= e((string) $flashError) ?></div><?php endif; ?>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="get" action="<?= e(base_url('branch/index')) ?>" class="row g-3 align-items-end">
            <div class="col-md-8">
                <label class="form-label">Search</label>
                <input type="text" name="search" class="form-control" value="<?= e((string) ($search ?? '')) ?>" placeholder="Branch name, code, or city">
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-outline-primary">Search</button>
                <a href="<?= e(base_url('branch/index')) ?>" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body table-responsive">
        <table class="table table-striped align-middle">
            <thead>
                <tr>
                    <th>Branch</th>
                    <th>Code</th>
                    <th>City</th>
                    <th>Manager</th>
                    <th class="text-center">Employees</th>
                    <th class="text-center">Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($branches)): ?>
                <tr><td colspan="7" class="text-center text-gray">No branches found.</td></tr>
            <?php else: ?>
                <?php foreach ($branches as $branch): ?>
                    <tr>
                        <td>
                            <div class="fw-semibold"><?= e((string) ($branch['name'] ?? '')) ?></div>
                            <div class="text-gray small"><?= e((string) ($branch['email'] ?? $branch['phone'] ?? '')) ?></div>
                        </td>
                        <td><?= e((string) ($branch['code'] ?? '-')) ?></td>
                        <td><?= e((string) ($branch['city'] ?? '-')) ?></td>
                        <td><?= e((string) ($branch['manager_name'] ?? '-')) ?></td>
                        <td class="text-center fw-semibold"><?= (int) ($branch['employee_count'] ?? 0) ?></td>
                        <td class="text-center">
                            <?php if ((int) ($branch['is_active'] ?? 1) === 1): ?>
                                <span class="badge bg-success">Active</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <a href="<?= e(base_url('branch/edit/' . (string) $branch['id'])) ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                            <form method="post" action="<?= e(base_url('branch/delete/' . (string) $branch['id'])) ?>" class="d-inline">
                                <input type="hidden" name="_csrf" value="<?= e((string) $csrf) ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this branch?');">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
