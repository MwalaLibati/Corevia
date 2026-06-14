<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h2 class="text-dark mb-0">Client Entities</h2>
        <p class="text-muted mb-0 mt-1">Group multiple companies under one client, holding company, franchise, or organization.</p>
    </div>
    <a href="<?= e(base_url('superadmin/client-entity/create')) ?>" class="btn btn-sm" style="background:#7c3aed;color:#fff">
        <i class="bi bi-plus-circle me-1"></i>New Entity
    </a>
</div>

<?php if (!empty($flashSuccess)): ?><div class="alert alert-success"><?= e((string) $flashSuccess) ?></div><?php endif; ?>
<?php if (!empty($flashError)): ?><div class="alert alert-danger"><?= e((string) $flashError) ?></div><?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table align-middle mb-0" style="font-size:.84rem">
                <thead class="table-light">
                    <tr>
                        <th>Entity / Group</th>
                        <th>Code</th>
                        <th>Type</th>
                        <th>Contact</th>
                        <th class="text-center">Companies</th>
                        <th class="text-center">Employees</th>
                        <th class="text-center">Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($entities)): ?>
                    <tr><td colspan="8" class="text-center text-muted py-5">No client entities yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($entities as $entity): ?>
                    <tr>
                        <td>
                            <div class="fw-semibold"><?= e((string) $entity['name']) ?></div>
                            <div class="text-muted" style="font-size:.72rem"><?= e((string) ($entity['email'] ?? $entity['phone'] ?? '')) ?></div>
                        </td>
                        <td><code><?= e((string) ($entity['code'] ?? '-')) ?></code></td>
                        <td><?= e((string) ($entity['entity_type'] ?? 'Group')) ?></td>
                        <td><?= e((string) ($entity['contact_person'] ?? '-')) ?></td>
                        <td class="text-center fw-semibold"><?= (int) ($entity['company_count'] ?? 0) ?></td>
                        <td class="text-center"><?= (int) ($entity['employee_count'] ?? 0) ?></td>
                        <td class="text-center">
                            <?php if ((int) ($entity['is_active'] ?? 1) === 1): ?>
                                <span class="badge bg-success">Active</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="<?= e(base_url('superadmin/client-entity/view/' . (string) $entity['id'])) ?>" class="btn btn-xs btn-outline-primary" style="font-size:.72rem;padding:2px 8px" title="View"><i class="bi bi-eye"></i></a>
                                <a href="<?= e(base_url('superadmin/client-entity/edit/' . (string) $entity['id'])) ?>" class="btn btn-xs btn-outline-secondary" style="font-size:.72rem;padding:2px 8px" title="Edit"><i class="bi bi-pencil"></i></a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
