<div class="d-flex align-items-center justify-content-between mr-bottom-30">
    <div>
        <h2 class="text-dark">Salary Change Approvals</h2>
        <p class="text-gray mb-0">Finance reviews salary changes before Admin/Director approval applies them.</p>
    </div>
    <a href="<?= e(base_url('employee/index')) ?>" class="btn btn-outline-secondary">Employees</a>
</div>

<?php if (!empty($flashSuccess)): ?><div class="alert alert-success"><?= e((string)$flashSuccess) ?></div><?php endif; ?>
<?php if (!empty($flashError)): ?><div class="alert alert-danger"><?= e((string)$flashError) ?></div><?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body table-responsive">
        <table class="table table-striped align-middle mb-0">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>New Structure</th>
                    <th>Basic Pay</th>
                    <th>Effective</th>
                    <th>Status</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($requests)): ?>
                    <tr><td colspan="6" class="text-center text-gray">No salary change requests.</td></tr>
                <?php else: ?>
                    <?php foreach ($requests as $request): ?>
                        <?php
                        $standardBasic = (float) ($request['structure_basic_pay'] ?? 0);
                        $agreedBasic = $request['actual_basic_pay'] !== null ? (float) $request['actual_basic_pay'] : $standardBasic;
                        $variance = $agreedBasic - $standardBasic;
                        ?>
                        <tr>
                            <td>
                                <div class="fw-semibold"><?= e((string)$request['employee_name']) ?></div>
                                <div class="small text-gray"><?= e((string)$request['employee_number']) ?></div>
                            </td>
                            <td><?= e((string)$request['salary_structure_name']) ?></td>
                            <td>
                                <div class="fw-semibold">ZMW <?= e(number_format($agreedBasic, 2)) ?></div>
                                <div class="small text-gray">Standard: ZMW <?= e(number_format($standardBasic, 2)) ?></div>
                                <?php if ($variance !== 0.0): ?>
                                    <div class="small <?= $variance > 0 ? 'text-success' : 'text-danger' ?>">
                                        <?= e(($variance > 0 ? '+' : '') . 'ZMW ' . number_format($variance, 2)) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td><?= e((string)$request['effective_date']) ?></td>
                            <td><span class="badge bg-warning text-dark"><?= e((string)$request['status']) ?></span></td>
                            <td class="text-end">
                                <?php if (in_array((string)$request['status'], ['Pending Finance Review','Pending Admin Approval'], true)): ?>
                                    <form method="post" action="<?= e(base_url('salary-change/approve/' . (string)$request['id'])) ?>" class="d-inline">
                                        <input type="hidden" name="_csrf" value="<?= e((string)$csrf) ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <button class="btn btn-sm btn-outline-success" type="submit">Approve Step</button>
                                    </form>
                                    <form method="post" action="<?= e(base_url('salary-change/approve/' . (string)$request['id'])) ?>" class="d-inline">
                                        <input type="hidden" name="_csrf" value="<?= e((string)$csrf) ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <button class="btn btn-sm btn-outline-danger" type="submit">Reject</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
