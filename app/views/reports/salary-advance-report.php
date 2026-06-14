<?php
$totals = ['amount' => 0.0, 'outstanding' => 0.0];
foreach ($rows as $r) { $totals['amount'] += (float)$r['amount']; $totals['outstanding'] += (float)$r['outstanding_balance']; }
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h2 class="text-dark">Salary Advance Report</h2>
        <p class="text-gray mb-0">All employee salary advances with current status.</p>
    </div>
    <div class="d-flex gap-2">
        <form method="get" action="<?= e(base_url('report/salaryAdvanceReport')) ?>" class="d-flex gap-2">
            <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">All Statuses</option>
                <?php foreach (['Pending','Active','Completed','Cancelled'] as $s): ?>
                <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= $s ?></option>
                <?php endforeach; ?>
            </select>
        </form>
        <a href="<?= e(base_url('report/salaryAdvanceReport?status='.urlencode($status).'&export=csv')) ?>" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-download me-1"></i>CSV
        </a>
        <a href="<?= e(base_url('report/index')) ?>" class="btn btn-sm btn-outline-primary">← Reports</a>
    </div>
</div>

<!-- Summary cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="ent-stat-card" style="--ent-stat-accent:#1d4ed8">
            <div class="stat-label">Total Records</div>
            <div class="stat-value"><?= count($rows) ?></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="ent-stat-card" style="--ent-stat-accent:#7c3aed">
            <div class="stat-label">Total Advanced</div>
            <div class="stat-value" style="font-size:1rem">ZMW <?= number_format($totals['amount'], 2) ?></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="ent-stat-card" style="--ent-stat-accent:#d97706">
            <div class="stat-label">Outstanding Balance</div>
            <div class="stat-value" style="font-size:1rem">ZMW <?= number_format($totals['outstanding'], 2) ?></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="ent-stat-card" style="--ent-stat-accent:#16a34a">
            <div class="stat-label">Recovered</div>
            <div class="stat-value" style="font-size:1rem">ZMW <?= number_format($totals['amount'] - $totals['outstanding'], 2) ?></div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Employee</th>
                        <th>Department</th>
                        <th class="text-end">Amount</th>
                        <th class="text-end">Monthly Ded.</th>
                        <th class="text-end">Outstanding</th>
                        <th>Start Date</th>
                        <th class="text-center">Status</th>
                        <th>Approved By</th>
                        <th>Requested</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="9" class="text-center text-muted py-5">No records found.</td></tr>
                <?php else: foreach ($rows as $r):
                    $sc = ['Pending'=>'warning text-dark','Active'=>'primary','Completed'=>'success','Cancelled'=>'secondary'][$r['status']] ?? 'secondary';
                ?>
                <tr>
                    <td>
                        <div class="fw-semibold"><?= e((string)$r['employee_name']) ?></div>
                        <div class="text-muted" style="font-size:.76rem"><?= e((string)$r['employee_number']) ?></div>
                    </td>
                    <td><?= e((string)$r['department_name']) ?></td>
                    <td class="text-end">ZMW <?= number_format((float)$r['amount'],2) ?></td>
                    <td class="text-end">ZMW <?= number_format((float)$r['monthly_deduction'],2) ?></td>
                    <td class="text-end fw-semibold <?= (float)$r['outstanding_balance'] > 0 ? 'text-danger' : '' ?>">
                        ZMW <?= number_format((float)$r['outstanding_balance'],2) ?>
                    </td>
                    <td><?= e((string)$r['start_date']) ?></td>
                    <td class="text-center"><span class="badge bg-<?= $sc ?>"><?= e((string)$r['status']) ?></span></td>
                    <td><?= e((string)($r['approved_by_name'] ?? '—')) ?></td>
                    <td style="font-size:.8rem"><?= e(date('d M Y', strtotime((string)$r['created_at']))) ?></td>
                </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
