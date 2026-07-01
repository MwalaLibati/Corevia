<div class="d-flex align-items-center justify-content-between mr-bottom-30">
    <div>
        <h2 class="text-dark">Create Payroll Run</h2>
        <p class="text-gray mb-0">Create payroll cycle for a pay period.</p>
    </div>
    <a href="<?= e(base_url('payroll/index')) ?>" class="btn btn-outline-secondary">Back</a>
</div>

<?php if (!empty($flashError)): ?><div class="alert alert-danger"><?= e((string) $flashError) ?></div><?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <?php
            $defaultPayPeriod = (string) ($old['pay_period'] ?? date('Y-m'));
            if (!preg_match('/^(20\d{2})-(0[1-9]|1[0-2])$/', $defaultPayPeriod, $periodParts)) {
                $periodParts = [0, date('Y'), date('m')];
            }
            $selectedYear = (int)($old['pay_year'] ?? $periodParts[1]);
            $selectedMonth = (int)($old['pay_month'] ?? $periodParts[2]);
            $monthNames = [
                1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
                5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
                9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
            ];
        ?>
        <?php $defaultRunDate = (string) ($old['run_date'] ?? date('Y-m-d')); ?>

        <form method="post" action="<?= e(base_url('payroll/store')) ?>" class="row g-3">
            <input type="hidden" name="_csrf" value="<?= e((string) $csrf) ?>">

            <div class="col-md-4">
                <label class="form-label">Pay Month *</label>
                <select name="pay_month" class="form-select" required>
                    <?php foreach ($monthNames as $monthNumber => $monthName): ?>
                        <option value="<?= e((string)$monthNumber) ?>" <?= $selectedMonth === $monthNumber ? 'selected' : '' ?>>
                            <?= e($monthName) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label">Pay Year *</label>
                <input type="number" name="pay_year" class="form-control" value="<?= e((string)$selectedYear) ?>" min="2000" max="2099" step="1" required>
            </div>

            <div class="col-md-4">
                <label class="form-label">Run Date *</label>
                <input type="date" name="run_date" class="form-control" value="<?= e($defaultRunDate) ?>" required>
            </div>

            <div class="col-md-4">
                <label class="form-label">Tax Year</label>
                <select name="tax_year_id" class="form-select">
                    <option value="">Auto-match by run date</option>
                    <?php foreach (($taxYears ?? []) as $year): ?>
                        <option value="<?= e((string) $year['id']) ?>" <?= ((string)($old['tax_year_id'] ?? '') === (string)$year['id']) ? 'selected' : '' ?>>
                            <?= e((string) $year['name']) ?> (<?= e((string) $year['starts_on']) ?> to <?= e((string) $year['ends_on']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label">Partial-Month Pay</label>
                <?php $prorationMode = (string)($old['proration_mode'] ?? 'Full Month'); ?>
                <select name="proration_mode" class="form-select">
                    <option value="Full Month" <?= $prorationMode === 'Full Month' ? 'selected' : '' ?>>Pay full month</option>
                    <option value="Calendar Days" <?= $prorationMode === 'Calendar Days' ? 'selected' : '' ?>>Prorate by calendar days</option>
                </select>
                <div class="form-text">Applies when an employee starts or leaves during the selected month.</div>
            </div>

            <div class="col-12">
                <div class="alert alert-info mb-0">
                    Status, totals, and creator are set automatically when you save the run.
                </div>
            </div>

            <div class="col-12">
                <button type="submit" class="btn btn-primary">Save Payroll Run</button>
                <a href="<?= e(base_url('payroll/index')) ?>" class="btn btn-outline-secondary ms-2">Cancel</a>
            </div>
        </form>
    </div>
</div>
