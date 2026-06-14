<div class="d-flex align-items-center justify-content-between mr-bottom-30">
    <div>
        <h2 class="text-dark">Leave Management</h2>
        <p class="text-gray mb-0">View and manage employee leave requests.</p>
    </div>
    <?php if (in_array(current_user()['role'] ?? '', ['Super Admin','HR Officer'], true)): ?>
        <a href="<?= e(base_url('leave/create')) ?>" class="btn btn-primary"><i class="bi bi-plus-circle me-1"></i> New Request</a>
    <?php endif; ?>
</div>

<?php if (!empty($flashSuccess)): ?><div class="alert alert-success"><?= e((string)$flashSuccess) ?></div><?php endif; ?>
<?php if (!empty($flashError)): ?><div class="alert alert-danger"><?= e((string)$flashError) ?></div><?php endif; ?>

<div class="mb-3 d-flex gap-2">
    <a href="<?= e(base_url('leave/index')) ?>" class="btn btn-sm <?= $tab !== 'pending' ? 'btn-primary' : 'btn-outline-secondary' ?>">All Requests</a>
    <a href="<?= e(base_url('leave/index?tab=pending')) ?>" class="btn btn-sm <?= $tab === 'pending' ? 'btn-warning text-dark' : 'btn-outline-warning' ?>">
        Pending <?= $pendingCount > 0 ? '<span class="badge bg-danger ms-1">' . $pendingCount . '</span>' : '' ?>
    </a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body table-responsive">
        <table class="table table-striped align-middle">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Leave Type</th>
                    <th>From</th>
                    <th>To</th>
                    <th class="text-center">Days</th>
                    <th class="text-center">Status</th>
                    <th>Approved By</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($requests)): ?>
                <tr><td colspan="8" class="text-center text-gray">No leave requests found.</td></tr>
            <?php else: ?>
                <?php foreach ($requests as $req):
                    $statusClasses = ['Pending'=>'warning text-dark','Approved'=>'success','Rejected'=>'danger','Cancelled'=>'secondary'];
                    $sc = $statusClasses[$req['status']] ?? 'secondary';
                ?>
                <tr>
                    <td>
                        <div class="fw-semibold"><?= e((string)$req['employee_name']) ?></div>
                        <div class="text-muted small"><?= e((string)$req['employee_number']) ?></div>
                    </td>
                    <td>
                        <?= e((string)$req['leave_type_name']) ?>
                        <?= (int)($req['is_paid'] ?? 1) ? '<span class="badge bg-success-subtle text-success-emphasis ms-1">Paid</span>' : '<span class="badge bg-secondary-subtle text-secondary-emphasis ms-1">Unpaid</span>' ?>
                    </td>
                    <td><?= e((string)$req['start_date']) ?></td>
                    <td><?= e((string)$req['end_date']) ?></td>
                    <td class="text-center fw-bold"><?= number_format((float)$req['total_days'], 1) ?></td>
                    <td class="text-center"><span class="badge bg-<?= $sc ?>"><?= e((string)$req['status']) ?></span></td>
                    <td><?= e((string)($req['approved_by_name'] ?? '—')) ?></td>
                    <td class="text-end">
                        <a href="<?= e(base_url('leave/view/' . (string)$req['id'])) ?>" class="btn btn-sm btn-outline-primary">View</a>
                        <?php if ($req['status'] === 'Pending' && in_array(current_user()['role'] ?? '', ['Super Admin','HR Officer'], true)): ?>
                            <form method="post" action="<?= e(base_url('leave/cancel/' . (string)$req['id'])) ?>" class="d-inline">
                                <input type="hidden" name="_csrf" value="<?= e((string)$csrf) ?>">
                                <button class="btn btn-sm btn-outline-secondary" onclick="return confirm('Cancel this request?')">Cancel</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
