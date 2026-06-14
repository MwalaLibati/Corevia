<div class="d-flex align-items-center justify-content-between mr-bottom-30">
    <div>
        <h2 class="text-dark">Import Employees</h2>
        <p class="text-gray mb-0">Create or update employees from a CSV file.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= e(base_url('employee/importTemplate')) ?>" class="btn btn-outline-primary">Download Template</a>
        <a href="<?= e(base_url('employee/index')) ?>" class="btn btn-outline-secondary">Back</a>
    </div>
</div>

<?php if (!empty($flashSuccess)): ?>
    <div class="alert alert-success"><?= e((string) $flashSuccess) ?></div>
<?php endif; ?>
<?php if (!empty($flashError)): ?>
    <div class="alert alert-danger"><?= e((string) $flashError) ?></div>
<?php endif; ?>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="post" action="<?= e(base_url('employee/importStore')) ?>" enctype="multipart/form-data" class="row g-3 align-items-end">
            <input type="hidden" name="_csrf" value="<?= e((string) $csrf) ?>">
            <div class="col-md-8">
                <label class="form-label">Employee CSV</label>
                <input type="file" name="employee_csv" class="form-control" accept=".csv,text/csv" required>
                <div class="form-text">Existing employee numbers are updated. Blank employee numbers are auto-generated.</div>
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary">Import Employees</button>
            </div>
        </form>
    </div>
</div>

<?php if (!empty($results)): ?>
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <h5 class="mb-3">Import Result</h5>
        <div class="row g-3 mb-3">
            <div class="col-md-3"><div class="alert alert-success mb-0">Created: <?= e((string) ($results['created'] ?? 0)) ?></div></div>
            <div class="col-md-3"><div class="alert alert-info mb-0">Updated: <?= e((string) ($results['updated'] ?? 0)) ?></div></div>
            <div class="col-md-3"><div class="alert alert-warning mb-0">Skipped: <?= e((string) count($results['errors'] ?? [])) ?></div></div>
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
