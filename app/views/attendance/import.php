<div class="d-flex align-items-center justify-content-between mb-3">
    <div>
        <h2 class="text-dark">Import Attendance</h2>
        <p class="text-gray mb-0">Generate a pre-filled monthly template, complete it in Excel, then upload it as CSV.</p>
    </div>
</div>

<ul class="nav nav-tabs mb-4" role="tablist">
    <li class="nav-item"><a class="nav-link" href="<?= e(base_url('attendance/index?month=' . urlencode((string) ($selectedMonth ?? date('Y-m'))))) ?>">Daily Attendance</a></li>
    <li class="nav-item"><a class="nav-link" href="<?= e(base_url('attendance/monthly?month=' . urlencode((string) ($selectedMonth ?? date('Y-m'))))) ?>">Monthly Hours</a></li>
    <li class="nav-item"><a class="nav-link active" aria-current="page" href="<?= e(base_url('attendance/import?month=' . urlencode((string) ($selectedMonth ?? date('Y-m'))))) ?>">Import Attendance</a></li>
</ul>

<?php if (!empty($flashSuccess)): ?>
    <div class="alert alert-success"><?= e((string) $flashSuccess) ?></div>
<?php endif; ?>
<?php if (!empty($flashError)): ?>
    <div class="alert alert-danger"><?= e((string) $flashError) ?></div>
<?php endif; ?>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="d-flex align-items-start justify-content-between flex-wrap gap-2 mb-3">
            <div>
                <h5 class="mb-1">Generate Pre-Filled Template</h5>
                <p class="small text-muted mb-0">Employee IDs, employee names, and applicable dates are filled automatically.</p>
            </div>
        </div>
        <form method="get" action="<?= e(base_url('attendance/importTemplate')) ?>" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Attendance Month</label>
                <input type="month" name="month" class="form-control" value="<?= e((string) ($selectedMonth ?? date('Y-m'))) ?>" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Employee Scope</label>
                <select name="employee_id" class="form-select">
                    <option value="0">All employees</option>
                    <?php foreach (($employees ?? []) as $employee): ?>
                        <option value="<?= (int) $employee['id'] ?>"><?= e((string) $employee['employee_number']) ?> - <?= e((string) $employee['full_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Branch</label>
                <select name="branch_id" class="form-select">
                    <option value="0">All branches</option>
                    <?php foreach (($branches ?? []) as $branch): ?>
                        <option value="<?= (int) $branch['id'] ?>"><?= e((string) $branch['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Dates To Include</label>
                <select name="date_basis" class="form-select">
                    <option value="scheduled">Scheduled workdays</option>
                    <option value="weekdays">Monday to Friday</option>
                    <option value="calendar">All calendar days</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-outline-primary w-100"><i class="bi bi-download me-1"></i>Download</button>
            </div>
        </form>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h5 class="mb-3">Upload Completed Template</h5>
                <form method="post" action="<?= e(base_url('attendance/importStore')) ?>" enctype="multipart/form-data" class="row g-3 align-items-end">
                    <input type="hidden" name="_csrf" value="<?= e((string) $csrf) ?>">
                    <div class="col-md-8">
                        <label class="form-label">Attendance CSV</label>
                        <input type="file" name="attendance_csv" class="form-control" accept=".csv,text/csv" required>
                        <div class="form-text">Use the downloaded template. In Excel, choose Save As CSV before uploading.</div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="overwrite_existing" value="1" id="overwriteExisting">
                            <label class="form-check-label" for="overwriteExisting">Overwrite existing dates</label>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Import Attendance</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h5 class="mb-3">Template Rules</h5>
                <div class="small text-gray">
                    <p class="mb-2"><strong>employee_number</strong>, <strong>employee_name_reference</strong>, and <strong>attendance_date</strong> are generated for you.</p>
                    <p class="mb-2"><strong>status</strong> must be Present, Late, Absent, or Leave.</p>
                    <p class="mb-0"><strong>check_in</strong> and <strong>check_out</strong> use 24-hour time, for example 08:00 and 17:00.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($results)): ?>
<div class="card border-0 shadow-sm mt-4">
    <div class="card-body">
        <h5 class="mb-3">Import Result</h5>
        <div class="row g-3 mb-3">
            <div class="col-md-3"><div class="alert alert-success mb-0">Created: <?= e((string) ($results['created'] ?? 0)) ?></div></div>
            <div class="col-md-3"><div class="alert alert-info mb-0">Updated: <?= e((string) ($results['updated'] ?? 0)) ?></div></div>
            <div class="col-md-3"><div class="alert alert-warning mb-0">Skipped: <?= e((string) ($results['skipped'] ?? 0)) ?></div></div>
        </div>
        <?php if (!empty($results['errors'])): ?>
            <div class="table-responsive">
                <table class="table table-sm table-striped">
                    <thead><tr><th>Import Messages</th></tr></thead>
                    <tbody>
                        <?php foreach ($results['errors'] as $error): ?>
                            <tr><td><?= e((string) $error) ?></td></tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>
