<?php
$monthNames = ['','Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
?>
<div class="d-flex align-items-center justify-content-between mr-bottom-30">
    <div>
        <h2 class="text-dark">YTD Payroll Summary</h2>
        <p class="text-gray mb-0">Year-to-date gross, deductions and net per employee.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= e(base_url('report/index')) ?>" class="btn btn-outline-secondary">Back</a>
        <a href="<?= e(base_url('report/ytdSummary') . '?year=' . e((string)$year) . '&export=csv') ?>" class="btn btn-outline-success">
            <i class="bi bi-download me-1"></i> Export CSV
        </a>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="get" action="<?= e(base_url('report/ytdSummary')) ?>" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Year</label>
                <select name="year" class="form-select">
                    <?php foreach ((array)$years as $y): ?>
                        <option value="<?= e((string)$y) ?>" <?= (int)$y === (int)$year ? 'selected' : '' ?>><?= e((string)$y) ?></option>
                    <?php endforeach; ?>
                    <?php if (empty($years)): ?>
                        <option value="<?= e((string)$year) ?>" selected><?= e((string)$year) ?></option>
                    <?php endif; ?>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-outline-primary w-100">Filter</button>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body table-responsive">
        <?php if (empty($byEmployee)): ?>
            <p class="text-muted text-center py-4">No approved payroll data found for <?= e((string)$year) ?>.</p>
        <?php else: ?>
            <table class="table table-striped align-middle" style="min-width:900px">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Emp #</th>
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                            <th class="text-end" style="min-width:70px"><?= $monthNames[$m] ?></th>
                        <?php endfor; ?>
                        <th class="text-end">YTD Net</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($byEmployee as $emp): ?>
                    <?php
                        $ytdNet = 0.0;
                        foreach ($emp['months'] as $mr) { $ytdNet += (float)$mr['net']; }
                    ?>
                    <tr>
                        <td><?= e($emp['name']) ?></td>
                        <td><code><?= e($emp['number']) ?></code></td>
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                            <td class="text-end">
                                <?php if (isset($emp['months'][$m])): ?>
                                    <?= number_format((float)$emp['months'][$m]['net'], 2) ?>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                        <?php endfor; ?>
                        <td class="text-end fw-bold"><?= number_format($ytdNet, 2) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
