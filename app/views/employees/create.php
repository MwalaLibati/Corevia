<div class="d-flex align-items-center justify-content-between mr-bottom-30">
    <div>
        <h2 class="text-dark">Create Employee</h2>
        <p class="text-gray mb-0">Add a new staff profile.</p>
    </div>
    <a href="<?= e(base_url('employee/index')) ?>" class="btn btn-outline-secondary">Back</a>
</div>

<?php if (!empty($flashError)): ?>
    <div class="alert alert-danger"><?= e((string) $flashError) ?></div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="post" action="<?= e(base_url('employee/store')) ?>" class="row g-3">
            <input type="hidden" name="_csrf" value="<?= e((string) $csrf) ?>">

            <div class="col-md-4">
                <label class="form-label">Employee Number *</label>
                <?php $employeeNumber = (string) ($old['employee_number'] ?? $nextEmployeeNumber ?? ''); ?>
                <input type="text" class="form-control" value="<?= e($employeeNumber) ?>" readonly>
                <input type="hidden" name="employee_number" value="<?= e($employeeNumber) ?>">
            </div>

            <div class="col-md-8">
                <label class="form-label">Full Name *</label>
                <input type="text" name="full_name" class="form-control" value="<?= e((string) ($old['full_name'] ?? '')) ?>" required>
            </div>

            <div class="col-md-6">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" value="<?= e((string) ($old['email'] ?? '')) ?>">
            </div>

            <div class="col-md-6">
                <label class="form-label">Phone</label>
                <input type="text" name="phone" class="form-control" value="<?= e((string) ($old['phone'] ?? '')) ?>">
            </div>

            <div class="col-12">
                <div class="border rounded-3 p-3 bg-light">
                    <h6 class="mb-2">Statutory Identity Details</h6>
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">NRC Number</label>
                            <input type="text" name="nrc_number" class="form-control" value="<?= e((string) ($old['nrc_number'] ?? '')) ?>" placeholder="e.g. 123456/78/9">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Date of Birth</label>
                            <input type="date" name="date_of_birth" class="form-control" value="<?= e((string) ($old['date_of_birth'] ?? '')) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">NAPSA Social Security No.</label>
                            <input type="text" name="napsa_number" class="form-control" value="<?= e((string) ($old['napsa_number'] ?? '')) ?>" placeholder="Required for NAPSA return">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Employee TPIN</label>
                            <input type="text" name="tpin" class="form-control" value="<?= e((string) ($old['tpin'] ?? '')) ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Residential Address</label>
                            <input type="text" name="address" class="form-control" value="<?= e((string) ($old['address'] ?? '')) ?>">
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <label class="form-label">Branch / Location</label>
                <select name="branch_id" class="form-select">
                    <option value="">No branch selected</option>
                    <?php foreach (($branches ?? []) as $branch): ?>
                        <option value="<?= e((string) $branch['id']) ?>" <?= ((string) ($old['branch_id'] ?? '') === (string) $branch['id']) ? 'selected' : '' ?>>
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
                        <option value="<?= e((string) $department['id']) ?>" <?= ((string) ($old['department_id'] ?? '') === (string) $department['id']) ? 'selected' : '' ?>>
                            <?= e((string) $department['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label">Designation</label>
                <input type="text" name="designation" class="form-control" value="<?= e((string) ($old['designation'] ?? '')) ?>">
            </div>

            <div class="col-md-3">
                <label class="form-label">Employment Type</label>
                <select name="employment_type" class="form-select">
                    <?php $employmentType = (string) ($old['employment_type'] ?? 'Permanent'); ?>
                    <?php foreach (($employmentTypes ?? []) as $type): ?>
                        <option value="<?= e((string) $type['name']) ?>" <?= $employmentType === (string) $type['name'] ? 'selected' : '' ?>>
                            <?= e((string) $type['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-6">
                <label class="form-label">Bank Name</label>
                <input type="text" name="bank_name" class="form-control" value="<?= e((string) ($old['bank_name'] ?? '')) ?>">
            </div>

            <div class="col-md-6">
                <label class="form-label">Bank Account Number</label>
                <input type="text" name="bank_account_number" class="form-control" value="<?= e((string) ($old['bank_account_number'] ?? '')) ?>">
            </div>

            <div class="col-md-6">
                <label class="form-label">Contract Status</label>
                <?php $contractStatus = (string) ($old['contract_status'] ?? 'Active'); ?>
                <select name="contract_status" class="form-select">
                    <option value="Active" <?= $contractStatus === 'Active' ? 'selected' : '' ?>>Active</option>
                    <option value="Ended" <?= $contractStatus === 'Ended' ? 'selected' : '' ?>>Ended</option>
                    <option value="Suspended" <?= $contractStatus === 'Suspended' ? 'selected' : '' ?>>Suspended</option>
                </select>
            </div>

            <div class="col-md-6">
                <label class="form-label">Hire Date</label>
                <input type="date" name="hired_at" class="form-control" value="<?= e((string) ($old['hired_at'] ?? '')) ?>">
            </div>

            <div class="col-12">
                <button type="submit" class="btn btn-primary">Save Employee</button>
                <a href="<?= e(base_url('employee/index')) ?>" class="btn btn-outline-secondary ms-2">Cancel</a>
            </div>
        </form>
    </div>
</div>
