<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="mb-0">Edit Affiliate</h4>
        <p class="text-muted mb-0 small"><?= e((string)$affiliate['full_name']) ?> &bull; <?= e((string)$affiliate['affiliate_code']) ?></p>
    </div>
    <a href="<?= e(base_url('superadmin/invoice/affiliateView/' . (string)$affiliate['id'])) ?>" class="btn btn-sm btn-outline-secondary">Back</a>
</div>

<?php if (!empty($flash)): ?><div class="alert alert-success"><?= e((string)$flash) ?></div><?php endif; ?>
<?php if (!empty($flashErr)): ?><div class="alert alert-danger"><?= e((string)$flashErr) ?></div><?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="post" action="<?= e(base_url('superadmin/invoice/affiliateUpdate/' . (string)$affiliate['id'])) ?>">
            <input type="hidden" name="_csrf" value="<?= e((string)$csrf) ?>">
            <h6 class="fw-bold mb-3">Affiliate Identity</h6>
            <div class="row g-3">
                <div class="col-md-4"><label class="form-label">Affiliate Type</label><select name="affiliate_type" class="form-select"><?php foreach (['Individual','Company','Consultant','Reseller','Agency'] as $type): ?><option <?= (string)($affiliate['affiliate_type'] ?? 'Individual') === $type ? 'selected' : '' ?>><?= e($type) ?></option><?php endforeach; ?></select></div>
                <div class="col-md-6"><label class="form-label">Full Name</label><input name="full_name" class="form-control" value="<?= e((string)$affiliate['full_name']) ?>" required></div>
                <div class="col-md-2"><label class="form-label">KYC Status</label><select name="kyc_status" class="form-select"><?php foreach (['Draft','Pending Review','Approved','Rejected'] as $status): ?><option <?= (string)($affiliate['kyc_status'] ?? 'Draft') === $status ? 'selected' : '' ?>><?= e($status) ?></option><?php endforeach; ?></select></div>
                <div class="col-md-6"><label class="form-label">Trading Name</label><input name="trading_name" class="form-control" value="<?= e((string)($affiliate['trading_name'] ?? '')) ?>"></div>
                <div class="col-md-6"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="<?= e((string)$affiliate['email']) ?>" required></div>
                <div class="col-md-6"><label class="form-label">Alternate Email</label><input type="email" name="alternate_email" class="form-control" value="<?= e((string)($affiliate['alternate_email'] ?? '')) ?>"></div>
                <div class="col-md-3"><label class="form-label">Phone</label><input name="phone" class="form-control" value="<?= e((string)($affiliate['phone'] ?? '')) ?>"></div>
                <div class="col-md-3"><label class="form-label">Alternate Phone</label><input name="alternate_phone" class="form-control" value="<?= e((string)($affiliate['alternate_phone'] ?? '')) ?>"></div>
                <div class="col-md-4"><label class="form-label">NRC Number</label><input name="nrc_number" class="form-control" value="<?= e((string)($affiliate['nrc_number'] ?? '')) ?>"></div>
                <div class="col-md-4"><label class="form-label">TPIN</label><input name="tpin" class="form-control" value="<?= e((string)($affiliate['tpin'] ?? '')) ?>"></div>
                <div class="col-md-4"><label class="form-label">Date of Birth / Incorporation</label><input type="date" name="date_of_birth" class="form-control" value="<?= e((string)($affiliate['date_of_birth'] ?? '')) ?>"></div>
                <div class="col-md-4"><label class="form-label">City</label><input name="city" class="form-control" value="<?= e((string)($affiliate['city'] ?? '')) ?>"></div>
                <div class="col-md-4"><label class="form-label">Province</label><input name="province" class="form-control" value="<?= e((string)($affiliate['province'] ?? '')) ?>"></div>
                <div class="col-12"><label class="form-label">Address</label><textarea name="address" class="form-control" rows="2"><?= e((string)($affiliate['address'] ?? '')) ?></textarea></div>
                <div class="col-12"><label class="form-label">KYC Rejection Reason</label><textarea name="kyc_rejection_reason" class="form-control" rows="2"><?= e((string)($affiliate['kyc_rejection_reason'] ?? '')) ?></textarea></div>
            </div>

            <hr class="my-4">
            <h6 class="fw-bold mb-3">Commission & Payout Details</h6>
            <div class="row g-3">
                <div class="col-md-3"><label class="form-label">Commission %</label><input type="number" step="0.01" min="0" max="100" name="commission_rate" class="form-control" value="<?= e((string)$affiliate['commission_rate']) ?>"></div>
                <div class="col-md-3"><label class="form-label">Commission Basis</label><select name="commission_basis" class="form-select"><?php foreach (['Paid Amount','Invoice Amount','Net Amount'] as $basis): ?><option <?= (string)($affiliate['commission_basis'] ?? 'Paid Amount') === $basis ? 'selected' : '' ?>><?= e($basis) ?></option><?php endforeach; ?></select></div>
                <div class="col-md-3"><label class="form-label">Duration</label><select name="commission_duration" class="form-select"><?php foreach (['First Year','Lifetime','Fixed Months'] as $duration): ?><option <?= (string)($affiliate['commission_duration'] ?? 'First Year') === $duration ? 'selected' : '' ?>><?= e($duration) ?></option><?php endforeach; ?></select></div>
                <div class="col-md-3"><label class="form-label">Fixed Months</label><input type="number" min="0" name="commission_months" class="form-control" value="<?= e((string)($affiliate['commission_months'] ?? '')) ?>"></div>
                <div class="col-md-3"><label class="form-label">One-off Bonus</label><input type="number" step="0.01" min="0" name="one_off_bonus" class="form-control" value="<?= e((string)($affiliate['one_off_bonus'] ?? '0.00')) ?>"></div>
                <div class="col-md-3"><label class="form-label">Payout Tax %</label><input type="number" step="0.01" min="0" max="100" name="payout_tax_rate" class="form-control" value="<?= e((string)($affiliate['payout_tax_rate'] ?? '0.00')) ?>"></div>
                <div class="col-md-3"><label class="form-label">Payout Method</label><input name="payout_method" class="form-control" value="<?= e((string)($affiliate['payout_method'] ?? '')) ?>"></div>
                <div class="col-md-3"><label class="form-label">Bank Name</label><input name="bank_name" class="form-control" value="<?= e((string)($affiliate['bank_name'] ?? '')) ?>"></div>
                <div class="col-md-3"><label class="form-label">Mobile Money Number</label><input name="mobile_money_number" class="form-control" value="<?= e((string)($affiliate['mobile_money_number'] ?? '')) ?>"></div>
                <div class="col-md-4"><label class="form-label">Account Name</label><input name="bank_account_name" class="form-control" value="<?= e((string)($affiliate['bank_account_name'] ?? '')) ?>"></div>
                <div class="col-md-4"><label class="form-label">Account Number</label><input name="bank_account_number" class="form-control" value="<?= e((string)($affiliate['bank_account_number'] ?? '')) ?>"></div>
                <div class="col-md-4"><label class="form-label">Additional Payout Details</label><textarea name="payout_details" class="form-control" rows="1"><?= e((string)($affiliate['payout_details'] ?? '')) ?></textarea></div>
            </div>

            <hr class="my-4">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Status</label>
                    <select name="is_active" class="form-select">
                        <option value="1" <?= (int)$affiliate['is_active'] === 1 ? 'selected' : '' ?>>Active</option>
                        <option value="0" <?= (int)$affiliate['is_active'] === 0 ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
                <div class="col-md-8">
                    <label class="form-label">Reset Temporary Password</label>
                    <input type="password" name="password" class="form-control" placeholder="Leave blank to keep existing password">
                    <div class="form-text">If entered, affiliate must change it on next login.</div>
                </div>
            </div>

            <div class="mt-4"><button class="btn text-white" style="background:#7c3aed">Save Affiliate</button></div>
        </form>
    </div>
</div>
