<?php
$period     = (string)($slip['pay_period'] ?? $slip['run_date'] ?? '');
$runDate    = (string)($slip['run_date'] ?? '');
$grossPay   = (float)($slip['gross_pay'] ?? 0);
$totalDed   = (float)($slip['total_deductions'] ?? 0);
$netPay     = (float)($slip['net_pay'] ?? 0);
$empName    = (string)($slip['full_name'] ?? ($emp['full_name'] ?? ''));
$empNo      = (string)($slip['employee_number'] ?? ($emp['employee_number'] ?? ''));
$dept       = (string)($slip['department_name'] ?? '');
$desig      = (string)($slip['designation'] ?? ($emp['designation'] ?? ''));
$basicSal   = 0.0;
foreach (($earnings ?? []) as $earningLine) {
    if (($earningLine['category'] ?? '') === 'basic') { $basicSal = (float) $earningLine['amount']; break; }
}
$company    = current_company() ?? [];
$companyName = (string)($company['name'] ?? app_product_name());
$companyLogo = company_logo_url($company);
?>

<div class="portal-page-header d-flex align-items-center justify-content-between flex-wrap gap-2">
    <div>
        <h2><i class="bi bi-receipt me-2"></i>Payslip</h2>
        <p><?= e($period) ?></p>
    </div>
    <div class="d-flex gap-2 no-print">
        <a href="<?= e(base_url('portal/payslips')) ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
        <button onclick="window.print()" class="btn btn-success btn-sm">
            <i class="bi bi-printer me-1"></i> Print / Save PDF
        </button>
    </div>
</div>

<div class="portal-payslip">
    <!-- Header -->
    <div class="portal-payslip-header">
        <img src="<?= e($companyLogo) ?>" alt="<?= e($companyName) ?> logo" style="max-height:68px;max-width:140px;object-fit:contain;margin-bottom:8px">
        <div style="font-size:1rem;font-weight:700;color:var(--portal-green);letter-spacing:.5px"><?= e(strtoupper($companyName)) ?></div>
        <h3>EMPLOYEE PAY SLIP</h3>
        <div style="font-size:.8rem;color:#6b7280">Pay Period: <strong><?= e($period) ?></strong> &bull; Date: <strong><?= e($runDate) ?></strong></div>
    </div>

    <!-- Employee info -->
    <div class="row g-3 mb-4" style="font-size:.85rem">
        <div class="col-6">
            <div class="text-muted mb-1">Employee Name</div>
            <div class="fw-semibold"><?= e($empName) ?></div>
        </div>
        <div class="col-6">
            <div class="text-muted mb-1">Employee No.</div>
            <div class="fw-semibold"><code><?= e($empNo) ?></code></div>
        </div>
        <div class="col-6">
            <div class="text-muted mb-1">Department</div>
            <div class="fw-semibold"><?= e($dept ?: '—') ?></div>
        </div>
        <div class="col-6">
            <div class="text-muted mb-1">Designation</div>
            <div class="fw-semibold"><?= e($desig ?: '—') ?></div>
        </div>
    </div>

    <!-- Earnings -->
    <table class="portal-payslip-table mb-3">
        <thead>
            <tr>
                <th colspan="2">Earnings</th>
                <th class="text-end">Amount (ZMW)</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($earnings)): foreach ($earnings as $earning): ?>
            <tr><td colspan="2"><?= e((string)$earning['label']) ?></td><td class="text-end"><?= number_format((float)$earning['amount'], 2) ?></td></tr>
            <?php endforeach; else: ?>
            <tr><td colspan="2">Earnings</td><td class="text-end"><?= number_format($grossPay, 2) ?></td></tr>
            <?php endif; ?>
            <tr class="portal-payslip-total">
                <td colspan="2"><strong>Total Gross Pay</strong></td>
                <td class="text-end"><strong><?= number_format($grossPay, 2) ?></strong></td>
            </tr>
        </tbody>
    </table>

    <!-- Deductions -->
    <table class="portal-payslip-table mb-3">
        <thead>
            <tr>
                <th>Deductions</th>
                <th class="text-end">Amount (ZMW)</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($deductions)): ?>
            <tr><td colspan="2" class="text-muted">No specific deductions on record.</td></tr>
        <?php else: ?>
            <?php foreach ($deductions as $d): ?>
            <tr>
                <td><?= e((string)($d['label'] ?? 'Deduction')) ?></td>
                <td class="text-end"><?= number_format((float)$d['amount'], 2) ?></td>
            </tr>
            <?php endforeach; ?>
        <?php endif; ?>
            <tr class="portal-payslip-total">
                <td><strong>Total Deductions</strong></td>
                <td class="text-end text-danger"><strong><?= number_format($totalDed, 2) ?></strong></td>
            </tr>
        </tbody>
    </table>

    <!-- Net pay -->
    <div style="background:var(--portal-green);color:#fff;border-radius:6px;padding:14px 18px;display:flex;justify-content:space-between;align-items:center;font-size:1rem">
        <span style="font-weight:700">NET PAY</span>
        <span style="font-size:1.3rem;font-weight:800">ZMW <?= number_format($netPay, 2) ?></span>
    </div>

    <div class="text-muted text-center mt-4" style="font-size:.74rem">
        This is a computer-generated document. No signature required. &mdash; <?= e($companyName) ?> Payroll System
    </div>
</div>

