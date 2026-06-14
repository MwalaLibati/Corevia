<div class="d-flex align-items-center justify-content-between mr-bottom-30">
    <div>
        <h2 class="text-dark">Roles</h2>
        <p class="text-gray mb-0">Create company roles and map them to a system access profile.</p>
    </div>
    <a href="<?= e(base_url('role/create')) ?>" class="btn btn-primary">Add Role</a>
</div>

<?php if (!empty($flashSuccess)): ?><div class="alert alert-success"><?= e((string) $flashSuccess) ?></div><?php endif; ?>
<?php if (!empty($flashError)): ?><div class="alert alert-danger"><?= e((string) $flashError) ?></div><?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body table-responsive">
        <table class="table table-striped align-middle">
            <thead>
                <tr>
                    <th>Role</th>
                    <th>Access Profile</th>
                    <th>Modules</th>
                    <th>Description</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($roles)): ?>
                <tr><td colspan="5" class="text-center text-gray">No company roles created yet. System roles are still available when assigning users.</td></tr>
            <?php else: ?>
                <?php foreach ($roles as $role): ?>
                    <tr>
                        <td><?= e((string) ($role['name'] ?? '')) ?></td>
                        <td><span class="badge bg-primary-subtle text-primary-emphasis"><?= e((string) ($role['access_level'] ?? 'Viewer')) ?></span></td>
                        <td><?= e((string) ($role['module_count'] ?? 0)) ?></td>
                        <td><?= e((string) ($role['description'] ?? '-')) ?></td>
                        <td class="text-end">
                            <a href="<?= e(base_url('role/edit/' . (string) $role['id'])) ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                            <form method="post" action="<?= e(base_url('role/delete/' . (string) $role['id'])) ?>" class="d-inline">
                                <input type="hidden" name="_csrf" value="<?= e((string) $csrf) ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this role?');">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
