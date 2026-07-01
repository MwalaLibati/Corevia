<?php $oldInput = !empty($old) ? $old : $run; ?>

<div class="d-flex align-items-center justify-content-between mr-bottom-30">
    <div>
        <h2 class="text-dark">Edit Payroll Run</h2>
        <p class="text-gray mb-0">Update payroll cycle details.</p>
    </div>
    <a href="<?= e(base_url('payroll/index')) ?>" class="btn btn-outline-secondary">Back</a>
</div>

<?php if (!empty($flashError)): ?><div class="alert alert-danger"><?= e((string) $flashError) ?></div><?php endif; ?>
<?php if (!empty($flashSuccess)): ?><div class="alert alert-success"><?= e((string) $flashSuccess) ?></div><?php endif; ?>

<?php
    $paymentSummary = $paymentSummary ?? ['payment_count' => 0, 'paid_total' => 0, 'latest_payment_date' => null];
    $paymentOldInput = !empty($paymentOld) ? $paymentOld : [];
    $paidTotal = (float) ($paymentSummary['paid_total'] ?? 0);
    $balanceDue = max(0.0, (float) ($run['total_net'] ?? 0) - $paidTotal);
    $paymentDateDefault = (string) ($paymentOldInput['payment_date'] ?? date('Y-m-d'));
    $paymentAmountDefault = (string) ($paymentOldInput['amount'] ?? ($balanceDue > 0 ? $balanceDue : '0'));
    $paymentMethodDefault = (string) ($paymentOldInput['payment_method'] ?? 'Cash');
    $paymentReferenceDefault = (string) ($paymentOldInput['reference_number'] ?? $nextPaymentReference ?? '');
    $paymentNotesDefault = (string) ($paymentOldInput['notes'] ?? '');
    $isLocked = (int) ($run['is_locked'] ?? 0) === 1;
    $isReversed = !empty($run['reversed_at']);
