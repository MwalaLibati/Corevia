<?php
$isEdit = !empty($entity);
$formData = !empty($old) ? $old : ($entity ?? []);
$postUrl = $isEdit ? base_url('superadmin/client-entity/update/' . (string) $entity['id']) : base_url('superadmin/client-entity/store');
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h2 class="text-dark mb-0"><?= $isEdit ? 'Edit Client Entity' : 'Create Client Entity' ?></h2>
        <p class="text-muted mb-0 mt-1">Use this for groups, holding companies, or clients with multiple companies.</p>
    </div>
    <a href="<?= e(base_url('superadmin/client-entity/index')) ?>" class="btn btn-sm btn-outline-secondary">Back</a>
</div>

<?php if (!empty($flashError)): ?><div class="alert alert-danger"><?= e((string) $flashError) ?></div><?php endif; ?>

<div class="card border-0 shadow-sm" style="max-width:820px">
    <div class="card-body p-4">
        <form method="post" action="<?= e($postUrl) ?>" class="row g-3">
            <input type="hidden" name="_csrf" value="<?= e((string) $csrf) ?>">

            <div class="col-md-8">
                <label class="form-label fw-semibold">Entity / Group Name <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control" value="<?= e((string) ($formData['name'] ?? '')) ?>" required placeholder="e.g. Libati Group Limited">
            </div>

            <div class="col-md-4">
                <label class="form-label fw-semibold">Entity Code</label>
                <input type="text" name="code" class="form-control" value="<?= e((string) ($formData['code'] ?? '')) ?>" readonly>
                <div class="form-text">Auto-generated.</div>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold">Entity Type</label>
                <?php $type = (string) ($formData['entity_type'] ?? 'Group'); ?>
                <select name="entity_type" class="form-select">
                    <?php foreach (['Group', 'Holding Company', 'Franchise', 'NGO', 'School Group', 'Single Company'] as $option): ?>
                        <option value="<?= e($option) ?>" <?= $type === $option ? 'selected' : '' ?>><?= e($option) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold">Contact Person</label>
                <input type="text" name="contact_person" class="form-control" value="<?= e((string) ($formData['contact_person'] ?? '')) ?>">
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold">Email</label>
                <input type="email" name="email" class="form-control" value="<?= e((string) ($formData['email'] ?? '')) ?>">
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold">Phone</label>
                <input type="text" name="phone" class="form-control" value="<?= e((string) ($formData['phone'] ?? '')) ?>">
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold">Status</label>
                <?php $active = (int) ($formData['is_active'] ?? 1); ?>
                <select name="is_active" class="form-select">
                    <option value="1" <?= $active === 1 ? 'selected' : '' ?>>Active</option>
                    <option value="0" <?= $active === 0 ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>

            <div class="col-12">
                <label class="form-label fw-semibold">Address</label>
                <textarea name="address" class="form-control" rows="3"><?= e((string) ($formData['address'] ?? '')) ?></textarea>
            </div>

            <div class="col-12 pt-2">
                <button type="submit" class="btn text-white" style="background:#7c3aed">
                    <i class="bi bi-floppy me-1"></i><?= $isEdit ? 'Save Changes' : 'Create Entity' ?>
                </button>
            </div>
        </form>
    </div>
</div>
