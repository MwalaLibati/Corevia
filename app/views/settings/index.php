<div class="d-flex align-items-center justify-content-between mr-bottom-30">
    <div>
        <h2 class="text-dark">System Settings</h2>
        <p class="text-gray mb-0">Configure company profile, rates, and payroll periods.</p>
    </div>
    <a href="<?= e(base_url('settings/create')) ?>" class="btn btn-primary">Add Setting</a>
</div>

<?php if (!empty($flashSuccess)): ?><div class="alert alert-success"><?= e((string) $flashSuccess) ?></div><?php endif; ?>
<?php if (!empty($flashError)): ?><div class="alert alert-danger"><?= e((string) $flashError) ?></div><?php endif; ?>

<?php $company = $company ?? current_company(); ?>
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="d-flex align-items-start gap-3 flex-wrap">
            <div style="width:96px;height:96px;border:1px solid #e5e7eb;border-radius:8px;display:flex;align-items:center;justify-content:center;background:#fff;overflow:hidden">
                <img src="<?= e(company_logo_url($company)) ?>" alt="<?= e((string)($company['name'] ?? 'Company')) ?> logo" style="max-width:86px;max-height:86px;object-fit:contain">
            </div>
            <div class="flex-grow-1">
                <h5 class="mb-1">Company Logo</h5>
                <p class="text-gray mb-3" style="font-size:.86rem">
                    This logo appears on company documents such as contracts and payslips.
                </p>
                <form method="post" action="<?= e(base_url('settings/updateCompanyLogo')) ?>" enctype="multipart/form-data" class="d-flex flex-wrap gap-2 align-items-center">
                    <input type="hidden" name="_csrf" value="<?= e((string) $csrf) ?>">
                    <input type="file" name="company_logo" class="form-control" style="max-width:320px" accept=".png,.jpg,.jpeg,.webp,image/png,image/jpeg,image/webp" required>
                    <button type="submit" class="btn btn-primary">Upload Logo</button>
                </form>
                <?php if (!empty($company['logo_path'])): ?>
                    <form method="post" action="<?= e(base_url('settings/removeCompanyLogo')) ?>" class="mt-2">
                        <input type="hidden" name="_csrf" value="<?= e((string) $csrf) ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Remove this company logo?')">Remove Logo</button>
                    </form>
                <?php endif; ?>
                <div class="text-gray mt-2" style="font-size:.75rem">Accepted: PNG, JPG, WebP. Maximum size: 2 MB.</div>
            </div>
        </div>
    </div>
</div>

<?php $stat = $statutorySettings ?? []; ?>
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap mb-3">
            <div>
                <h5 class="mb-1">Statutory Registration Details</h5>
                <p class="text-gray mb-0" style="font-size:.86rem">Used on PAYE, NAPSA, NHIMA reports and statutory portal templates.</p>
            </div>
        </div>
        <form method="post" action="<?= e(base_url('settings/updateStatutorySettings')) ?>" class="row g-3">
            <input type="hidden" name="_csrf" value="<?= e((string) $csrf) ?>">
            <div class="col-md-6">
                <label class="form-label">Registered Employer Name</label>
                <input type="text" name="statutory_registered_employer_name" class="form-control" value="<?= e((string)($stat['statutory_registered_employer_name'] ?? '')) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">NAPSA Account Number</label>
                <input type="text" name="statutory_napsa_account_number" class="form-control" value="<?= e((string)($stat['statutory_napsa_account_number'] ?? '')) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Company TPIN</label>
                <input type="text" name="statutory_tpin" class="form-control" value="<?= e((string)($stat['statutory_tpin'] ?? '')) ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">PAYE Account Number</label>
                <input type="text" name="statutory_paye_account_number" class="form-control" value="<?= e((string)($stat['statutory_paye_account_number'] ?? '')) ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">NHIMA Employer Number</label>
                <input type="text" name="statutory_nhima_employer_number" class="form-control" value="<?= e((string)($stat['statutory_nhima_employer_number'] ?? '')) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">Contact Person</label>
                <input type="text" name="statutory_contact_person" class="form-control" value="<?= e((string)($stat['statutory_contact_person'] ?? '')) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">Contact Phone</label>
                <input type="text" name="statutory_contact_phone" class="form-control" value="<?= e((string)($stat['statutory_contact_phone'] ?? '')) ?>">
            </div>
            <div class="col-12 d-flex justify-content-end">
                <button type="submit" class="btn btn-primary">Save Statutory Details</button>
            </div>
        </form>
    </div>
</div>

