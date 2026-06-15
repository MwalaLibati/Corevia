<div class="d-flex align-items-center justify-content-between mr-bottom-30">
    <div>
        <h2 class="text-dark">Create Onboarding Link</h2>
        <p class="text-gray mb-0">Invite an employee to complete their own profile details securely.</p>
    </div>
    <a href="<?= e(base_url('onboarding/index')) ?>" class="btn btn-outline-secondary">Back</a>
</div>

<?php if (!empty($flashError)): ?><div class="alert alert-danger"><?= e((string)$flashError) ?></div><?php endif; ?>

<?php
$selectedRequiredFields = json_decode((string)($old['required_fields_json'] ?? ''), true);
if (!is_array($selectedRequiredFields) || $selectedRequiredFields === []) {
    $selectedRequiredFields = ['full_name', 'email', 'phone', 'nrc_number', 'date_of_birth'];
}
?>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="post" action="<?= e(base_url('onboarding/store')) ?>" class="row g-3">
            <input type="hidden" name="_csrf" value="<?= e((string)$csrf) ?>">

            <div class="col-12">
                <label class="form-label">Existing Employee</label>
                <input type="hidden" name="selected_employee_id" id="selectedEmployeeId" value="<?= e((string)($old['selected_employee_id'] ?? '')) ?>">
                <input type="text" class="form-control" id="selectedEmployeeSearch" list="employeeOptions" placeholder="Search or select employee">
                <datalist id="employeeOptions">
                    <?php foreach (($employees ?? []) as $employee): ?>
                        <option value="<?= e(trim((string)($employee['employee_number'] ?? '') . ' - ' . (string)($employee['full_name'] ?? ''))) ?>"></option>
                    <?php endforeach; ?>
                </datalist>
                <div class="form-text">Use this when an employee profile already exists and HR only needs the employee to complete missing onboarding details.</div>
            </div>

            <div class="col-md-6">
                <label class="form-label">Employee Name *</label>
                <input type="text" name="invited_full_name" id="invitedFullName" class="form-control" value="<?= e((string)($old['invited_full_name'] ?? '')) ?>" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Email</label>
                <input type="email" name="invited_email" id="invitedEmail" class="form-control" value="<?= e((string)($old['invited_email'] ?? '')) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Phone</label>
                <input type="text" name="invited_phone" id="invitedPhone" class="form-control" value="<?= e((string)($old['invited_phone'] ?? '')) ?>">
            </div>

            <div class="col-md-4">
                <label class="form-label">Department</label>
                <select name="department_id" id="departmentId" class="form-select">
                    <option value="">No department yet</option>
                    <?php foreach (($departments ?? []) as $department): ?>
                        <option value="<?= e((string)$department['id']) ?>" <?= ((string)($old['department_id'] ?? '') === (string)$department['id']) ? 'selected' : '' ?>>
                            <?= e((string)$department['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Designation</label>
                <input type="text" name="designation" id="designation" class="form-control" value="<?= e((string)($old['designation'] ?? '')) ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Employment Type</label>
                <select name="employment_type" id="employmentType" class="form-select">
                    <?php foreach (($employmentTypes ?? []) as $type): ?>
                        <option value="<?= e((string)$type['name']) ?>" <?= ((string)($old['employment_type'] ?? 'Permanent') === (string)$type['name']) ? 'selected' : '' ?>>
                            <?= e((string)$type['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label">Expected Start Date</label>
                <input type="date" name="expected_start_date" class="form-control" value="<?= e((string)($old['expected_start_date'] ?? '')) ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Link Valid For</label>
                <select name="expires_days" class="form-select">
                    <?php foreach ([3,7,14,30] as $days): ?>
                        <option value="<?= e((string)$days) ?>" <?= (int)($old['expires_days'] ?? 7) === $days ? 'selected' : '' ?>><?= e((string)$days) ?> days</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-12">
                <label class="form-label">Internal HR Notes</label>
                <textarea name="hr_notes" class="form-control" rows="3"><?= e((string)($old['hr_notes'] ?? '')) ?></textarea>
            </div>

            <div class="col-12">
                <div class="border rounded-3 p-3 bg-light">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                        <div>
                            <h5 class="mb-1">Information Required From Employee</h5>
                            <p class="text-gray mb-0">Choose what this onboarding link should collect.</p>
                        </div>
                    </div>
                    <div class="row g-2">
                        <?php foreach (($requiredFieldOptions ?? []) as $field => $label): ?>
                            <div class="col-md-4 col-sm-6">
                                <label class="form-check mb-0">
                                    <input type="checkbox" name="required_fields[]" class="form-check-input" value="<?= e((string)$field) ?>" <?= in_array((string)$field, $selectedRequiredFields, true) ? 'checked' : '' ?>>
                                    <span class="form-check-label"><?= e((string)$label) ?></span>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <button type="submit" class="btn btn-primary">Create Secure Link</button>
                <a href="<?= e(base_url('onboarding/index')) ?>" class="btn btn-outline-secondary ms-2">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
    (function () {
        const input = document.getElementById('selectedEmployeeSearch');
        const hiddenId = document.getElementById('selectedEmployeeId');
        const fields = {
            name: document.getElementById('invitedFullName'),
            email: document.getElementById('invitedEmail'),
            phone: document.getElementById('invitedPhone'),
            department: document.getElementById('departmentId'),
            designation: document.getElementById('designation'),
            employmentType: document.getElementById('employmentType')
        };

        const employees = <?= json_encode(array_map(static function (array $employee): array {
            return [
                'id' => (string)($employee['id'] ?? ''),
                'label' => trim((string)($employee['employee_number'] ?? '') . ' - ' . (string)($employee['full_name'] ?? '')),
                'name' => (string)($employee['full_name'] ?? ''),
                'email' => (string)($employee['email'] ?? ''),
                'phone' => (string)($employee['phone'] ?? ''),
                'department' => (string)($employee['department_id'] ?? ''),
                'designation' => (string)($employee['designation'] ?? ''),
                'employmentType' => (string)($employee['employment_type'] ?? ''),
            ];
        }, $employees ?? []), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;

        function applyEmployee(employee) {
            hiddenId.value = employee ? employee.id : '';
            if (!employee) {
                return;
            }

            if (fields.name) fields.name.value = employee.name || '';
            if (fields.email) fields.email.value = employee.email || '';
            if (fields.phone) fields.phone.value = employee.phone || '';
            if (fields.department && employee.department) fields.department.value = employee.department;
            if (fields.designation) fields.designation.value = employee.designation || '';
            if (fields.employmentType && employee.employmentType) fields.employmentType.value = employee.employmentType;
        }

        function syncSelection() {
            const selected = employees.find(employee => employee.label === input.value.trim());
            applyEmployee(selected || null);
        }

        input.addEventListener('input', syncSelection);
        input.addEventListener('change', syncSelection);

        if (hiddenId.value) {
            const selected = employees.find(employee => employee.id === hiddenId.value);
            if (selected) {
                input.value = selected.label;
                applyEmployee(selected);
            }
        }
    })();
</script>
