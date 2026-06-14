<div class="d-flex align-items-center justify-content-between mr-bottom-30">
    <div>
        <h2 class="text-dark">Statutory Report</h2>
        <p class="text-gray mb-0">Choose one statutory item at a time and review employee/employer contributions clearly.</p>
    </div>
    <div class="d-flex gap-2">
        <?php $query = http_build_query(array_filter(['run_id' => (int)($runId ?? 0) ?: null, 'period' => (string)($period ?? '') ?: null, 'statutory_code' => (string)($statutoryCode ?? 'NAPSA')])); ?>
        <a href="<?= e(base_url('report/statutory?export=csv' . ($query !== '' ? '&' . $query : ''))) ?>" class="btn btn-outline-primary">Export CSV</a>
        <a href="<?= e(base_url('report/statutory?export=filing' . ($query !== '' ? '&' . $query : ''))) ?>" class="btn btn-outline-dark">Filing CSV</a>
        <a href="<?= e(base_url('report/index')) ?>" class="btn btn-outline-secondary">Back</a>
    </div>
</div>

<?php $cards = $cards ?? ['employee_total' => 0, 'employer_total' => 0, 'combined_total' => 0, 'employee_count' => 0]; ?>
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="ent-stat-card" style="--ent-stat-accent:#dc2626">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <span class="stat-label">Employee Contribution</span>
                <span class="stat-icon" style="background:#fee2e2;color:#dc2626"><i class="bi bi-person-dash"></i></span>
            </div>
            <div class="stat-value" style="font-size:1.25rem"><?= e(format_currency((float)$cards['employee_total'])) ?></div>
            <div style="font-size:.74rem;color:var(--ent-text-muted);margin-top:4px"><?= e((string)($statutoryCode ?? '')) ?> deducted from employees</div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="ent-stat-card" style="--ent-stat-accent:#0f172a">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <span class="stat-label">Employer Contribution</span>
                <span class="stat-icon" style="background:#e2e8f0;color:#0f172a"><i class="bi bi-building-check"></i></span>
            </div>
            <div class="stat-value" style="font-size:1.25rem"><?= e(format_currency((float)$cards['employer_total'])) ?></div>
            <div style="font-size:.74rem;color:var(--ent-text-muted);margin-top:4px">Company contribution</div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="ent-stat-card" style="--ent-stat-accent:#16a34a">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <span class="stat-label">Total Payable</span>
                <span class="stat-icon" style="background:#dcfce7;color:#16a34a"><i class="bi bi-cash-coin"></i></span>
            </div>
            <div class="stat-value" style="font-size:1.25rem"><?= e(format_currency((float)$cards['combined_total'])) ?></div>
            <div style="font-size:.74rem;color:var(--ent-text-muted);margin-top:4px">Employee + employer</div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="ent-stat-card" style="--ent-stat-accent:#2563eb">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <span class="stat-label">Employees</span>
                <span class="stat-icon" style="background:#dbeafe;color:#2563eb"><i class="bi bi-people"></i></span>
            </div>
            <div class="stat-value"><?= e((string)((int)$cards['employee_count'])) ?></div>
            <div style="font-size:.74rem;color:var(--ent-text-muted);margin-top:4px">Employees with selected statutory item</div>
        </div>
    </div>
</div>

