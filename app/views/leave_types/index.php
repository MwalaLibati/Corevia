<div class="d-flex align-items-center justify-content-between mr-bottom-30">
    <div>
        <h2 class="text-dark">Leave Types</h2>
        <p class="text-gray mb-0">Configure leave categories, yearly entitlement, and paid/unpaid behavior.</p>
    </div>
    <a href="<?= e(base_url('leave-type/create')) ?>" class="btn btn-primary">Add Leave Type</a>
</div>

<?php if (!empty($flashSuccess)): ?><div class="alert alert-success"><?= e((string) $flashSuccess) ?></div><?php endif; ?>
<?php if (!empty($flashError)): ?><div class="alert alert-danger"><?= e((string) $flashError) ?></div><?php endif; ?>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="get" action="<?= e(base_url('leave-type/index')) ?>" class="row g-3 align-items-end">
            <div class="col-md-8">
                <label class="form-label">Search</label>
                <input type="text" name="search" class="form-control" value="<?= e((string) ($search ?? '')) ?>" placeholder="Leave type or code">
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-outline-primary">Search</button>
                <a href="<?= e(base_url('leave-type/index')) ?>" class="btn btn-outline-secondary">Reset</a>
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
                    <th>Days / Year</th>
                    <th>Paid</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($types)): ?>
                <tr><td colspan="6" class="text-center text-gray">No leave types found.</td></tr>
            <?php else: ?>
                <?php foreach ($types as $type): ?>
                    <tr>
                        <td><?= e((string) ($type['name'] ?? '')) ?></td>
                        <td><?= e((string) ($type['code'] ?? '-')) ?></td>
                        <td><?= e((string) ($type['days_per_year'] ?? '0')) ?></td>
                        <td><?= (int) ($type['is_paid'] ?? 0) === 1 ? '<span class="badge bg-success">Paid</span>' : '<span class="badge bg-secondary">Unpaid</span>' ?></td>
                        <td><?= (int) ($type['is_active'] ?? 1) === 1 ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>' ?></td>
                        <td class="text-end">
                            <a href="<?= e(base_url('leave-type/edit/' . (string) $type['id'])) ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                            <form method="post" action="<?= e(base_url('leave-type/delete/' . (string) $type['id'])) ?>" class="d-inline">
                                <input type="hidden" name="_csrf" value="<?= e((string) $csrf) ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this leave type?');">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
