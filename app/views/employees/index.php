<div class="d-flex align-items-center justify-content-between mr-bottom-30">
    <div>
        <h2 class="text-dark">Employees</h2>
        <p class="text-gray mb-0">Staff profile and contract management.</p>
    </div>
    <?php $canManageEmployees = in_array((string) (current_user()['access_level'] ?? current_user()['role'] ?? ''), ['Super Admin', 'HR Officer'], true); ?>
    <div class="d-flex gap-2 flex-wrap">
    <a href="<?= e(base_url('employee/export')) ?>" class="btn btn-outline-primary">Export CSV</a>
    <?php if ($canManageEmployees): ?>
    <a href="<?= e(base_url('onboarding/index')) ?>" class="btn btn-outline-success">Onboarding Links</a>
    <a href="<?= e(base_url('employee-letter-template/index')) ?>" class="btn btn-outline-secondary">Letter Templates</a>
    <a href="<?= e(base_url('employee/import')) ?>" class="btn btn-outline-secondary">Import CSV</a>
    <a href="<?= e(base_url('employee/create')) ?>" class="btn btn-primary">Add Employee</a>
    <?php endif; ?>
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
        <form method="get" action="<?= e(base_url('employee/index')) ?>" class="row g-3 align-items-end">
            <div class="col-md-8">
                <label class="form-label">Search</label>
                <input type="text" name="search" class="form-control" value="<?= e((string) ($search ?? '')) ?>" placeholder="Search by name, employee number, or email">
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-outline-primary">Search</button>
                <a href="<?= e(base_url('employee/index')) ?>" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped align-middle">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Employee No.</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Branch</th>
                        <th>Department</th>
                        <th>Salary Structure</th>
                        <th>Status</th>
                        <th class="text-center">Portal</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($employees)): ?>
                        <tr>
                            <td colspan="10" class="text-center text-gray">No employees found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($employees as $employee): ?>
                            <tr>
                                <td><?= e((string) ($employee['id'] ?? '')) ?></td>
                                <td><?= e((string) ($employee['employee_number'] ?? '')) ?></td>
                                <td><?= e((string) ($employee['full_name'] ?? '')) ?></td>
                                <td><?= e((string) ($employee['email'] ?? '')) ?></td>
                                <td><?= e((string) ($employee['branch_name'] ?? '-')) ?></td>
                                <td><?= e((string) ($employee['department_name'] ?? '-')) ?></td>
                                <td>
                                    <?php if (!empty($employee['active_salary_structure_name'])): ?>
                                        <span class="badge bg-success-subtle text-success-emphasis"><?= e((string) $employee['active_salary_structure_name']) ?></span>
                                    <?php else: ?>
                                        <span class="text-gray">Not assigned</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= e((string) ($employee['contract_status'] ?? '-')) ?></td>
                                <td class="text-center">
                                    <?php if ((int)($employee['portal_active'] ?? 1) === 1): ?>
                                        <span class="badge bg-success" title="Portal access enabled"><i class="bi bi-check"></i></span>
                                        <?php if (!empty($employee['portal_must_change_password'])): ?>
                                            <span class="badge bg-warning text-dark ms-1" title="One-time password pending">OTP</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="badge bg-secondary" title="Portal access disabled"><i class="bi bi-dash"></i></span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <a href="<?= e(base_url('employee/profile/' . (string) $employee['id'])) ?>" class="btn btn-sm btn-outline-dark">Profile</a>
                                    <?php if ($canManageEmployees): ?>
                                    <a href="<?= e(base_url('employee/edit/' . (string) $employee['id'])) ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                                    <form method="post" action="<?= e(base_url('employee/delete/' . (string) $employee['id'])) ?>" class="d-inline">
                                        <input type="hidden" name="_csrf" value="<?= e((string) $csrf) ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Archive this employee? They will be hidden from active employee lists and portal access will be disabled.');">Archive</button>
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
</div>