<?php if ((int)($runId ?? 0) > 0): ?>
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-0 pt-4 px-4">
        <h5 class="mb-1">Payment Tracking</h5>
        <p class="text-gray mb-0">Record whether this statutory obligation has been paid, including the payment reference used with the authority.</p>
    </div>
    <div class="card-body">
        <form method="post" action="<?= e(base_url('report/recordStatutoryPayment')) ?>" class="row g-3">
            <input type="hidden" name="_csrf" value="<?= e((string)($csrf ?? '')) ?>">
            <input type="hidden" name="run_id" value="<?= e((string)(int)($runId ?? 0)) ?>">
            <input type="hidden" name="statutory_code" value="<?= e((string)($statutoryCode ?? 'NAPSA')) ?>">
            <div class="col-md-3">
                <label class="form-label">Status</label>
                <?php $paymentStatus = (string)($payment['status'] ?? 'Pending'); ?>
                <select name="status" class="form-select">
                    <?php foreach (['Pending','Paid','Partially Paid','Overdue','Cancelled'] as $status): ?>
                        <option value="<?= e($status) ?>" <?= $paymentStatus === $status ? 'selected' : '' ?>><?= e($status) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Payment Date</label>
                <input type="date" name="payment_date" class="form-control" value="<?= e((string)($payment['payment_date'] ?? '')) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Reference</label>
                <input type="text" name="payment_reference" class="form-control" value="<?= e((string)($payment['payment_reference'] ?? '')) ?>" placeholder="Bank / authority reference">
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button class="btn btn-primary w-100" type="submit">Save Payment Status</button>
            </div>
            <div class="col-12">
                <label class="form-label">Notes</label>
                <textarea name="notes" rows="2" class="form-control"><?= e((string)($payment['notes'] ?? '')) ?></textarea>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="get" action="<?= e(base_url('report/statutory')) ?>" class="row g-2 mb-3">
            <div class="col-md-3">
                <label class="form-label">Statutory Item</label>
                <select name="statutory_code" class="form-select">
                    <?php foreach (($statutoryOptions ?? ['NAPSA','PAYE','NHIMA']) as $option): ?>
                        <option value="<?= e((string)$option) ?>" <?= (string)($statutoryCode ?? 'NAPSA') === (string)$option ? 'selected' : '' ?>>
                            <?= e((string)$option) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-5">
                <label class="form-label">Payroll Run</label>
                <select name="run_id" class="form-select">
                    <option value="">All payroll runs</option>
                    <?php foreach (($runs ?? []) as $run): ?>
                        <option value="<?= e((string)$run['id']) ?>" <?= (int)($runId ?? 0) === (int)$run['id'] ? 'selected' : '' ?>>
                            <?= e((string)$run['pay_period']) ?> - <?= e((string)$run['run_date']) ?> (<?= e((string)$run['status']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Period</label>
                <input type="text" name="period" class="form-control" value="<?= e((string)($period ?? '')) ?>" placeholder="Filter by pay period, e.g. 2026-05">
            </div>
            <div class="col-auto align-self-end">
                <button class="btn btn-outline-primary" type="submit">Filter</button>
            </div>
        </form>
        <div class="table-responsive">
        <table class="table table-striped align-middle mb-0">
            <thead>
                <tr>
                    <th>Deduction</th>
                    <th>Code</th>
                    <th>Category</th>
                    <th>Employees</th>
                    <th>Total Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($statutory)): ?>
                    <tr>
                        <td colspan="5" class="text-center text-gray">No deduction data found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($statutory as $item): ?>
                        <tr>
                            <td class="fw-semibold"><?= e((string) ($item['deduction_name'] ?? '')) ?></td>
                            <td><?= e((string) ($item['deduction_code'] ?? '')) ?></td>
                            <td>
                                <span class="badge bg-<?= (($item['deduction_category'] ?? '') === 'statutory_employer') ? 'dark' : 'danger' ?>">
                                    <?= e((string) ($item['deduction_category'] ?? '')) ?>
                                </span>
                            </td>
                            <td><?= e((string) ((int) ($item['employee_count'] ?? 0))) ?></td>
                            <td><?= e(format_currency((float) ($item['total_amount'] ?? 0))) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mt-4">
    <div class="card-header bg-white border-0 pt-4 px-4">
        <h5 class="mb-1">Employee Statutory Details</h5>
        <p class="text-gray mb-0">Use this to confirm each employee has the right NAPSA number, TPIN, NRC, base amount, and statutory deduction before filing.</p>
    </div>
    <div class="card-body table-responsive">
        <table class="table table-striped align-middle mb-0">
            <thead>
                <tr>
                    <th>Period</th>
                    <th>Employee</th>
                    <th>NAPSA No.</th>
                    <th>TPIN</th>
                    <th>NRC</th>
                    <th>Code</th>
                    <th>Category</th>
                    <th>Base</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($details)): ?>
                <tr><td colspan="9" class="text-center text-gray">No employee statutory lines found.</td></tr>
            <?php else: ?>
                <?php foreach ($details as $row): ?>
                    <tr>
                        <td><?= e((string)($row['pay_period'] ?? '')) ?></td>
                        <td>
                            <div class="fw-semibold"><?= e((string)($row['full_name'] ?? '')) ?></div>
                            <small class="text-gray"><?= e((string)($row['employee_number'] ?? '')) ?></small>
                        </td>
                        <td><?= trim((string)($row['napsa_number'] ?? '')) !== '' ? e((string)$row['napsa_number']) : '<span class="badge bg-danger">Missing</span>' ?></td>
                        <td><?= trim((string)($row['tpin'] ?? '')) !== '' ? e((string)$row['tpin']) : '<span class="badge bg-warning text-dark">Missing</span>' ?></td>
                        <td><?= trim((string)($row['nrc_number'] ?? '')) !== '' ? e((string)$row['nrc_number']) : '<span class="badge bg-warning text-dark">Missing</span>' ?></td>
                        <td><?= e((string)($row['deduction_code'] ?? '')) ?></td>
                        <td><span class="badge bg-<?= (($row['deduction_category'] ?? '') === 'statutory_employer') ? 'dark' : 'danger' ?>"><?= e((string)($row['deduction_category'] ?? '')) ?></span></td>
                        <td><?= e(format_currency((float)($row['calculation_base'] ?? 0))) ?></td>
                        <td><?= e(format_currency((float)($row['amount'] ?? 0))) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
