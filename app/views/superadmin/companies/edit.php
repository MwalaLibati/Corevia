<div class="d-flex align-items-center justify-content-between mb-4">
    <h2 class="text-dark mb-0">Edit Company</h2>
    <a href="<?= e(base_url('superadmin/company/index')) ?>" class="btn btn-sm btn-outline-secondary">← Back</a>
</div>

<div class="card border-0 shadow-sm" style="max-width:640px">
    <div class="card-body p-4">
        <?php if (!empty($flash)): ?>
        <div class="alert alert-danger py-2 mb-3" style="font-size:.83rem"><?= e($flash) ?></div>
        <?php endif; ?>
        <?php if (!empty($success)): ?>
        <div class="alert alert-success py-2 mb-3" style="font-size:.83rem"><?= e($success) ?></div>
        <?php endif; ?>
        <?php if (!empty($newAdminPassword)): ?>
        <div class="alert alert-warning mb-3" style="font-size:.9rem">
            <div class="fw-semibold mb-1">One-time admin password</div>
            <div>Email: <strong><?= e((string) $newAdminPassword['email']) ?></strong></div>
            <div>Password: <strong><?= e((string) $newAdminPassword['password']) ?></strong></div>
            <div class="small mt-1">This is shown once. The admin must change it after signing in.</div>
            <?php if (!empty($newAdminPassword['email_sent'])): ?>
                <div class="small mt-2 text-success"><i class="bi bi-envelope-check me-1"></i>Welcome email sent to the company admin.</div>
            <?php else: ?>
                <div class="small mt-2 text-danger">
                    <i class="bi bi-envelope-exclamation me-1"></i>Welcome email was not sent<?= !empty($newAdminPassword['email_error']) ? ': ' . e((string) $newAdminPassword['email_error']) : '.' ?>
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <form method="post" action="<?= e(base_url('superadmin/company/update')) ?>" enctype="multipart/form-data">
            <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
            <input type="hidden" name="id" value="<?= (int)$company['id'] ?>">
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label fw-semibold">Company Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" required value="<?= e((string)$company['name']) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Client Entity / Group</label>
                    <select name="client_entity_id" class="form-select">
                        <option value="">Create as single-company entity</option>
                        <?php foreach (($clientEntities ?? []) as $entity): ?>
                            <option value="<?= e((string) $entity['id']) ?>" <?= (int) ($company['client_entity_id'] ?? 0) === (int) $entity['id'] ? 'selected' : '' ?>>
                                <?= e((string) $entity['name']) ?><?= !empty($entity['code']) ? ' (' . e((string) $entity['code']) . ')' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">New Entity Name</label>
                    <input type="text" name="new_client_entity_name" class="form-control" placeholder="Optional new group">
                    <div class="form-text">Leave blank to use the selected entity.</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Slug</label>
                    <input type="text" class="form-control" value="<?= e((string)$company['slug']) ?>" disabled>
                    <div class="form-text text-muted">Slug cannot be changed after creation.</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Subscription Plan</label>
                    <select name="subscription_plan" class="form-select">
                        <?php foreach (($plans ?? []) as $p): ?>
                        <option value="<?= e((string) $p['name']) ?>" <?= (string) $company['subscription_plan'] === (string) $p['name'] ? 'selected' : '' ?>>
                            <?= e((string) $p['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Email</label>
                    <input type="email" name="email" class="form-control" value="<?= e((string)($company['email'] ?? '')) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Phone</label>
                    <input type="text" name="phone" class="form-control" value="<?= e((string)($company['phone'] ?? '')) ?>">
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">Address</label>
                    <textarea name="address" class="form-control" rows="2"><?= e((string)($company['address'] ?? '')) ?></textarea>
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">Company Logo</label>
                    <div class="d-flex align-items-center gap-3 flex-wrap">
                        <div style="width:86px;height:86px;border:1px solid #e5e7eb;border-radius:8px;display:flex;align-items:center;justify-content:center;background:#fff;overflow:hidden">
                            <img src="<?= e(company_logo_url($company)) ?>" alt="<?= e((string)$company['name']) ?> logo" style="max-width:76px;max-height:76px;object-fit:contain">
                        </div>
                        <div class="flex-grow-1">
                            <input type="file" name="company_logo" class="form-control" accept=".png,.jpg,.jpeg,.webp,image/png,image/jpeg,image/webp">
                            <div class="form-text">Used on company documents like contracts and payslips. PNG, JPG, WebP. Max 2 MB.</div>
                        </div>
                    </div>
                </div>
                <div class="col-12 pt-2">
                    <button type="submit" class="btn text-white" style="background:#7c3aed">
                        <i class="bi bi-floppy me-1"></i>Save Changes
                    </button>
                </div>
            </div>
        </form>
        <?php if (!empty($company['logo_path'])): ?>
            <form method="post" action="<?= e(base_url('superadmin/company/removeLogo/' . (string)$company['id'])) ?>" class="mt-2">
                <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Remove this company logo?')">Remove Logo</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<div class="card border-0 shadow-sm mt-4" style="max-width:820px">
    <div class="card-body p-4">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div>
                <h5 class="mb-1">Company Access</h5>
                <p class="text-muted mb-0" style="font-size:.86rem">Grant an existing login access to this company with a company-specific role.</p>
            </div>
        </div>

        <form method="post" action="<?= e(base_url('superadmin/company/grantAccess/' . (string) $company['id'])) ?>" class="row g-2 align-items-end mb-4">
            <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
            <div class="col-md-6">
                <label class="form-label fw-semibold">Existing User Email</label>
                <input type="email" name="email" class="form-control" placeholder="user@example.com" required>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold">Role in This Company</label>
                <select name="role_id" class="form-select" required>
                    <?php foreach (($roles ?? []) as $role): ?>
                        <option value="<?= (int) $role['id'] ?>"><?= e((string) $role['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">Grant</button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-sm align-middle">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($memberships)): ?>
                    <tr><td colspan="4" class="text-muted text-center">No users have access yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($memberships as $membership): ?>
                        <tr>
                            <td><?= e((string) $membership['full_name']) ?></td>
                            <td><?= e((string) $membership['email']) ?></td>
                            <td><span class="badge bg-secondary-subtle text-secondary-emphasis"><?= e((string) $membership['role_name']) ?></span></td>
                            <td class="text-end">
                                <form method="post" action="<?= e(base_url('superadmin/company/revokeAccess/' . (string) $membership['id'])) ?>" class="d-inline">
                                    <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Revoke this user access to this company?')">Revoke</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
