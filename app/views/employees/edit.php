<?php
$oldInput = !empty($old) ? $old : $employee;
?>

<div class="d-flex align-items-center justify-content-between mr-bottom-30">
    <div>
        <h2 class="text-dark">Edit Employee</h2>
        <p class="text-gray mb-0">Update staff profile details.</p>
    </div>
    <a href="<?= e(base_url('employee/index')) ?>" class="btn btn-outline-secondary">Back</a>
</div>

<?php if (!empty($flashError)): ?>
    <div class="alert alert-danger"><?= e((string) $flashError) ?></div>
<?php endif; ?>
<?php if (!empty($flashSuccess)): ?>
    <div class="alert alert-success"><?= e((string) $flashSuccess) ?></div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="post" action="<?= e(base_url('employee/update/' . (string) $employee['id'])) ?>" class="row g-3">
            <input type="hidden" name="_csrf" value="<?= e((string) $csrf) ?>">

            <div class="col-md-4">
                <label class="form-label">Employee Number *</label>
                <?php $employeeNumber = (string) ($oldInput['employee_number'] ?? ''); ?>
                <input type="text" class="form-control" value="<?= e($employeeNumber) ?>" readonly>
                <input type="hidden" name="employee_number" value="<?= e($employeeNumber) ?>">
            </div>

            <div class="col-md-8">
                <label class="form-label">Full Name *</label>
                <input type="text" name="full_name" class="form-control" value="<?= e((string) ($oldInput['full_name'] ?? '')) ?>" required>
            </div>

            <div class="col-md-6">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" value="<?= e((string) ($oldInput['email'] ?? '')) ?>">
            </div>

            <div class="col-md-6">
                <label class="form-label">Phone</label>
                <input type="text" name="phone" class="form-control" value="<?= e((string) ($oldInput['phone'] ?? '')) ?>">
            </div>

            <div class="col-12">
                <div class="border rounded-3 p-3 bg-light">
                    <h6 class="mb-2">Statutory Identity Details</h6>
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">NRC Number</label>
                            <input type="text" name="nrc_number" class="form-control" value="<?= e((string) ($oldInput['nrc_number'] ?? '')) ?>" placeholder="e.g. 123456/78/9">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Date of Birth</label>
                            <input type="date" name="date_of_birth" class="form-control" value="<?= e((string) ($oldInput['date_of_birth'] ?? '')) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">NAPSA Social Security No.</label>
                            <input type="text" name="napsa_number" class="form-control" value="<?= e((string) ($oldInput['napsa_number'] ?? '')) ?>" placeholder="Required for NAPSA return">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Employee TPIN</label>
                            <input type="text" name="tpin" class="form-control" value="<?= e((string) ($oldInput['tpin'] ?? '')) ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Residential Address</label>
                            <input type="text" name="address" class="form-control" value="<?= e((string) ($oldInput['address'] ?? '')) ?>">
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <label class="form-label">Branch / Location</label>
                <select name="branch_id" class="form-select">
                    <option value="">No branch selected</option>
                    <?php foreach (($branches ?? []) as $branch): ?>
                        <option value="<?= e((string) $branch['id']) ?>" <?= ((string) ($oldInput['branch_id'] ?? '') === (string) $branch['id']) ? 'selected' : '' ?>>
                            <?= e((string) $branch['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label">Department</label>
                <select name="department_id" class="form-select">
                    <option value="">Select department</option>
                    <?php foreach ($departments as $department): ?>
                        <option value="<?= e((string) $department['id']) ?>" <?= ((string) ($oldInput['department_id'] ?? '') === (string) $department['id']) ? 'selected' : '' ?>>
                            <?= e((string) $department['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label">Designation</label>
                <input type="text" name="designation" class="form-control" value="<?= e((string) ($oldInput['designation'] ?? '')) ?>">
            </div>

            <div class="col-md-3">
                <label class="form-label">Employment Type</label>
                <?php $employmentType = (string) ($oldInput['employment_type'] ?? 'Permanent'); ?>
                <select name="employment_type" class="form-select">
                    <?php foreach (($employmentTypes ?? []) as $type): ?>
                        <option value="<?= e((string) $type['name']) ?>" <?= $employmentType === (string) $type['name'] ? 'selected' : '' ?>>
                            <?= e((string) $type['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-6">
                <label class="form-label">Bank Name</label>
                <input type="text" name="bank_name" class="form-control" value="<?= e((string) ($oldInput['bank_name'] ?? '')) ?>">
            </div>

            <div class="col-md-6">
                <label class="form-label">Bank Account Number</label>
                <input type="text" name="bank_account_number" class="form-control" value="<?= e((string) ($oldInput['bank_account_number'] ?? '')) ?>">
            </div>

            <div class="col-md-6">
                <label class="form-label">Contract Status</label>
                <?php $contractStatus = (string) ($oldInput['contract_status'] ?? 'Active'); ?>
                <select name="contract_status" class="form-select">
                    <option value="Active" <?= $contractStatus === 'Active' ? 'selected' : '' ?>>Active</option>
                    <option value="Ended" <?= $contractStatus === 'Ended' ? 'selected' : '' ?>>Ended</option>
                    <option value="Suspended" <?= $contractStatus === 'Suspended' ? 'selected' : '' ?>>Suspended</option>
                </select>
            </div>

            <div class="col-md-6">
                <label class="form-label">Hire Date</label>
                <input type="date" name="hired_at" class="form-control" value="<?= e((string) ($oldInput['hired_at'] ?? '')) ?>">
            </div>

            <div class="col-12">
                <button type="submit" class="btn btn-primary">Update Employee</button>
                <a href="<?= e(base_url('employee/index')) ?>" class="btn btn-outline-secondary ms-2">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php $salaryInput = !empty($salaryOld) ? $salaryOld : []; ?>
<?php $deductionInput = !empty($deductionOld) ? $deductionOld : []; ?>

<div class="card border-0 shadow-sm mt-4">
    <div class="card-header bg-white border-0 pt-4 px-4">
        <h5 class="mb-1">Employee Deductions</h5>
        <p class="text-gray mb-0">Assign recurring deduction rules for this employee.</p>
    </div>
    <div class="card-body px-4 pb-4">
        <form method="post" action="<?= e(base_url('employee/assignDeduction/' . (string) $employee['id'])) ?>" class="row g-3">
            <input type="hidden" name="_csrf" value="<?= e((string) $csrf) ?>">

            <div class="col-md-4">
                <label class="form-label">Deduction Type *</label>
                <select name="deduction_type_id" class="form-select" id="deductionTypeSelect" required>
                    <option value="">Select deduction type</option>
                    <?php foreach ($deductionTypes as $type): ?>
                        <option
                            value="<?= e((string) $type['id']) ?>"
                            data-statutory="<?= (int) ($type['is_statutory'] ?? 0) ?>"
                            data-default-value="<?= e((string) ($type['default_value'] ?? '0')) ?>"
                            data-calculation-type="<?= e((string) ($type['calculation_type'] ?? 'Fixed')) ?>"
                            <?= ((string) ($deductionInput['deduction_type_id'] ?? '') === (string) $type['id']) ? 'selected' : '' ?>>
                            <?= e((string) ($type['name'] ?? '')) ?><?= !empty($type['code']) ? ' (' . e((string) $type['code']) . ')' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <small class="text-gray d-block mt-1">PAYE, NAPSA, and NHIMA are calculated automatically from statutory settings. Assign only employee-specific deductions here.</small>
            </div>

            <div class="col-md-2" id="deductionAmountWrap">
                <label class="form-label">Amount *</label>
                <input type="number" step="0.01" min="0" name="amount" id="deductionAmount" class="form-control" value="<?= e((string) ($deductionInput['amount'] ?? '0')) ?>">
            </div>

            <div class="col-md-3" id="deductionStartWrap">
                <label class="form-label">Start Date</label>
                <input type="date" name="start_date" id="deductionStartDate" class="form-control" value="<?= e((string) ($deductionInput['start_date'] ?? '')) ?>">
            </div>

            <div class="col-md-3" id="deductionEndWrap">
                <label class="form-label">End Date</label>
                <input type="date" name="end_date" id="deductionEndDate" class="form-control" value="<?= e((string) ($deductionInput['end_date'] ?? '')) ?>">
            </div>

            <div class="col-12">
                <button type="submit" class="btn btn-outline-primary">Assign Deduction</button>
            </div>
        </form>

        <div class="table-responsive mt-4">
            <table class="table table-striped align-middle mb-0">
                <thead>
                    <tr>
                        <th>Deduction</th>
                        <th>Amount</th>
                        <th>Start</th>
                        <th>End</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($deductionAssignmentHistory)): ?>
                    <tr><td colspan="5" class="text-center text-gray">No employee deduction history.</td></tr>
                <?php else: ?>
                    <?php foreach ($deductionAssignmentHistory as $assignment): ?>
                        <tr>
                            <td>
                                <div class="fw-semibold"><?= e((string) ($assignment['deduction_name'] ?? '')) ?></div>
                                <div class="text-gray small"><?= e((string) ($assignment['deduction_code'] ?? '')) ?></div>
                            </td>
                            <td><?= e(format_currency((float) ($assignment['amount'] ?? 0))) ?></td>
                            <td><?= e((string) ($assignment['start_date'] ?? '-')) ?></td>
                            <td><?= e((string) ($assignment['end_date'] ?? '-')) ?></td>
                            <td><?= (int) ($assignment['is_active'] ?? 0) === 1 ? 'Active' : 'Inactive' ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    (function () {
        const select = document.getElementById('deductionTypeSelect');
        const amountWrap = document.getElementById('deductionAmountWrap');
        const startWrap = document.getElementById('deductionStartWrap');
        const endWrap = document.getElementById('deductionEndWrap');
        const amountInput = document.getElementById('deductionAmount');
        const startInput = document.getElementById('deductionStartDate');
        const endInput = document.getElementById('deductionEndDate');

        if (!select || !amountWrap || !startWrap || !endWrap || !amountInput || !startInput || !endInput) {
            return;
        }

        function toggleDeductionFields() {
            const selected = select.options[select.selectedIndex];
            const isStatutory = selected?.dataset?.statutory === '1';
            const defaultValue = selected?.dataset?.defaultValue ?? '0';

            amountWrap.style.display = isStatutory ? 'none' : '';
            startWrap.style.display = isStatutory ? 'none' : '';
            endWrap.style.display = isStatutory ? 'none' : '';

            amountInput.required = !isStatutory;
            startInput.required = !isStatutory;
            endInput.required = !isStatutory;

            if (isStatutory) {
                amountInput.value = defaultValue;
                startInput.value = '';
                endInput.value = '';
            }
        }

        select.addEventListener('change', toggleDeductionFields);
        toggleDeductionFields();
    })();
</script>

<div class="card border-0 shadow-sm mt-4">
    <div class="card-header bg-white border-0 pt-4 px-4">
        <h5 class="mb-1">Salary Structure Assignment</h5>
        <p class="text-gray mb-0">Assign the active salary structure for this employee.</p>
    </div>
    <div class="card-body px-4 pb-4">
        <div class="mb-3">
            <span class="text-gray d-block mb-1">Current Active Structure</span>
            <?php if (!empty($activeSalaryAssignment)): ?>
                <span class="fw-semibold"><?= e((string) ($activeSalaryAssignment['structure_name'] ?? '')) ?></span>
                <small class="text-gray d-block">Effective: <?= e((string) ($activeSalaryAssignment['effective_date'] ?? '')) ?></small>
                <small class="text-gray d-block">
                    Standard basic: ZMW <?= e(number_format((float) ($activeSalaryAssignment['structure_basic_pay'] ?? 0), 2)) ?>
                    · Agreed basic: ZMW <?= e(number_format((float) ($activeSalaryAssignment['basic_pay'] ?? 0), 2)) ?>
                </small>
            <?php else: ?>
                <span class="text-gray">No active salary structure assigned.</span>
            <?php endif; ?>
        </div>

        <form method="post" action="<?= e(base_url('employee/assignSalary/' . (string) $employee['id'])) ?>" class="row g-3">
            <input type="hidden" name="_csrf" value="<?= e((string) $csrf) ?>">

            <div class="col-md-6">
                <label class="form-label">Salary Structure *</label>
                <select name="salary_structure_id" class="form-select" required>
                    <option value="">Select salary structure</option>
                    <?php foreach ($salaryStructures as $structure): ?>
                        <option value="<?= e((string) $structure['id']) ?>" <?= ((string) ($salaryInput['salary_structure_id'] ?? '') === (string) $structure['id']) ? 'selected' : '' ?>>
                            <?= e((string) ($structure['name'] ?? '')) ?><?= !empty($structure['grade_level']) ? ' (' . e((string) $structure['grade_level']) . ')' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label">Effective Date *</label>
                <input type="date" name="effective_date" class="form-control" value="<?= e((string) ($salaryInput['effective_date'] ?? '')) ?>" required>
            </div>

            <div class="col-md-4">
                <label class="form-label">Agreed Basic Pay</label>
                <input type="number" step="0.01" min="0" name="actual_basic_pay" class="form-control" value="<?= e((string) ($salaryInput['actual_basic_pay'] ?? '')) ?>" placeholder="Leave blank to use structure basic pay">
            </div>

            <div class="col-md-4">
                <label class="form-label">Agreed Housing Allowance</label>
                <input type="number" step="0.01" min="0" name="actual_housing_allowance" class="form-control" value="<?= e((string) ($salaryInput['actual_housing_allowance'] ?? '')) ?>" placeholder="Leave blank to use structure allowance">
            </div>

            <div class="col-md-4">
                <label class="form-label">Agreed Transport Allowance</label>
                <input type="number" step="0.01" min="0" name="actual_transport_allowance" class="form-control" value="<?= e((string) ($salaryInput['actual_transport_allowance'] ?? '')) ?>" placeholder="Leave blank to use structure allowance">
            </div>

            <div class="col-md-4">
                <label class="form-label">Agreed Other Allowances</label>
                <input type="number" step="0.01" min="0" name="actual_other_allowances" class="form-control" value="<?= e((string) ($salaryInput['actual_other_allowances'] ?? '')) ?>" placeholder="Leave blank to use structure allowance">
            </div>

            <div class="col-md-8">
                <label class="form-label">Reason for Employee-Specific Pay</label>
                <input type="text" name="override_reason" class="form-control" value="<?= e((string) ($salaryInput['override_reason'] ?? '')) ?>" placeholder="Example: negotiated offer above standard teacher basic pay">
            </div>

            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-outline-primary w-100">Request</button>
            </div>
            <div class="col-12">
                <small class="text-gray">Leave agreed pay fields blank when the employee should use the salary structure amounts. Salary changes go through Finance review and Admin/Director approval before they are applied.</small>
                <a href="<?= e(base_url('salary-change/index')) ?>" class="ms-2">View approvals</a>
            </div>
        </form>

        <div class="table-responsive mt-4">
            <table class="table table-striped align-middle mb-0">
                <thead>
                    <tr>
                        <th>Structure</th>
                        <th>Grade</th>
                        <th>Standard Basic</th>
                        <th>Agreed Basic</th>
                        <th>Variance</th>
                        <th>Effective Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($salaryAssignmentHistory)): ?>
                    <tr><td colspan="7" class="text-center text-gray">No salary assignment history.</td></tr>
                <?php else: ?>
                    <?php foreach ($salaryAssignmentHistory as $assignment): ?>
                        <tr>
                            <td><?= e((string) ($assignment['structure_name'] ?? '')) ?></td>
                            <td><?= e((string) ($assignment['grade_level'] ?? '-')) ?></td>
                            <?php
                            $standardBasic = (float) ($assignment['structure_basic_pay'] ?? 0);
                            $agreedBasic = (float) ($assignment['basic_pay'] ?? $standardBasic);
                            $variance = $agreedBasic - $standardBasic;
                            ?>
                            <td>ZMW <?= e(number_format($standardBasic, 2)) ?></td>
                            <td>ZMW <?= e(number_format($agreedBasic, 2)) ?></td>
                            <td class="<?= $variance === 0.0 ? 'text-gray' : ($variance > 0 ? 'text-success' : 'text-danger') ?>">
                                <?= $variance === 0.0 ? '-' : e(($variance > 0 ? '+' : '') . 'ZMW ' . number_format($variance, 2)) ?>
                            </td>
                            <td><?= e((string) ($assignment['effective_date'] ?? '')) ?></td>
                            <td><?= (int) ($assignment['is_active'] ?? 0) === 1 ? 'Active' : 'Inactive' ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
$statusColors = ['Active' => 'success', 'Expired' => 'danger', 'Terminated' => 'secondary', 'Renewed' => 'info'];
?>
<div class="card border-0 shadow-sm mt-4">
    <div class="card-body">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h5 class="mb-0">Contracts</h5>
            <a href="<?= e(base_url('contract/create?employee_id=' . (string) $employee['id'])) ?>" class="btn btn-sm btn-outline-primary">Add Contract</a>
        </div>

        <?php if (!empty($activeContract)): ?>
            <?php $ac = $activeContract; ?>
            <div class="alert alert-success py-2 mb-3">
                <strong>Active:</strong>
                <?= e((string) ($ac['contract_number'] ?? '')) ?> &mdash;
                <?= e((string) ($ac['contract_type'] ?? '')) ?>,
                <?= e((string) ($ac['start_date'] ?? '')) ?>
                <?= !empty($ac['end_date']) ? '&ndash; ' . e((string) $ac['end_date']) : '(no expiry)' ?>
                &nbsp;
                <a href="<?= e(base_url('contract/download/' . (string) $ac['id'])) ?>" class="btn btn-sm btn-outline-dark py-0 px-2" target="_blank">&#8615; Download</a>
                <a href="<?= e(base_url('contract/renew/' . (string) $ac['id'])) ?>" class="btn btn-sm btn-outline-success py-0 px-2">Renew</a>
                <a href="<?= e(base_url('contract/edit/' . (string) $ac['id'])) ?>" class="btn btn-sm btn-outline-primary py-0 px-2">Edit</a>
            </div>
        <?php else: ?>
            <p class="text-gray mb-3">No active contract assigned.</p>
        <?php endif; ?>

        <?php if (!empty($contractHistory)): ?>
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Contract No.</th>
                            <th>Type</th>
                            <th>Start</th>
                            <th>End</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($contractHistory as $c): ?>
                            <?php $sc = $statusColors[$c['status'] ?? ''] ?? 'secondary'; ?>
                            <tr>
                                <td><?= e((string) ($c['contract_number'] ?? '-')) ?></td>
                                <td><?= e((string) ($c['contract_type'] ?? '-')) ?></td>
                                <td><?= e((string) ($c['start_date'] ?? '-')) ?></td>
                                <td><?= !empty($c['end_date']) ? e((string) $c['end_date']) : '<span class="text-gray">No expiry</span>' ?></td>
                                <td><span class="badge bg-<?= $sc ?>-subtle text-<?= $sc ?>-emphasis"><?= e((string) ($c['status'] ?? '')) ?></span></td>
                                <td class="text-end">
                                    <a href="<?= e(base_url('contract/download/' . (string) $c['id'])) ?>" class="btn btn-sm btn-outline-dark" target="_blank">&#8615;</a>
                                    <a href="<?= e(base_url('contract/edit/' . (string) $c['id'])) ?>" class="btn btn-sm btn-outline-secondary">Edit</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
