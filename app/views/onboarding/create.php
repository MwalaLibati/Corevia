<div class="d-flex align-items-center justify-content-between mr-bottom-30">
    <div>
        <h2 class="text-dark">Create Onboarding Link</h2>
        <p class="text-gray mb-0">Invite an employee to complete their own profile details securely.</p>
    </div>
    <a href="<?= e(base_url('onboarding/index')) ?>" class="btn btn-outline-secondary">Back</a>
</div>

<?php if (!empty($flashError)): ?><div class="alert alert-danger"><?= e((string)$flashError) ?></div><?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="post" action="<?= e(base_url('onboarding/store')) ?>" class="row g-3">
            <input type="hidden" name="_csrf" value="<?= e((string)$csrf) ?>">

            <div class="col-md-6">
                <label class="form-label">Employee Name *</label>
                <input type="text" name="invited_full_name" class="form-control" value="<?= e((string)($old['invited_full_name'] ?? '')) ?>" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Email</label>
                <input type="email" name="invited_email" class="form-control" value="<?= e((string)($old['invited_email'] ?? '')) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Phone</label>
                <input type="text" name="invited_phone" class="form-control" value="<?= e((string)($old['invited_phone'] ?? '')) ?>">
            </div>

            <div class="col-md-4">
                <label class="form-label">Department</label>
                <select name="department_id" class="form-select">
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
                <input type="text" name="designation" class="form-control" value="<?= e((string)($old['designation'] ?? '')) ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Employment Type</label>
                <select name="employment_type" class="form-select">
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
                <button type="submit" class="btn btn-primary">Create Secure Link</button>
                <a href="<?= e(base_url('onboarding/index')) ?>" class="btn btn-outline-secondary ms-2">Cancel</a>
            </div>
        </form>
    </div>
</div>