?>

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="ent-stat-card" style="--ent-stat-accent:#7c3aed">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <span class="stat-label">Total Net</span>
                <span class="stat-icon" style="background:#ede9fe;color:#7c3aed"><i class="bi bi-wallet2"></i></span>
            </div>
            <div class="stat-value" style="font-size:1.25rem"><?= e(format_currency((float) ($run['total_net'] ?? 0))) ?></div>
            <div style="font-size:.74rem;color:var(--ent-text-muted);margin-top:4px">Total payable this run</div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="ent-stat-card" style="--ent-stat-accent:#16a34a">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <span class="stat-label">Paid So Far</span>
                <span class="stat-icon" style="background:#dcfce7;color:#16a34a"><i class="bi bi-check-circle"></i></span>
            </div>
            <div class="stat-value" style="font-size:1.25rem"><?= e(format_currency($paidTotal)) ?></div>
            <div style="font-size:.74rem;color:var(--ent-text-muted);margin-top:4px">Across <?= (int)($paymentSummary['payment_count'] ?? 0) ?> payment(s)</div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="ent-stat-card" style="--ent-stat-accent:<?= $balanceDue <= 0 ? '#16a34a' : '#dc2626' ?>">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <span class="stat-label">Balance Due</span>
                <span class="stat-icon" style="background:<?= $balanceDue <= 0 ? '#dcfce7' : '#fee2e2' ?>;color:<?= $balanceDue <= 0 ? '#16a34a' : '#dc2626' ?>">
                    <i class="bi bi-<?= $balanceDue <= 0 ? 'check2-all' : 'exclamation-circle' ?>"></i>
                </span>
            </div>
            <div class="stat-value" style="font-size:1.25rem"><?= e(format_currency($balanceDue)) ?></div>
            <div style="font-size:.74rem;color:var(--ent-text-muted);margin-top:4px"><?= $balanceDue <= 0 ? 'Fully paid' : 'Outstanding' ?></div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="ent-stat-card" style="--ent-stat-accent:#0284c7">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <span class="stat-label">Payments</span>
                <span class="stat-icon" style="background:#e0f2fe;color:#0284c7"><i class="bi bi-receipt"></i></span>
            </div>
            <div class="stat-value"><?= e((string) ((int) ($paymentSummary['payment_count'] ?? 0))) ?></div>
            <div style="font-size:.74rem;color:var(--ent-text-muted);margin-top:4px">
                Last: <?= e((string) ($paymentSummary['latest_payment_date'] ?? '-')) ?>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div>
                <h5 class="mb-1">Record Payment</h5>
                <p class="text-gray mb-0">Enter a partial amount now and return later to finish the run.</p>
            </div>
            <span class="badge bg-info text-dark">Remaining: <?= e(format_currency($balanceDue)) ?></span>
        </div>

        <?php if ($balanceDue <= 0): ?>
            <div class="alert alert-success mb-0">
                This payroll run is fully paid. No additional payment is required.
            </div>
        <?php else: ?>
        <form method="post" action="<?= e(base_url('payroll/recordPayment/' . (string) $run['id'])) ?>" class="row g-3">
            <input type="hidden" name="_csrf" value="<?= e((string) $csrf) ?>">

            <div class="col-md-3">
                <label class="form-label">Payment Date *</label>
                <input type="date" name="payment_date" class="form-control" value="<?= e($paymentDateDefault) ?>" required>
            </div>

            <div class="col-md-3">
                <label class="form-label">Amount *</label>
                <input type="number" step="0.01" min="0.01" max="<?= e((string) $balanceDue) ?>" name="amount" class="form-control" value="<?= e($paymentAmountDefault) ?>" required>
            </div>

            <div class="col-md-3">
                <label class="form-label">Payment Method</label>
                <?php $methods = ['Cash', 'Bank Transfer', 'Cheque', 'Mobile Money']; ?>
                <select name="payment_method" class="form-select">
                    <?php foreach ($methods as $method): ?>
                        <option value="<?= e($method) ?>" <?= $paymentMethodDefault === $method ? 'selected' : '' ?>><?= e($method) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label">Payment Reference</label>
                <input type="text" name="reference_number" class="form-control" value="<?= e($paymentReferenceDefault) ?>" readonly>
                <small class="text-gray">Auto-generated by the system.</small>
            </div>

            <div class="col-12">
                <label class="form-label">Notes</label>
                <textarea name="notes" class="form-control" rows="2" placeholder="Optional payment note"><?= e($paymentNotesDefault) ?></textarea>
            </div>

            <div class="col-12">
                <button type="submit" class="btn btn-success">Record Payment</button>
                <a href="<?= e(base_url('payroll/index')) ?>" class="btn btn-outline-secondary ms-2">Cancel</a>
            </div>
        </form>
        <?php endif; ?>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <?php
            $currentStatus = (string)($run['status'] ?? 'Draft');
            $userRole      = (string)(current_user()['role'] ?? '');
            $statusColors  = ['Draft'=>'secondary','HR Approved'=>'info','Finance Approved'=>'warning',
                              'Admin Approved'=>'primary','Posted'=>'success','Partially Paid'=>'warning','Paid'=>'success'];
            $sColor        = $statusColors[$currentStatus] ?? 'secondary';

            $workflowSteps = [];
            foreach (($workflow['steps'] ?? []) as $step) {
                $workflowSteps[(int)($step['step_order'] ?? 0)] = $step;
            }
            $userAccess = (string)(current_user()['access_level'] ?? '');
            $approvalMap = [
                'Draft'            => ['label'=>$workflowSteps[1]['action_label'] ?? 'Submit for HR Review',      'role'=>$workflowSteps[1]['required_role'] ?? 'HR Officer'],
                'HR Approved'      => ['label'=>$workflowSteps[2]['action_label'] ?? 'Submit for Finance Review', 'role'=>$workflowSteps[2]['required_role'] ?? 'Finance Officer'],
                'Finance Approved' => ['label'=>$workflowSteps[3]['action_label'] ?? 'Director Approval',         'role'=>$workflowSteps[3]['required_role'] ?? 'Super Admin'],
                'Admin Approved'   => ['label'=>$workflowSteps[4]['action_label'] ?? 'Post Payroll',              'role'=>$workflowSteps[4]['required_role'] ?? 'Finance Officer'],
            ];
            $canApprove = isset($approvalMap[$currentStatus])
                && ($userRole === 'Super Admin' || $userAccess === 'Super Admin' || $userRole === $approvalMap[$currentStatus]['role'] || $userAccess === $approvalMap[$currentStatus]['role']);
            $hasItems   = count($runItems) > 0;
        ?>
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-<?= $sColor ?> fs-6 px-3 py-2"><?= e($currentStatus) ?></span>
                <?php if (!empty($run['approved_by_hr'])): ?>
                    <span class="badge bg-light text-dark border"><i class="bi bi-check-circle-fill text-info me-1"></i>HR Approved</span>
                <?php endif; ?>
                <?php if (!empty($run['approved_by_finance'])): ?>
                    <span class="badge bg-light text-dark border"><i class="bi bi-check-circle-fill text-warning me-1"></i>Finance Approved</span>
                <?php endif; ?>
                <?php if (!empty($run['approved_by_admin'])): ?>
                    <span class="badge bg-light text-dark border"><i class="bi bi-check-circle-fill text-success me-1"></i>Admin Approved</span>
                <?php endif; ?>
                <?php if ($isLocked): ?>
                    <span class="badge bg-dark"><i class="bi bi-lock-fill me-1"></i>Locked</span>
                <?php endif; ?>
                <?php if ($isReversed): ?>
                    <span class="badge bg-danger"><i class="bi bi-arrow-counterclockwise me-1"></i>Reversed</span>
                <?php endif; ?>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <?php if (!$isLocked): ?>
                    <a href="<?= e(base_url('payroll/preview/' . (string) $run['id'])) ?>" class="btn btn-outline-info">
                        <i class="bi bi-eye me-1"></i> Preview Payroll
                    </a>
                <?php endif; ?>
                <?php if (in_array($currentStatus, ['Draft'], true)): ?>
                    <form method="post" action="<?= e(base_url('payroll/process/' . (string) $run['id'])) ?>" class="d-inline">
                        <input type="hidden" name="_csrf" value="<?= e((string) $csrf) ?>">
                        <button type="submit" class="btn btn-outline-success" onclick="return confirm('Re-generate payroll items? Existing items will be replaced.');">
                            <i class="bi bi-arrow-repeat me-1"></i> Generate Items
                        </button>
                    </form>
                <?php endif; ?>
                <?php if ($canApprove && $hasItems): ?>
                    <form method="post" action="<?= e(base_url('payroll/approve/' . (string) $run['id'])) ?>" class="d-inline">
                        <input type="hidden" name="_csrf" value="<?= e((string) $csrf) ?>">
                        <button type="submit" class="btn btn-primary" onclick="return confirm('<?= e($approvalMap[$currentStatus]['label']) ?>?');">
                            <i class="bi bi-check2-circle me-1"></i> <?= e($approvalMap[$currentStatus]['label']) ?>
                        </button>
                    </form>
                <?php endif; ?>
                <?php if ($hasItems && in_array($userRole, ['Super Admin','Finance Officer'], true)): ?>
                    <a href="<?= e(base_url('payroll/bankExport/' . (string) $run['id'])) ?>" class="btn btn-outline-dark">
                        <i class="bi bi-bank me-1"></i> Bank Export CSV
                    </a>
                    <a href="<?= e(base_url('payroll/napsaReturn/' . (string) $run['id'])) ?>" class="btn btn-outline-success">
                        <i class="bi bi-file-earmark-spreadsheet me-1"></i> NAPSA Return
                    </a>
                <?php endif; ?>
                <?php if ($hasItems): ?>
                    <form method="post" action="<?= e(base_url('payroll/emailAllPayslips/' . (string) $run['id'])) ?>" class="d-inline">
                        <input type="hidden" name="_csrf" value="<?= e((string) $csrf) ?>">
                        <button type="submit" class="btn btn-outline-primary" onclick="return confirm('Email payslips to all employees in this payroll run? Employees without email addresses will be skipped.');">
                            <i class="bi bi-envelope-paper me-1"></i> Email All Payslips
                        </button>
                    </form>
                <?php endif; ?>
                <?php if ($isLocked && (int)($run['payslips_released'] ?? 0) !== 1 && $balanceDue <= 0 && in_array($userRole, ['Super Admin','Finance Officer'], true)): ?>
                    <form method="post" action="<?= e(base_url('payroll/releasePayslips/' . (string) $run['id'])) ?>" class="d-inline">
                        <input type="hidden" name="_csrf" value="<?= e((string) $csrf) ?>">
                        <button type="submit" class="btn btn-success" onclick="return confirm('Release payslips to employee portal?');">
                            <i class="bi bi-send-check me-1"></i> Release Payslips
                        </button>
                    </form>
                <?php elseif ((int)($run['payslips_released'] ?? 0) === 1): ?>
                    <span class="badge bg-success align-self-center px-3 py-2"><i class="bi bi-send-check me-1"></i>Payslips Released</span>
                <?php endif; ?>
                <?php if ($isLocked && !$isReversed && $userRole === 'Super Admin'): ?>
                    <button type="button" class="btn btn-outline-danger" data-bs-toggle="collapse" data-bs-target="#reversePayrollBox">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Reverse / Correct
                    </button>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($isLocked && !$isReversed && $userRole === 'Super Admin'): ?>
        <div class="collapse mb-4" id="reversePayrollBox">
            <div class="alert alert-warning">
                Reversal preserves this locked run for audit and creates a new draft correction run. Use this only when a posted payroll needs correction.
            </div>
            <form method="post" action="<?= e(base_url('payroll/reverse/' . (string) $run['id'])) ?>">
                <input type="hidden" name="_csrf" value="<?= e((string) $csrf) ?>">
                <label class="form-label">Reversal Reason *</label>
                <textarea name="reason" class="form-control mb-2" rows="3" required></textarea>
                <button type="submit" class="btn btn-danger" onclick="return confirm('Reverse this locked payroll run and create a correction run?');">Reverse Payroll</button>
            </form>
        </div>
        <?php endif; ?>

        <form method="post" action="<?= e(base_url('payroll/update/' . (string) $run['id'])) ?>" class="row g-3">
            <input type="hidden" name="_csrf" value="<?= e((string) $csrf) ?>">
            <?php
                $editPayPeriod = (string) ($oldInput['pay_period'] ?? date('Y-m'));
                if (!preg_match('/^(20\d{2})-(0[1-9]|1[0-2])$/', $editPayPeriod, $periodParts)) {
                    $periodParts = [0, date('Y'), date('m')];
                }
                $selectedYear = (int)($oldInput['pay_year'] ?? $periodParts[1]);
                $selectedMonth = (int)($oldInput['pay_month'] ?? $periodParts[2]);
                $monthNames = [
                    1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
                    5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
                    9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
                ];
            ?>

            <div class="col-md-4">
                <label class="form-label">Pay Month *</label>
                <select name="pay_month" class="form-select" required <?= $isLocked ? 'disabled' : '' ?>>
                    <?php foreach ($monthNames as $monthNumber => $monthName): ?>
                        <option value="<?= e((string)$monthNumber) ?>" <?= $selectedMonth === $monthNumber ? 'selected' : '' ?>>
                            <?= e($monthName) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if ($isLocked): ?><input type="hidden" name="pay_month" value="<?= e((string)$selectedMonth) ?>"><?php endif; ?>
            </div>

            <div class="col-md-4">
                <label class="form-label">Pay Year *</label>
                <input type="number" name="pay_year" class="form-control" value="<?= e((string)$selectedYear) ?>" min="2000" max="2099" step="1" required <?= $isLocked ? 'disabled' : '' ?>>
                <?php if ($isLocked): ?><input type="hidden" name="pay_year" value="<?= e((string)$selectedYear) ?>"><?php endif; ?>
            </div>

            <div class="col-md-4">
                <label class="form-label">Run Date *</label>
                <input type="date" name="run_date" class="form-control" value="<?= e((string) ($oldInput['run_date'] ?? '')) ?>" required>
            </div>

            <div class="col-md-4">
                <label class="form-label">Status</label>
                <?php $status = (string) ($oldInput['status'] ?? 'Draft'); ?>
                <select name="status" class="form-select">
                    <?php foreach ($statusOptions as $option): ?>
                        <option value="<?= e((string) $option) ?>" <?= $status === $option ? 'selected' : '' ?>><?= e((string) $option) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label">Tax Year</label>
                <select name="tax_year_id" class="form-select" <?= $isLocked ? 'disabled' : '' ?>>
                    <option value="">Auto-match by run date</option>
                    <?php foreach (($taxYears ?? []) as $year): ?>
                        <option value="<?= e((string) $year['id']) ?>" <?= ((string)($oldInput['tax_year_id'] ?? '') === (string)$year['id']) ? 'selected' : '' ?>>
                            <?= e((string) $year['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label">Partial-Month Pay</label>
                <?php $prorationMode = (string)($oldInput['proration_mode'] ?? 'Full Month'); ?>
                <select name="proration_mode" class="form-select" <?= $isLocked ? 'disabled' : '' ?>>
                    <option value="Full Month" <?= $prorationMode === 'Full Month' ? 'selected' : '' ?>>Pay full month</option>
                    <option value="Calendar Days" <?= $prorationMode === 'Calendar Days' ? 'selected' : '' ?>>Prorate by calendar days</option>
                </select>
                <?php if ($isLocked): ?><input type="hidden" name="proration_mode" value="<?= e($prorationMode) ?>"><?php endif; ?>
                <div class="form-text">Uses hire and termination dates within the selected month.</div>
            </div>

            <div class="col-md-4">
                <label class="form-label">Total Gross</label>
                <input type="number" step="0.01" min="0" name="total_gross" class="form-control" value="<?= e((string) ($oldInput['total_gross'] ?? '0')) ?>">
            </div>

            <div class="col-md-4">
                <label class="form-label">Total Deductions</label>
                <input type="number" step="0.01" min="0" name="total_deductions" class="form-control" value="<?= e((string) ($oldInput['total_deductions'] ?? '0')) ?>">
            </div>

            <div class="col-md-4">
                <label class="form-label">Total Net</label>
                <input type="number" step="0.01" min="0" name="total_net" class="form-control" value="<?= e((string) ($oldInput['total_net'] ?? '0')) ?>">
            </div>

            <div class="col-12">
                <?php if (!$isLocked): ?>
                    <button type="submit" class="btn btn-primary">Update Payroll Run</button>
                <?php else: ?>
                    <div class="alert alert-secondary mb-0">This run is locked. Header details are read-only.</div>
                <?php endif; ?>
                <a href="<?= e(base_url('payroll/index')) ?>" class="btn btn-outline-secondary ms-2">Cancel</a>
            </div>
        </form>

        <hr class="my-4">

        <div class="row g-3 mb-3">
            <div class="col-sm-6 col-xl-3">
                <div class="ent-stat-card" style="--ent-stat-accent:#1d4ed8">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <span class="stat-label">Items Generated</span>
                        <span class="stat-icon" style="background:#dbeafe;color:#1d4ed8"><i class="bi bi-people-fill"></i></span>
                    </div>
                    <div class="stat-value"><?= e((string) count($runItems)) ?></div>
                    <div style="font-size:.74rem;color:var(--ent-text-muted);margin-top:4px">Employees in this run</div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="ent-stat-card" style="--ent-stat-accent:#16a34a">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <span class="stat-label">Total Gross</span>
                        <span class="stat-icon" style="background:#dcfce7;color:#16a34a"><i class="bi bi-cash-stack"></i></span>
                    </div>
                    <div class="stat-value" style="font-size:1.25rem"><?= e(format_currency((float)($run['total_gross'] ?? 0))) ?></div>
                    <div style="font-size:.74rem;color:var(--ent-text-muted);margin-top:4px">Before deductions</div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="ent-stat-card" style="--ent-stat-accent:#dc2626">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <span class="stat-label">Total Deductions</span>
                        <span class="stat-icon" style="background:#fee2e2;color:#dc2626"><i class="bi bi-dash-circle"></i></span>
                    </div>
                    <div class="stat-value" style="font-size:1.25rem"><?= e(format_currency((float)($run['total_deductions'] ?? 0))) ?></div>
                    <div style="font-size:.74rem;color:var(--ent-text-muted);margin-top:4px">PAYE + NAPSA + NHIMA + other</div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="ent-stat-card" style="--ent-stat-accent:#7c3aed">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <span class="stat-label">Total Net Pay</span>
                        <span class="stat-icon" style="background:#ede9fe;color:#7c3aed"><i class="bi bi-wallet2"></i></span>
                    </div>
                    <div class="stat-value" style="font-size:1.25rem"><?= e(format_currency((float)($run['total_net'] ?? 0))) ?></div>
                    <div style="font-size:.74rem;color:var(--ent-text-muted);margin-top:4px">Take-home amount</div>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-striped align-middle">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Gross Pay</th>
                        <th>Deductions</th>
                        <th>Net Pay</th>
                        <th>Paid</th>
                        <th>Balance</th>
                        <th>Generated At</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($runItems)): ?>
                        <tr><td colspan="8" class="text-center text-gray">No payroll items generated yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($runItems as $item): ?>
                            <tr>
                                <td>
                                    <div class="fw-semibold"><?= e((string) ($item['employee_name'] ?? '')) ?></div>
                                    <div class="text-gray small"><?= e((string) ($item['employee_number'] ?? '')) ?></div>
                                </td>
                                <td><?= e(format_currency((float) ($item['gross_pay'] ?? 0))) ?></td>
                                <td><?= e(format_currency((float) ($item['total_deductions'] ?? 0))) ?></td>
                                <td><?= e(format_currency((float) ($item['net_pay'] ?? 0))) ?></td>
                                <td class="text-success"><?= e(format_currency((float) ($item['paid_amount'] ?? 0))) ?></td>
                                <td class="<?= (float)($item['balance_due'] ?? 0) > 0 ? 'text-danger' : 'text-success' ?>"><?= e(format_currency((float) ($item['balance_due'] ?? 0))) ?></td>
                                <td><?= e((string) ($item['generated_at'] ?? '-')) ?></td>
                                <td class="text-end">
                                    <a class="btn btn-sm btn-outline-primary" href="<?= e(base_url('payroll/payslip/' . (string) $run['id'] . '/' . (string) ($item['employee_id'] ?? 0))) ?>">View Payslip</a>
                                    <?php if (!$isLocked && $currentStatus === 'Draft' && in_array($userRole, ['Super Admin','Finance Officer'], true)): ?>
                                    <button type="button"
                                            class="btn btn-sm btn-outline-danger"
                                            data-bs-toggle="modal"
                                            data-bs-target="#customDeductionModal"
                                            data-employee-id="<?= e((string) ($item['employee_id'] ?? 0)) ?>"
                                            data-employee-name="<?= e((string) ($item['employee_name'] ?? '')) ?>">
                                        Add Deduction
                                    </button>
                                    <?php endif; ?>
                                    <?php if ((float)($item['balance_due'] ?? 0) > 0 && in_array($userRole, ['Super Admin','Finance Officer'], true)): ?>
                                    <form method="post" action="<?= e(base_url('payroll/recordPayslipPayment/' . (string) $item['id'])) ?>" class="d-inline">
                                        <input type="hidden" name="_csrf" value="<?= e((string) $csrf) ?>">
                                        <input type="hidden" name="run_id" value="<?= e((string) $run['id']) ?>">
                                        <input type="hidden" name="payment_date" value="<?= e(date('Y-m-d')) ?>">
                                        <input type="hidden" name="amount" value="<?= e(number_format((float)($item['balance_due'] ?? 0), 2, '.', '')) ?>">
                                        <input type="hidden" name="payment_method" value="Bank Transfer">
                                        <input type="hidden" name="reference_number" value="<?= e('PS-' . str_pad((string)$item['id'], 6, '0', STR_PAD_LEFT)) ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-success" onclick="return confirm('Record this payslip balance as paid?');">Pay Balance</button>
                                    </form>
                                    <?php endif; ?>
                                    <form method="post" action="<?= e(base_url('payroll/emailPayslip/' . (string) $run['id'] . '/' . (string) ($item['employee_id'] ?? 0))) ?>" class="d-inline">
                                        <input type="hidden" name="_csrf" value="<?= e((string) $csrf) ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-success" onclick="return confirm('Email this payslip to <?= e((string)($item['employee_email'] ?? 'the employee')) ?>?');">
                                            Email
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <hr class="my-4">

        <div class="d-flex align-items-center justify-content-between mb-3">
            <div>
                <h5 class="mb-1">Payroll Adjustments</h5>
                <p class="text-gray mb-0">Once-off deductions added directly on this payroll run.</p>
            </div>
        </div>

        <div class="table-responsive mb-4">
            <table class="table table-striped align-middle">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Type</th>
                        <th>Deduction</th>
                        <th>Amount</th>
                        <th>Reason</th>
                        <th>By</th>
                        <th>At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($adjustmentHistory)): ?>
                        <tr><td colspan="7" class="text-center text-gray">No payroll adjustments have been added.</td></tr>
                    <?php else: ?>
                        <?php foreach ($adjustmentHistory as $adjustment): ?>
                            <tr>
                                <td>
                                    <div class="fw-semibold"><?= e((string) ($adjustment['employee_name'] ?? '-')) ?></div>
                                    <div class="text-gray small"><?= e((string) ($adjustment['employee_number'] ?? '')) ?></div>
                                </td>
                                <td><span class="badge bg-danger"><?= e((string) ($adjustment['adjustment_type'] ?? 'Deduction')) ?></span></td>
                                <td><?= e((string) ($adjustment['label'] ?? '')) ?></td>
                                <td><?= e(format_currency((float) ($adjustment['amount'] ?? 0))) ?></td>
                                <td><?= e((string) ($adjustment['reason'] ?? '')) ?></td>
                                <td><?= e((string) ($adjustment['created_by_name'] ?? '-')) ?></td>
                                <td><?= e((string) ($adjustment['created_at'] ?? '')) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <hr class="my-4">

        <div class="d-flex align-items-center justify-content-between mb-3">
            <div>
                <h5 class="mb-1">Payment History</h5>
                <p class="text-gray mb-0">All payments recorded against this payroll run.</p>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-striped align-middle">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Method</th>
                        <th>Reference</th>
                        <th>Recorded By</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($paymentHistory)): ?>
                        <tr><td colspan="6" class="text-center text-gray">No payments recorded yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($paymentHistory as $payment): ?>
                            <tr>
                                <td><?= e((string) ($payment['payment_date'] ?? '')) ?></td>
                                <td><?= e(format_currency((float) ($payment['amount'] ?? 0))) ?></td>
                                <td><?= e((string) ($payment['payment_method'] ?? '-')) ?></td>
                                <td><?= e((string) ($payment['reference_number'] ?? '-')) ?></td>
                                <td><?= e((string) ($payment['created_by_name'] ?? '-')) ?></td>
                                <td><?= e((string) ($payment['notes'] ?? '-')) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <hr class="my-4">

        <div class="row g-3">
            <div class="col-lg-6">
                <h5 class="mb-1">Calculation History</h5>
                <p class="text-gray mb-3">Generation, recalculation, lock, and reversal audit records.</p>
                <div class="table-responsive">
                    <table class="table table-striped align-middle">
                        <thead>
                            <tr>
                                <th>Action</th>
                                <th>Employee</th>
                                <th>Gross</th>
                                <th>Deductions</th>
                                <th>Net</th>
                                <th>By</th>
                                <th>At</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($calculationHistory)): ?>
                                <tr><td colspan="7" class="text-center text-gray">No calculation history yet.</td></tr>
                            <?php else: ?>
                                <?php foreach ($calculationHistory as $event): ?>
                                    <tr>
                                        <td><?= e((string) ($event['action'] ?? '')) ?></td>
                                        <td>
                                            <?= e((string) ($event['employee_name'] ?? '-')) ?>
                                            <?php if (!empty($event['employee_number'])): ?>
                                                <div class="text-gray small"><?= e((string) $event['employee_number']) ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= e(format_currency((float) ($event['gross_pay'] ?? 0))) ?></td>
                                        <td><?= e(format_currency((float) ($event['total_deductions'] ?? 0))) ?></td>
                                        <td><?= e(format_currency((float) ($event['net_pay'] ?? 0))) ?></td>
                                        <td><?= e((string) ($event['created_by_name'] ?? '-')) ?></td>
                                        <td><?= e((string) ($event['created_at'] ?? '')) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="col-lg-6">
                <h5 class="mb-1">Reversal / Correction History</h5>
                <p class="text-gray mb-3">Posted payroll runs remain preserved after reversal.</p>
                <div class="table-responsive">
                    <table class="table table-striped align-middle">
                        <thead>
                            <tr>
                                <th>Reversed At</th>
                                <th>By</th>
                                <th>Correction Run</th>
                                <th>Reason</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($reversalHistory)): ?>
                                <tr><td colspan="4" class="text-center text-gray">No reversal history.</td></tr>
                            <?php else: ?>
                                <?php foreach ($reversalHistory as $reversal): ?>
                                    <tr>
                                        <td><?= e((string) ($reversal['reversed_at'] ?? '')) ?></td>
                                        <td><?= e((string) ($reversal['reversed_by_name'] ?? '-')) ?></td>
                                        <td><?= e((string) ($reversal['correction_period'] ?? '-')) ?></td>
                                        <td><?= e((string) ($reversal['reason'] ?? '')) ?></td>
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

