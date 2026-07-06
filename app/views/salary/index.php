<div class="d-flex align-items-center justify-content-between mr-bottom-30">
    <div>
        <h2 class="text-dark">Salary Management</h2>
        <p class="text-gray mb-0">Configure salary structures, allowances, and increments.</p>
    </div>
    <div class="d-flex gap-2"><a href="<?= e(base_url('allowance/index')) ?>" class="btn btn-outline-primary">Manage Allowances</a><a href="<?= e(base_url('salary/create')) ?>" class="btn btn-primary">Add Structure</a></div>
</div>

<?php if (!empty($flashSuccess)): ?><div class="alert alert-success"><?= e((string) $flashSuccess) ?></div><?php endif; ?>
<?php if (!empty($flashError)): ?><div class="alert alert-danger"><?= e((string) $flashError) ?></div><?php endif; ?>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="get" action="<?= e(base_url('salary/index')) ?>" class="row g-3 align-items-end">
            <div class="col-md-8">
                <label class="form-label">Search</label>
                <input type="text" class="form-control" name="search" value="<?= e((string) ($search ?? '')) ?>" placeholder="Name or grade level">
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button class="btn btn-outline-primary" type="submit">Search</button>
                <a href="<?= e(base_url('salary/index')) ?>" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body table-responsive">
        <table class="table table-striped align-middle">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Grade</th>
                    <th>Basic</th>
                    <th>Allowances</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($structures)): ?>
                <tr><td colspan="5" class="text-center text-gray">No salary structures found.</td></tr>
            <?php else: ?>
                <?php foreach ($structures as $row): ?>
                    <tr>
                        <td><?= e((string) ($row['name'] ?? '')) ?></td>
                        <td><?= e((string) ($row['grade_level'] ?? '-')) ?></td>
                        <td><?= e(format_currency((float) ($row['basic_pay'] ?? 0))) ?></td>
                        <td><?php $assigned=$allowancesByStructure[(int)$row['id']]??[]; ?><?php if(!$assigned): ?><span class="text-gray">None</span><?php else: ?><?php foreach($assigned as $a): ?><span class="badge bg-light text-dark border me-1 mb-1"><?= e((string)$a['name']) ?>: <?= $a['calculation_type']==='Percent'?e((string)$a['amount']).'%':e(format_currency((float)$a['amount'])) ?></span><?php endforeach; ?><?php endif; ?></td>
                        <td class="text-end">
                            <a href="<?= e(base_url('salary/edit/' . (string) $row['id'])) ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                            <form method="post" action="<?= e(base_url('salary/delete/' . (string) $row['id'])) ?>" class="d-inline">
                                <input type="hidden" name="_csrf" value="<?= e((string) $csrf) ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this salary structure?');">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
