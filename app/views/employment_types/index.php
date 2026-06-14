<div class="d-flex align-items-center justify-content-between mr-bottom-30">
    <div>
        <h2 class="text-dark">Employment Types</h2>
        <p class="text-gray mb-0">Configure the employment and contract categories used across employees and contracts.</p>
    </div>
    <a href="<?= e(base_url('employment-type/create')) ?>" class="btn btn-primary">Add Type</a>
</div>

<?php if (!empty($flashSuccess)): ?><div class="alert alert-success"><?= e((string) $flashSuccess) ?></div><?php endif; ?>
<?php if (!empty($flashError)): ?><div class="alert alert-danger"><?= e((string) $flashError) ?></div><?php endif; ?>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="get" action="<?= e(base_url('employment-type/index')) ?>" class="row g-3 align-items-end">
            <div class="col-md-8">
                <label class="form-label">Search</label>
                <input type="text" name="search" class="form-control" value="<?= e((string) ($search ?? '')) ?>" placeholder="Type name or code">
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-outline-primary">Search</button>
                <a href="<?= e(base_url('employment-type/index')) ?>" class="btn btn-outline-secondary">Reset</a>
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
                    <th>Sort</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($types)): ?>
                <tr><td colspan="5" class="text-center text-gray">No employment types found.</td></tr>
            <?php else: ?>
                <?php foreach ($types as $type): ?>
                    <tr>
                        <td><?= e((string) ($type['name'] ?? '')) ?></td>
                        <td><?= e((string) ($type['code'] ?? '-')) ?></td>
                        <td><?= e((string) ($type['sort_order'] ?? '0')) ?></td>
                        <td><?= (int) ($type['is_active'] ?? 1) === 1 ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>' ?></td>
                        <td class="text-end">
                            <a href="<?= e(base_url('employment-type/edit/' . (string) $type['id'])) ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                            <form method="post" action="<?= e(base_url('employment-type/delete/' . (string) $type['id'])) ?>" class="d-inline">
                                <input type="hidden" name="_csrf" value="<?= e((string) $csrf) ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this employment type?');">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
