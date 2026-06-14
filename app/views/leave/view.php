<?php
$statusClasses = ['Pending'=>'warning text-dark','Approved'=>'success','Rejected'=>'danger','Cancelled'=>'secondary'];
$sc = $statusClasses[$request['status']] ?? 'secondary';
$balance = $balance ?? ['entitled_days'=>0,'used_days'=>0,'balance'=>0];
$canAction = in_array(current_user()['role'] ?? '', ['Super Admin','HR Officer'], true);
?>

<div class="d-flex align-items-center justify-content-between mr-bottom-30">
    <div>
        <h2 class="text-dark">Leave Request</h2>
        <p class="text-gray mb-0"><?= e((string)$request['employee_name']) ?> &bull; <code><?= e((string)$request['employee_number']) ?></code></p>
    </div>
    <div class="d-flex gap-2">
        <span class="badge bg-<?= $sc ?> fs-6 px-3 py-2"><?= e((string)$request['status']) ?></span>
        <a href="<?= e(base_url('leave/index')) ?>" class="btn btn-outline-secondary">Back</a>
    </div>
</div>

<?php if (!empty($flashSuccess)): ?><div class="alert alert-success"><?= e((string)$flashSuccess) ?></div><?php endif; ?>
<?php if (!empty($flashError)): ?><div class="alert alert-danger"><?= e((string)$flashError) ?></div><?php endif; ?>

<div class="row g-4">
    <div class="col-md-8">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <h6 class="fw-bold mb-3">Request Details</h6>
                <table class="table table-sm">
                    <tr><td class="text-muted" style="width:35%">Employee</td><td><strong><?= e((string)$request['employee_name']) ?></strong> (<?= e((string)$request['employee_number']) ?>)</td></tr>
                    <tr><td class="text-muted">Department</td><td><?= e((string)($request['department_name'] ?? '—')) ?></td></tr>
                    <tr><td class="text-muted">Designation</td><td><?= e((string)($request['designation'] ?? '—')) ?></td></tr>
                    <tr><td class="text-muted">Leave Type</td><td><?= e((string)$request['leave_type_name']) ?> <?= (int)($request['is_paid'] ?? 1) ? '<span class="badge bg-success-subtle text-success-emphasis">Paid</span>' : '<span class="badge bg-secondary">Unpaid</span>' ?></td></tr>
                    <tr><td class="text-muted">Start Date</td><td><?= e((string)$request['start_date']) ?></td></tr>
                    <tr><td class="text-muted">End Date</td><td><?= e((string)$request['end_date']) ?></td></tr>
                    <tr><td class="text-muted">Days Requested</td><td><strong><?= number_format((float)$request['total_days'], 1) ?></strong></td></tr>
                    <tr><td class="text-muted">Reason</td><td><?= e((string)($request['reason'] ?? '—')) ?></td></tr>
                    <?php if ($request['status'] !== 'Pending'): ?>
                        <tr><td class="text-muted">Actioned By</td><td><?= e((string)($request['approved_by_name'] ?? '—')) ?></td></tr>
                    <?php endif; ?>
                    <?php if ($request['status'] === 'Rejected' && !empty($request['rejection_reason'])): ?>
                        <tr><td class="text-muted">Rejection Reason</td><td class="text-danger"><?= e((string)$request['rejection_reason']) ?></td></tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>

        <?php if ($request['status'] === 'Pending' && $canAction): ?>
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h6 class="fw-bold mb-3">Action Request</h6>
                <form method="post" action="<?= e(base_url('leave/approve/' . (string)$request['id'])) ?>" class="row g-3">
                    <input type="hidden" name="_csrf" value="<?= e((string)$csrf) ?>">
                    <div class="col-12">
                        <label class="form-label">Rejection Reason <span class="text-muted">(required only when rejecting)</span></label>
                        <textarea name="rejection_reason" class="form-control" rows="2" placeholder="Reason for rejection…"></textarea>
                    </div>
                    <div class="col-12 d-flex gap-2">
                        <button type="submit" name="action" value="approve" class="btn btn-success"
                                onclick="return confirm('Approve this leave request?')">
                            <i class="bi bi-check-circle me-1"></i> Approve
                        </button>
                        <button type="submit" name="action" value="reject" class="btn btn-danger"
                                onclick="return confirm('Reject this leave request?')">
                            <i class="bi bi-x-circle me-1"></i> Reject
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h6 class="fw-bold mb-3">Leave Balance <small class="text-muted fw-normal">(<?= date('Y', strtotime((string)$request['start_date'])) ?>)</small></h6>
                <?php
                    $entitled = (float)$balance['entitled_days'];
                    $used     = (float)$balance['used_days'];
                    $rem      = max(0, $entitled - $used);
                    $pct      = $entitled > 0 ? round(($used / $entitled) * 100) : 0;
                ?>
                <div class="mb-2 d-flex justify-content-between">
                    <small class="text-muted">Entitled</small>
                    <strong><?= number_format($entitled, 1) ?> days</strong>
                </div>
                <div class="mb-2 d-flex justify-content-between">
                    <small class="text-muted">Used</small>
                    <strong class="text-danger"><?= number_format($used, 1) ?> days</strong>
                </div>
                <div class="mb-3 d-flex justify-content-between">
                    <small class="text-muted">Remaining</small>
                    <strong class="text-success"><?= number_format($rem, 1) ?> days</strong>
                </div>
                <div class="progress" style="height:8px">
                    <div class="progress-bar bg-warning" style="width:<?= $pct ?>%"></div>
                </div>
                <div class="text-muted mt-1" style="font-size:.75rem"><?= $pct ?>% used</div>
            </div>
        </div>
    </div>
</div>