<?php if (!$isLocked && $currentStatus === 'Draft' && in_array($userRole, ['Super Admin','Finance Officer'], true)): ?>
<div class="modal fade" id="customDeductionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="post" action="<?= e(base_url('payroll/addCustomDeduction/' . (string) $run['id'])) ?>" class="modal-content">
            <input type="hidden" name="_csrf" value="<?= e((string) $csrf) ?>">
            <input type="hidden" name="employee_id" id="customDeductionEmployeeId" value="">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title">Add Custom Deduction</h5>
                    <div class="text-gray small" id="customDeductionEmployeeName">Selected employee</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Deduction Name *</label>
                    <input type="text" name="label" class="form-control" placeholder="Example: Uniform recovery" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Amount *</label>
                    <input type="number" name="amount" class="form-control" step="0.01" min="0.01" required>
                </div>
                <div>
                    <label class="form-label">Reason *</label>
                    <textarea name="reason" class="form-control" rows="3" placeholder="Explain why this deduction is being added." required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-danger">Add Deduction</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var modal = document.getElementById('customDeductionModal');
    if (!modal) { return; }
    modal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        if (!button) { return; }
        var employeeId = button.getAttribute('data-employee-id') || '';
        var employeeName = button.getAttribute('data-employee-name') || 'Selected employee';
        document.getElementById('customDeductionEmployeeId').value = employeeId;
        document.getElementById('customDeductionEmployeeName').textContent = employeeName;
    });
});
</script>
<?php endif; ?>
