<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h2 class="text-dark mb-0"><?= e((string) $entity['name']) ?></h2>
        <p class="text-muted mb-0 mt-1"><?= e((string) ($entity['entity_type'] ?? 'Group')) ?> · <code><?= e((string) ($entity['code'] ?? '-')) ?></code></p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= e(base_url('superadmin/client-entity/edit/' . (string) $entity['id'])) ?>" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-pencil me-1"></i>Edit
        </a>
        <a href="<?= e(base_url('superadmin/company/create?client_entity_id=' . (string) $entity['id'])) ?>" class="btn btn-sm" style="background:#7c3aed;color:#fff">
            <i class="bi bi-building-add me-1"></i>Add Company
        </a>
        <a href="<?= e(base_url('superadmin/client-entity/index')) ?>" class="btn btn-sm btn-outline-secondary">Back</a>
    </div>
</div>

<?php if (!empty($flashSuccess)): ?><div class="alert alert-success"><?= e((string) $flashSuccess) ?></div><?php endif; ?>
<?php if (!empty($flashError)): ?><div class="alert alert-danger"><?= e((string) $flashError) ?></div><?php endif; ?>

<div class="row g-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-3">Entity Details</h6>
                <table class="table table-sm mb-0">
                    <tr><td class="text-muted">Contact</td><td class="fw-semibold"><?= e((string) ($entity['contact_person'] ?? '-')) ?></td></tr>
                    <tr><td class="text-muted">Email</td><td><?= e((string) ($entity['email'] ?? '-')) ?></td></tr>
                    <tr><td class="text-muted">Phone</td><td><?= e((string) ($entity['phone'] ?? '-')) ?></td></tr>
                    <tr><td class="text-muted">Status</td><td><?= (int) ($entity['is_active'] ?? 1) === 1 ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>' ?></td></tr>
                    <tr><td class="text-muted">Address</td><td><?= e((string) ($entity['address'] ?? '-')) ?></td></tr>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h6 class="fw-bold mb-0">Companies Under This Entity</h6>
                    <span class="badge bg-secondary-subtle text-secondary-emphasis"><?= count($companies ?? []) ?> company(s)</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Company</th>
                                <th>Slug</th>
                                <th class="text-center">Branches</th>
                                <th class="text-center">Employees</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($companies)): ?>
                            <tr><td colspan="6" class="text-center text-muted py-4">No companies attached to this entity yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($companies as $company): ?>
                            <tr>
                                <td class="fw-semibold"><?= e((string) $company['name']) ?></td>
                                <td><code><?= e((string) $company['slug']) ?></code></td>
                                <td class="text-center"><?= (int) ($company['branch_count'] ?? 0) ?></td>
                                <td class="text-center"><?= (int) ($company['employee_count'] ?? 0) ?></td>
                                <td><?= (int) ($company['is_active'] ?? 1) === 1 ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-danger">Inactive</span>' ?></td>
                                <td class="text-end"><a href="<?= e(base_url('superadmin/company/view/' . (string) $company['id'])) ?>" class="btn btn-sm btn-outline-primary">Open</a></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
