<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h2 class="text-dark mb-0 fw-bold">Platform Admins</h2>
        <p class="text-muted mb-0 mt-1 small">Manage users who can access the Stonesoft platform console.</p>
    </div>
    <a href="<?= e(base_url('superadmin/platform-admin/create')) ?>" class="btn btn-sm text-white" style="background:#7c3aed">
        <i class="bi bi-person-plus me-1"></i>Add Admin
    </a>
</div>

<?php if (!empty($flash)): ?><div class="alert alert-success"><?= e((string) $flash) ?></div><?php endif; ?>
<?php if (!empty($flashErr)): ?><div class="alert alert-danger"><?= e((string) $flashErr) ?></div><?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Last Login</th>
                        <th class="text-center">Status</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach (($admins ?? []) as $admin): ?>
                    <tr>
                        <td class="fw-semibold"><?= e((string) $admin['full_name']) ?></td>
                        <td><?= e((string) $admin['email']) ?></td>
                        <td><?= e((string) ($admin['last_login_at'] ?? 'Never')) ?></td>
                        <td class="text-center"><span class="badge bg-<?= (int) $admin['is_active'] === 1 ? 'success' : 'secondary' ?>"><?= (int) $admin['is_active'] === 1 ? 'Active' : 'Inactive' ?></span></td>
                        <td class="text-end">
                            <a href="<?= e(base_url('superadmin/platform-admin/edit/' . (string) $admin['id'])) ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
