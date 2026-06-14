<div class="d-flex align-items-center justify-content-between mr-bottom-30">
    <div>
        <h2 class="text-dark">User Management & Roles</h2>
        <p class="text-gray mb-0">Manage users who can access this company.</p>
    </div>
    <a href="<?= e(base_url('user-management/create')) ?>" class="btn btn-primary">Add User</a>
</div>

<?php if (!empty($flashSuccess)): ?><div class="alert alert-success"><?= e((string) $flashSuccess) ?></div><?php endif; ?>
<?php if (!empty($flashError)): ?><div class="alert alert-danger"><?= e((string) $flashError) ?></div><?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body table-responsive">
        <table class="table table-striped align-middle">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Roles</th>
                    <th>Status</th>
                    <th>Last Login</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($users)): ?>
                <tr><td colspan="6" class="text-center text-gray">No users found.</td></tr>
            <?php else: ?>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?= e((string) ($user['full_name'] ?? '')) ?></td>
                        <td><?= e((string) ($user['email'] ?? '')) ?></td>
                        <td><?= e((string) ($user['roles'] ?? '-')) ?></td>
                        <td><?= (int) ($user['is_active'] ?? 0) === 1 ? 'Active' : 'Inactive' ?></td>
                        <td><?= e((string) ($user['last_login_at'] ?? '-')) ?></td>
                        <td class="text-end">
                            <a href="<?= e(base_url('user-management/edit/' . (string) $user['id'])) ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
