<?php
$readiness = $readiness ?? ['score' => 0, 'checks' => [], 'missing' => []];
$score = (int) ($readiness['score'] ?? 0);
?>
<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h4 class="mb-0">Profile & KYC</h4>
        <p class="text-muted mb-0 small">Keep payout and compliance details current for commission processing.</p>
    </div>
    <span class="badge bg-light text-dark border">KYC: <?= e((string)($affiliate['kyc_status'] ?? 'Draft')) ?></span>
</div>

<?php if (!empty($flash)): ?><div class="alert alert-success"><?= e((string)$flash) ?></div><?php endif; ?>
<?php if (!empty($flashErr)): ?><div class="alert alert-danger"><?= e((string)$flashErr) ?></div><?php endif; ?>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold mb-0">Profile Details</h6>
                    <span class="badge <?= $score >= 100 ? 'bg-success' : 'bg-warning text-dark' ?>"><?= e((string)$score) ?>% complete</span>
                </div>
                <form method="post" action="<?= e(base_url('affiliate/dashboard/profileUpdate')) ?>">
                    <input type="hidden" name="_csrf" value="<?= e((string)$csrf) ?>">
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label">Legal Name</label><input class="form-control" value="<?= e((string)($affiliate['full_name'] ?? '')) ?>" disabled></div>
                        <div class="col-md-6"><label class="form-label">Trading Name</label><input name="trading_name" class="form-control" value="<?= e((string)($affiliate['trading_name'] ?? '')) ?>"></div>
                        <div class="col-md-6"><label class="form-label">Primary Email</label><input class="form-control" value="<?= e((string)($affiliate['email'] ?? '')) ?>" disabled></div>
                        <div class="col-md-6"><label class="form-label">Alternate Email</label><input type="email" name="alternate_email" class="form-control" value="<?= e((string)($affiliate['alternate_email'] ?? '')) ?>"></div>
                        <div class="col-md-6"><label class="form-label">Phone</label><input class="form-control" value="<?= e((string)($affiliate['phone'] ?? '')) ?>" disabled></div>
                        <div class="col-md-6"><label class="form-label">Alternate Phone</label><input name="alternate_phone" class="form-control" value="<?= e((string)($affiliate['alternate_phone'] ?? '')) ?>"></div>
                        <div class="col-md-6"><label class="form-label">NRC / Registration Number</label><input name="nrc_number" class="form-control" value="<?= e((string)($affiliate['nrc_number'] ?? '')) ?>"></div>
                        <div class="col-md-6"><label class="form-label">TPN</label><input name="tpin" class="form-control" value="<?= e((string)($affiliate['tpin'] ?? '')) ?>"></div>
                        <div class="col-12"><label class="form-label">Physical Address</label><textarea name="address" class="form-control" rows="2"><?= e((string)($affiliate['address'] ?? '')) ?></textarea></div>
                        <div class="col-md-6"><label class="form-label">City</label><input name="city" class="form-control" value="<?= e((string)($affiliate['city'] ?? '')) ?>"></div>
                        <div class="col-md-6"><label class="form-label">Province</label><input name="province" class="form-control" value="<?= e((string)($affiliate['province'] ?? '')) ?>"></div>
                    </div>

                    <hr class="my-4">
                    <h6 class="fw-bold mb-3">Payout Details</h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Payout Method</label>
                            <select name="payout_method" class="form-select">
                                <?php $method = (string)($affiliate['payout_method'] ?? ''); ?>
                                <?php foreach (['' => 'Select method', 'Bank Transfer' => 'Bank Transfer', 'Mobile Money' => 'Mobile Money', 'Cheque' => 'Cheque', 'Other' => 'Other'] as $value => $label): ?>
                                    <option value="<?= e($value) ?>" <?= $method === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6"><label class="form-label">Bank Name</label><input name="bank_name" class="form-control" value="<?= e((string)($affiliate['bank_name'] ?? '')) ?>"></div>
                        <div class="col-md-6"><label class="form-label">Account Name</label><input name="bank_account_name" class="form-control" value="<?= e((string)($affiliate['bank_account_name'] ?? '')) ?>"></div>
                        <div class="col-md-6"><label class="form-label">Account Number</label><input name="bank_account_number" class="form-control" value="<?= e((string)($affiliate['bank_account_number'] ?? '')) ?>"></div>
                        <div class="col-md-6"><label class="form-label">Mobile Money Number</label><input name="mobile_money_number" class="form-control" value="<?= e((string)($affiliate['mobile_money_number'] ?? '')) ?>"></div>
                        <div class="col-md-6"><label class="form-label">Additional Payout Details</label><textarea name="payout_details" class="form-control" rows="1"><?= e((string)($affiliate['payout_details'] ?? '')) ?></textarea></div>
                    </div>
                    <button class="btn text-white mt-4" style="background:#7c3aed">Save Profile</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <h6 class="fw-bold mb-3">Readiness Checklist</h6>
                <div class="progress mb-3" style="height:8px">
                    <div class="progress-bar" role="progressbar" style="width: <?= e((string)$score) ?>%;background:#7c3aed" aria-valuenow="<?= e((string)$score) ?>" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
                <?php foreach (($readiness['checks'] ?? []) as $label => $done): ?>
                    <div class="d-flex align-items-center gap-2 py-2 border-bottom">
                        <span class="badge <?= $done ? 'bg-success' : 'bg-light text-dark border' ?>"><i class="bi <?= $done ? 'bi-check2' : 'bi-circle' ?>"></i></span>
                        <span><?= e((string)$label) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <h6 class="fw-bold mb-3">Upload KYC Document</h6>
                <form method="post" action="<?= e(base_url('affiliate/dashboard/documentUpload')) ?>" enctype="multipart/form-data">
                    <input type="hidden" name="_csrf" value="<?= e((string)$csrf) ?>">
                    <div class="mb-3"><label class="form-label">Document Type</label><select name="document_type" class="form-select"><option>NRC</option><option>TPIN</option><option>Affiliate Agreement</option><option>Bank Proof</option><option>Proof of Address</option><option>Other</option></select></div>
                    <div class="mb-3"><label class="form-label">File</label><input type="file" name="document_file" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required></div>
                    <div class="mb-3"><label class="form-label">Notes</label><textarea name="notes" class="form-control" rows="2"></textarea></div>
                    <button class="btn btn-outline-primary"><i class="bi bi-upload me-1"></i>Upload</button>
                </form>
            </div>
        </div>
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h6 class="fw-bold mb-3">Submitted Documents</h6>
                <?php foreach ($documents as $doc): ?>
                    <div class="border-bottom py-2"><strong><?= e((string)$doc['document_type']) ?></strong><div class="text-muted small"><?= e((string)$doc['file_name']) ?> &bull; <?= e((string)$doc['created_at']) ?></div></div>
                <?php endforeach; if (empty($documents)): ?><p class="text-muted text-center py-4 mb-0">No documents uploaded yet.</p><?php endif; ?>
            </div>
        </div>
    </div>
</div>
