<div class="d-flex align-items-center justify-content-between mr-bottom-30">
    <div>
        <h2 class="text-dark">Onboarding Links</h2>
        <p class="text-gray mb-0">Create secure data capture links and review submitted employee details.</p>
    </div>
    <a href="<?= e(base_url('onboarding/create')) ?>" class="btn btn-primary"><i class="bi bi-person-plus me-1"></i> New Link</a>
</div>

<?php if (!empty($flashSuccess)): ?><div class="alert alert-success"><?= e((string)$flashSuccess) ?></div><?php endif; ?>
<?php if (!empty($flashError)): ?><div class="alert alert-danger"><?= e((string)$flashError) ?></div><?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="get" action="<?= e(base_url('onboarding/index')) ?>" class="row g-2 align-items-end mb-3">
            <div class="col-md-4">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">All statuses</option>
                    <?php foreach (['Sent','Opened','Submitted','Approved','Cancelled','Expired'] as $option): ?>
                        <option value="<?= e($option) ?>" <?= (string)($status ?? '') === $option ? 'selected' : '' ?>><?= e($option) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <button class="btn btn-outline-primary" type="submit">Filter</button>
                <a class="btn btn-outline-secondary" href="<?= e(base_url('onboarding/index')) ?>">Reset</a>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Department</th>
                        <th>Expected Start</th>
                        <th>Status</th>
                        <th>Expires</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($requests)): ?>
                    <tr><td colspan="6" class="text-center text-gray py-4">No onboarding links found.</td></tr>
                <?php endif; ?>
                <?php foreach (($requests ?? []) as $row): ?>
                    <tr>
                        <td>
                            <strong><?= e((string)$row['invited_full_name']) ?></strong>
                            <div class="text-gray small"><?= e((string)($row['invited_email'] ?? $row['invited_phone'] ?? '')) ?></div>
                        </td>
                        <td><?= e((string)($row['department_name'] ?? 'Unassigned')) ?></td>
                        <td><?= e((string)($row['expected_start_date'] ?? '-')) ?></td>
                        <td><span class="badge bg-<?= ($row['status'] ?? '') === 'Submitted' ? 'warning text-dark' : (($row['status'] ?? '') === 'Approved' ? 'success' : 'secondary') ?>"><?= e((string)$row['status']) ?></span></td>
                        <td><?= e((string)($row['expires_at'] ?? '-')) ?></td>
                        <td class="text-end">
                            <a href="<?= e(base_url('onboarding/show/' . (string)$row['id'])) ?>" class="btn btn-sm btn-outline-primary">Open</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