<?php $doc = $documentSettings ?? []; ?>
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap mb-3">
            <div>
                <h5 class="mb-1">Document Letterhead & Signatures</h5>
                <p class="text-gray mb-0" style="font-size:.86rem">Used by report PDFs, letters, contracts, payslips, and other company documents.</p>
            </div>
        </div>
        <form method="post" action="<?= e(base_url('settings/updateDocumentSettings')) ?>" class="row g-3">
            <input type="hidden" name="_csrf" value="<?= e((string) $csrf) ?>">
            <div class="col-md-4">
                <label class="form-label">Default Signatory Name</label>
                <input type="text" name="document_default_signatory_name" class="form-control" value="<?= e((string)($doc['document_default_signatory_name'] ?? '')) ?>" placeholder="e.g. Emmanuel Libati">
            </div>
            <div class="col-md-4">
                <label class="form-label">Default Signatory Title</label>
                <input type="text" name="document_default_signatory_title" class="form-control" value="<?= e((string)($doc['document_default_signatory_title'] ?? '')) ?>" placeholder="e.g. Managing Director">
            </div>
            <div class="col-md-4">
                <label class="form-label">Signature Placeholders</label>
                <select name="document_signature_placeholders_enabled" class="form-select">
                    <?php $sigEnabled = (string)($doc['document_signature_placeholders_enabled'] ?? '1'); ?>
                    <option value="1" <?= $sigEnabled === '1' ? 'selected' : '' ?>>Show on documents</option>
                    <option value="0" <?= $sigEnabled === '0' ? 'selected' : '' ?>>Hide by default</option>
                </select>
            </div>
            <div class="col-12">
                <label class="form-label">Letterhead Footer</label>
                <textarea name="document_letterhead_footer" class="form-control" rows="3" placeholder="Registration number, address, confidentiality text, or legal footer"><?= e((string)($doc['document_letterhead_footer'] ?? '')) ?></textarea>
            </div>
            <div class="col-12 d-flex justify-content-end">
                <button type="submit" class="btn btn-primary">Save Document Settings</button>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap mb-3">
            <div>
                <h5 class="mb-1">Payroll Policy</h5>
                <p class="text-gray mb-0" style="font-size:.86rem">Company-level rates used by payroll and employee profiles.</p>
            </div>
        </div>
        <form method="post" action="<?= e(base_url('settings/updatePayrollPolicy')) ?>" class="row g-3 align-items-end">
            <input type="hidden" name="_csrf" value="<?= e((string) $csrf) ?>">
            <div class="col-md-3">
                <label class="form-label">Contract Gratuity Rate (%)</label>
                <input type="number" name="gratuity_rate_percent" class="form-control" min="0" max="100" step="0.01" value="<?= e((string) ($gratuityRate ?? 5)) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Qualifies After (Years)</label>
                <input type="number" name="gratuity_qualifying_years" class="form-control" min="0" max="50" step="0.01" value="<?= e((string) ($gratuityQualifyingYears ?? 2)) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Calculation Basis</label>
                <?php $basis = (string) ($gratuityBasis ?? 'annual_basic_earned'); ?>
                <select name="gratuity_basis" class="form-select">
                    <option value="annual_basic_earned" <?= $basis === 'annual_basic_earned' ? 'selected' : '' ?>>Annual basic earned</option>
                    <option value="monthly_basic_served" <?= $basis === 'monthly_basic_served' ? 'selected' : '' ?>>Monthly basic served</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Payment Timing</label>
                <input type="text" name="gratuity_payment_timing" class="form-control" value="<?= e((string) ($gratuityPaymentTiming ?? 'End of contract')) ?>">
            </div>
            <div class="col-md-9">
                <small class="text-gray">Example: 5% of basic annual salary earned for each year served, payable at the end of contract after 2 years.</small>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary">Save Payroll Policy</button>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="get" action="<?= e(base_url('settings/index')) ?>" class="row g-3 align-items-end">
            <div class="col-md-8">
                <label class="form-label">Search</label>
                <input type="text" class="form-control" name="search" value="<?= e((string) ($search ?? '')) ?>" placeholder="Key or value">
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button class="btn btn-outline-primary" type="submit">Search</button>
                <a href="<?= e(base_url('settings/index')) ?>" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body table-responsive">
        <table class="table table-striped align-middle">
            <thead>
                <tr>
                    <th>Key</th>
                    <th>Value</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($settings)): ?>
                <tr><td colspan="3" class="text-center text-gray">No settings found.</td></tr>
            <?php else: ?>
                <?php foreach ($settings as $setting): ?>
                    <tr>
                        <td><?= e((string) ($setting['setting_key'] ?? '')) ?></td>
                        <td><?= e((string) ($setting['setting_value'] ?? '')) ?></td>
                        <td class="text-end">
                            <a href="<?= e(base_url('settings/edit/' . (string) $setting['id'])) ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                            <form method="post" action="<?= e(base_url('settings/delete/' . (string) $setting['id'])) ?>" class="d-inline">
                                <input type="hidden" name="_csrf" value="<?= e((string) $csrf) ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this setting?');">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
