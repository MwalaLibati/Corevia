<div class="d-flex align-items-center justify-content-between mr-bottom-30">
    <div>
        <h2 class="text-dark">Import Attendance</h2>
        <p class="text-gray mb-0">Download the Excel-friendly template, fill attendance records, then upload it back as CSV.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= e(base_url('attendance/importTemplate')) ?>" class="btn btn-outline-primary">Download Template</a>
        <a href="<?= e(base_url('attendance/index')) ?>" class="btn btn-outline-secondary">Back</a>
    </div>
</div>

<?php if (!empty($flashSuccess)): ?>
    <div class="alert alert-success"><?= e((string) $flashSuccess) ?></div>
<?php endif; ?>
<?php if (!empty($flashError)): ?>
    <div class="alert alert-danger"><?= e((string) $flashError) ?></div>
<?php endif; ?>

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
                    <p class="mb-2"><strong>employee_number</strong> must match an employee already created in this company.</p>
                    <p class="mb-2"><strong>attendance_date</strong> should be a valid date, ideally YYYY-MM-DD.</p>
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
