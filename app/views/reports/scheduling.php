<?php
$month = (string) ($month ?? date('Y-m'));
$activeReport = (string) ($activeReport ?? 'coverage');
$reports = $reports ?? [];
$payloads = $payloads ?? [];
$activePayload = $payloads[$activeReport] ?? ['title' => 'Scheduling Report', 'description' => '', 'headers' => [], 'rows' => []];
$reportUrl = static fn(string $key, string $extra = ''): string => base_url('report/schedulingReports?month=' . urlencode($month) . '&report=' . urlencode($key) . $extra);
?>

<div class="d-flex align-items-center justify-content-between mr-bottom-30 flex-wrap gap-3">
    <div>
        <h2 class="text-dark">Scheduling Reports</h2>
        <p class="text-gray mb-0">Shift coverage, exceptions, employee requests, attendance impact, and payroll readiness.</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="<?= e($reportUrl($activeReport, '&export=csv')) ?>" class="btn btn-outline-primary"><i class="bi bi-filetype-csv me-1"></i>CSV</a>
        <a href="<?= e($reportUrl($activeReport, '&export=xls')) ?>" class="btn btn-outline-success"><i class="bi bi-file-earmark-excel me-1"></i>Excel</a>
        <a href="<?= e($reportUrl($activeReport, '&export=pdf')) ?>" class="btn btn-outline-danger" target="_blank"><i class="bi bi-file-earmark-pdf me-1"></i>PDF</a>
        <a href="<?= e(base_url('report/index')) ?>" class="btn btn-outline-secondary">Back</a>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="get" action="<?= e(base_url('report/schedulingReports')) ?>" class="row g-3 align-items-end">
            <input type="hidden" name="report" value="<?= e($activeReport) ?>">
            <div class="col-md-4 col-xl-3">
                <label class="form-label">Report Month</label>
                <input type="month" name="month" class="form-control" value="<?= e($month) ?>">
            </div>
            <div class="col-md-4 col-xl-3">
                <button class="btn btn-primary w-100"><i class="bi bi-funnel me-1"></i>Apply Filter</button>
            </div>
            <div class="col-md-4 col-xl-6 text-md-end">
                <span class="badge bg-light text-dark border"><?= e(date('F Y', strtotime($month . '-01'))) ?></span>
                <span class="badge bg-primary-subtle text-primary border"><?= count($activePayload['rows'] ?? []) ?> row(s)</span>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <ul class="nav nav-tabs ent-tabs mb-4" role="tablist">
            <?php foreach ($reports as $key => $report): ?>
                <li class="nav-item" role="presentation">
                    <a class="nav-link <?= $key === $activeReport ? 'active' : '' ?>" href="<?= e($reportUrl((string) $key)) ?>">
                        <i class="bi <?= e((string)($report['icon'] ?? 'bi-table')) ?> me-1"></i><?= e((string)($report['title'] ?? $key)) ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>

        <div class="d-flex align-items-start justify-content-between flex-wrap gap-3 mb-3">
            <div>
                <h5 class="mb-1"><?= e((string)($activePayload['title'] ?? 'Scheduling Report')) ?></h5>
                <p class="text-muted mb-0 small"><?= e((string)($activePayload['description'] ?? '')) ?></p>
            </div>
            <span class="badge bg-light text-dark border"><?= e((string)($reports[$activeReport]['title'] ?? 'Report')) ?></span>
        </div>

        <div class="table-responsive">
            <table class="table table-striped align-middle mb-0">
                <thead>
                    <tr>
                        <?php foreach (($activePayload['headers'] ?? []) as $header): ?>
                            <th><?= e((string) $header) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($activePayload['rows'])): ?>
                    <tr>
                        <td colspan="<?= max(1, count($activePayload['headers'] ?? [])) ?>" class="text-center text-gray py-5">
                            <div class="ent-empty-state">
                                <i class="bi bi-inbox"></i>
                                <div>No data found for this scheduling report yet.</div>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($activePayload['rows'] as $row): ?>
                        <tr>
                            <?php foreach ($row as $cell): ?>
                                <td><?= is_float($cell) || is_int($cell) ? e(is_float($cell) ? number_format($cell, 2) : (string) $cell) : e((string) $cell) ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
