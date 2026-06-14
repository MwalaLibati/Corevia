<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h2 class="text-dark">Salary Advance</h2>
        <p class="text-gray mb-0">Request a salary advance or view your current advance status.</p>
    </div>
    <?php if (!$activeAdvance && !$pendingAdvance): ?>
    <button class="btn btn-primary" type="button" data-bs-toggle="collapse" data-bs-target="#applyAdvanceForm">
        <i class="bi bi-plus-lg me-1"></i> Request Advance
    </button>
    <?php endif; ?>
</div>

<?php if (!empty($flashError)):   ?><div class="alert alert-danger"><?= e((string)$flashError) ?></div><?php endif; ?>
<?php if (!empty($flashSuccess)): ?><div class="alert alert-success"><?= e((string)$flashSuccess) ?></div><?php endif; ?>

<!-- Active advance card -->
<?php if ($activeAdvance): ?>
<div class="alert alert-info d-flex align-items-start gap-3 mb-4">
    <i class="bi bi-info-circle-fill fs-5 mt-1 flex-shrink-0"></i>
    <div>
        <strong>You have an active salary advance.</strong><br>
        <span>Amount: <strong>ZMW <?= number_format((float)$activeAdvance['amount'], 2) ?></strong> &bull;
        Monthly Deduction: <strong>ZMW <?= number_format((float)$activeAdvance['monthly_deduction'], 2) ?></strong> &bull;
        Outstanding: <strong>ZMW <?= number_format((float)$activeAdvance['outstanding_balance'], 2) ?></strong></span>
    </div>
</div>
<?php elseif ($pendingAdvance): ?>
<div class="alert alert-warning d-flex align-items-start gap-3 mb-4">
    <i class="bi bi-hourglass-split fs-5 mt-1 flex-shrink-0"></i>
    <div>
        <strong>Your advance request is pending approval.</strong><br>
        Amount requested: <strong>ZMW <?= number_format((float)$pendingAdvance['amount'], 2) ?></strong>
        — submitted <?= e(date('d M Y', strtotime((string)$pendingAdvance['created_at']))) ?>
    </div>
</div>
<?php endif; ?>

<!-- Request form (collapsed) -->
<?php if (!$activeAdvance && !$pendingAdvance): ?>
<div class="collapse mb-4" id="applyAdvanceForm">
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <h5 class="mb-3"><i class="bi bi-cash-coin me-2 text-primary"></i>New Advance Request</h5>
            <form method="post" action="<?= e(base_url('portal/salaryAdvanceApply')) ?>" class="row g-3">
                <input type="hidden" name="_csrf" value="<?= e((string)$csrf) ?>">

                <div class="col-md-4">
                    <label class="form-label">Amount Requested (ZMW) *</label>
                    <input type="number" name="amount" class="form-control" required min="100" step="0.01"
                           placeholder="e.g. 2000.00">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Preferred Monthly Repayment (ZMW) *</label>
                    <input type="number" name="monthly_deduction" class="form-control" required min="50" step="0.01"
                           placeholder="e.g. 500.00">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Preferred Start Date *</label>
                    <input type="date" name="start_date" class="form-control" required
                           min="<?= date('Y-m-d') ?>">
                </div>
                <div class="col-12">
                    <label class="form-label">Reason <span class="text-muted">(optional)</span></label>
                    <textarea name="reason" class="form-control" rows="2"
                              placeholder="Briefly describe the purpose of this advance…"></textarea>
                </div>
                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-send me-1"></i>Submit Request</button>
                    <button type="button" class="btn btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#applyAdvanceForm">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- History table -->
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <h6 class="fw-bold mb-3">Advance History</h6>
        <?php if (empty($advances)): ?>
            <p class="text-muted mb-0"><i class="bi bi-info-circle me-1"></i>No salary advance records found.</p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped align-middle">
                <thead>
                    <tr>
                        <th>Amount</th>
                        <th>Monthly Deduction</th>
                        <th>Outstanding</th>
                        <th>Start Date</th>
                        <th class="text-center">Status</th>
                        <th>Approved By</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($advances as $a):
                    $sc = ['Pending'=>'warning text-dark','Active'=>'primary','Completed'=>'success','Cancelled'=>'secondary'][$a['status']] ?? 'secondary';
                ?>
                <tr>
                    <td>ZMW <?= number_format((float)$a['amount'], 2) ?></td>
                    <td>ZMW <?= number_format((float)$a['monthly_deduction'], 2) ?></td>
                    <td>ZMW <?= number_format((float)$a['outstanding_balance'], 2) ?></td>
                    <td><?= e((string)$a['start_date']) ?></td>
                    <td class="text-center"><span class="badge bg-<?= $sc ?>"><?= e((string)$a['status']) ?></span></td>
                    <td><?= e((string)($a['approved_by_name'] ?? '—')) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>
