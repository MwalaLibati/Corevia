<div class="d-flex align-items-center justify-content-between mr-bottom-30">
    <div>
        <h2 class="text-dark">Audit Log</h2>
        <p class="text-gray mb-0">Searchable event trail across system, payroll, HR, portal, and billing activity.</p>
    </div>
    <a href="<?= e(base_url('audit/index?export=csv&action=' . urlencode((string)($filters['action'] ?? '')) . '&module=' . urlencode((string)($filters['module'] ?? '')) . '&date_from=' . urlencode((string)($filters['date_from'] ?? '')) . '&date_to=' . urlencode((string)($filters['date_to'] ?? '')))) ?>" class="btn btn-outline-primary">
        <i class="bi bi-download me-1"></i>Export CSV
    </a>
</div>

<?php if (!empty($flashSuccess)): ?><div class="alert alert-success"><?= e((string)$flashSuccess) ?></div><?php endif; ?>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="get" action="<?= e(base_url('audit/index')) ?>" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Action</label>
                <input type="text" name="action" class="form-control" value="<?= e((string)($filters['action'] ?? '')) ?>" placeholder="created, payroll, login">
            </div>
            <div class="col-md-3">
                <label class="form-label">Module</label>
                <input type="text" name="module" class="form-control" value="<?= e((string)($filters['module'] ?? '')) ?>" placeholder="Employee, PayrollRun">
            </div>
            <div class="col-md-2">
                <label class="form-label">From</label>
                <input type="date" name="date_from" class="form-control" value="<?= e((string)($filters['date_from'] ?? '')) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">To</label>
                <input type="date" name="date_to" class="form-control" value="<?= e((string)($filters['date_to'] ?? '')) ?>">
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="<?= e(base_url('audit/index')) ?>" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body table-responsive">
        <table class="table table-sm table-striped align-middle">
            <thead>
                <tr>
                    <th style="width:140px">Time</th>
                    <th>User</th>
                    <th>Action</th>
                    <th>Record</th>
                    <th>Description</th>
                    <th>IP</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($logs)): ?>
                <tr><td colspan="6" class="text-center text-muted">No audit events recorded yet.</td></tr>
            <?php else: ?>
                <?php foreach ($logs as $log):
                    $actionColors = [
                        'payroll_generate' => 'primary', 'payroll_approve' => 'success',
                        'bank_export' => 'dark', 'leave_approve' => 'success',
                        'leave_reject' => 'danger', 'leave_cancel' => 'warning',
                        'leave_create' => 'info', 'advance_create' => 'info',
                        'advance_cancel' => 'warning', 'login' => 'secondary',
                        'logout' => 'secondary',
                    ];
                    $ac = $actionColors[$log['action']] ?? 'secondary';
                    $who = $log['user_type'] === 'employee'
                        ? ($log['employee_name'] ?? 'Employee') . ' (' . ($log['employee_number'] ?? '') . ')'
                        : ($log['admin_name'] ?? 'System');
                ?>
                <tr>
                    <td class="text-muted" style="font-size:.75rem;white-space:nowrap"><?= e((string)$log['created_at']) ?></td>
                    <td>
                        <div style="font-size:.82rem"><?= e($who) ?></div>
                        <span class="badge bg-light text-secondary border" style="font-size:.65rem"><?= e((string)$log['user_type']) ?></span>
                    </td>
                    <td><span class="badge bg-<?= $ac ?>" style="font-size:.72rem"><?= e(str_replace('_', ' ', (string)$log['action'])) ?></span></td>
                    <td>
                        <?php if ($log['model'] && $log['record_id']): ?>
                            <code style="font-size:.72rem"><?= e((string)$log['model']) ?> #<?= (int)$log['record_id'] ?></code>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td style="font-size:.8rem;max-width:350px"><?= e((string)($log['description'] ?? '')) ?></td>
                    <td class="text-muted" style="font-size:.75rem"><?= e((string)($log['ip_address'] ?? '')) ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
