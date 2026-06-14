<div class="d-flex align-items-center justify-content-between mr-bottom-30">
    <div>
        <h2 class="text-dark">Email &amp; Notification Settings</h2>
        <p class="text-gray mb-0">Configure SMTP for employee payslips, contracts, and contract reminders.</p>
    </div>
    <a href="<?= e(base_url('settings/index')) ?>" class="btn btn-outline-secondary">System Settings</a>
</div>

<?php if (!empty($flashSuccess)): ?>
    <div class="alert alert-success"><?= e((string) $flashSuccess) ?></div>
<?php endif; ?>
<?php if (!empty($flashError)): ?>
    <div class="alert alert-danger"><?= e((string) $flashError) ?></div>
<?php endif; ?>

<?php
$emailTemplates = $emailTemplates ?? [];
$emailTokens = $emailTokens ?? [];
$contractTemplate = $emailTemplates['contract'] ?? [];
$payslipTemplate = $emailTemplates['payslip'] ?? [];
?>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <form method="post" action="<?= e(base_url('settings/updateEmail')) ?>" class="row g-3">
                    <input type="hidden" name="_csrf" value="<?= e((string) $csrf) ?>">

                    <div class="col-12">
                        <label class="form-label">Notifications Enabled</label>
                        <select name="email_notifications_enabled" class="form-select">
                            <option value="1" <?= ($s['email_notifications_enabled'] ?? '1') === '1' ? 'selected' : '' ?>>Yes &mdash; allow the system to send emails</option>
                            <option value="0" <?= ($s['email_notifications_enabled'] ?? '1') === '0' ? 'selected' : '' ?>>No &mdash; disable employee and contract emails</option>
                        </select>
                    </div>

                    <div class="col-md-8">
                        <label class="form-label">SMTP Host</label>
                        <input type="text" name="smtp_host" class="form-control" value="<?= e((string) ($s['smtp_host'] ?? '')) ?>" placeholder="smtp.gmail.com">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">SMTP Port</label>
                        <input type="number" name="smtp_port" class="form-control" value="<?= e((string) ($s['smtp_port'] ?? '587')) ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Encryption</label>
                        <select name="smtp_encryption" class="form-select">
                            <?php foreach (['tls' => 'TLS (port 587)', 'ssl' => 'SSL (port 465)', 'none' => 'None (port 25)'] as $val => $label): ?>
                                <option value="<?= e($val) ?>" <?= ($s['smtp_encryption'] ?? 'tls') === $val ? 'selected' : '' ?>><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">SMTP Username</label>
                        <input type="text" name="smtp_username" class="form-control" value="<?= e((string) ($s['smtp_username'] ?? '')) ?>" placeholder="your@gmail.com" autocomplete="off">
                    </div>

                    <div class="col-12">
                        <label class="form-label">SMTP Password <small class="text-gray">(leave blank to keep existing)</small></label>
                        <input type="password" name="smtp_password" class="form-control" placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;" autocomplete="new-password">
                        <div class="form-text">
                            <?= !empty($passwordSaved) ? 'A password is saved securely and is never shown here.' : 'No SMTP password is saved yet.' ?>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">From Email Address</label>
                        <input type="email" name="smtp_from_email" class="form-control" value="<?= e((string) ($s['smtp_from_email'] ?? '')) ?>" placeholder="payroll@stonesoft.local">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">From Name</label>
                        <input type="text" name="smtp_from_name" class="form-control" value="<?= e((string) ($s['smtp_from_name'] ?? '')) ?>" placeholder="<?= e(app_product_name()) ?>">
                    </div>

                    <div class="col-12">
                        <label class="form-label">HR / Admin Notification Email <small class="text-gray">(receives contract alerts)</small></label>
                        <input type="email" name="smtp_hr_email" class="form-control" value="<?= e((string) ($s['smtp_hr_email'] ?? '')) ?>" placeholder="hr@stonesoft.local">
                    </div>

                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">Save Settings</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <h6 class="mb-3">Test Email Connection</h6>
                <form method="post" action="<?= e(base_url('settings/testEmail')) ?>">
                    <input type="hidden" name="_csrf" value="<?= e((string) $csrf) ?>">
                    <div class="mb-3">
                        <label class="form-label">Send test to</label>
                        <input type="email" name="test_email" class="form-control" value="<?= e((string) ($s['smtp_hr_email'] ?? '')) ?>" required placeholder="your@email.com">
                    </div>
                    <button type="submit" class="btn btn-outline-primary">Send Test Email</button>
                </form>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h6 class="mb-2">When are notifications sent?</h6>
                <ul class="mb-0 small text-gray">
                    <li class="mb-2"><strong class="text-success-emphasis">Payslips</strong> &mdash; sent to each employee's email from the payroll run screen.</li>
                    <li class="mb-2"><strong class="text-success-emphasis">Contracts</strong> &mdash; sent to each employee's email from the contracts screen.</li>
                    <li class="mb-2"><strong class="text-warning-emphasis">Expiring Soon</strong> &mdash; 30 days before contract end date. Sent to HR email + employee email.</li>
                    <li class="mb-2"><strong class="text-danger-emphasis">Expired</strong> &mdash; when a contract passes its end date without renewal. Sent to HR email.</li>
                    <li><strong class="text-success-emphasis">Renewed</strong> &mdash; immediately after a contract is renewed. Sent to HR email + employee email.</li>
                </ul>
                <hr>
                <p class="small text-gray mb-0">Notifications are triggered automatically on first dashboard load each day. You can also trigger them manually from the <a href="<?= e(base_url('contract/index')) ?>">Contracts page</a>.</p>
                <hr>
                <p class="small text-gray mb-0"><strong>Gmail users:</strong> Use an <a href="https://myaccount.google.com/apppasswords" target="_blank">App Password</a> (not your regular password). Encryption: TLS, Port: 587.</p>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mt-4">
    <div class="card-body">
        <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap mb-3">
            <div>
                <h5 class="mb-1">Email Templates & Signature</h5>
                <p class="text-gray mb-0 small">Edit the messages employees receive for contracts and payslips. Documents are attached automatically when enabled.</p>
            </div>
            <span class="badge bg-light text-dark border">Company controlled</span>
        </div>

        <form method="post" action="<?= e(base_url('settings/updateEmailTemplates')) ?>" class="row g-4">
            <input type="hidden" name="_csrf" value="<?= e((string) $csrf) ?>">

            <div class="col-lg-8">
                <div class="border rounded-3 p-3 mb-3">
                    <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap mb-3">
                        <h6 class="mb-0">Contract Email</h6>
                        <label class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" name="contract_attach_document" value="1" <?= !empty($contractTemplate['attach_document']) ? 'checked' : '' ?>>
                            <span class="form-check-label small">Attach contract document</span>
                        </label>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Subject</label>
                        <input type="text" name="contract_subject" class="form-control" value="<?= e((string) ($contractTemplate['subject'] ?? '')) ?>">
                    </div>
                    <div>
                        <label class="form-label">Message</label>
                        <textarea name="contract_body" class="form-control" rows="7"><?= e((string) ($contractTemplate['body'] ?? '')) ?></textarea>
                    </div>
                </div>

                <div class="border rounded-3 p-3 mb-3">
                    <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap mb-3">
                        <h6 class="mb-0">Payslip Email</h6>
                        <label class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" name="payslip_attach_document" value="1" <?= !empty($payslipTemplate['attach_document']) ? 'checked' : '' ?>>
                            <span class="form-check-label small">Attach payslip document</span>
                        </label>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Subject</label>
                        <input type="text" name="payslip_subject" class="form-control" value="<?= e((string) ($payslipTemplate['subject'] ?? '')) ?>">
                    </div>
                    <div>
                        <label class="form-label">Message</label>
                        <textarea name="payslip_body" class="form-control" rows="7"><?= e((string) ($payslipTemplate['body'] ?? '')) ?></textarea>
                    </div>
                </div>

                <div class="border rounded-3 p-3">
                    <h6 class="mb-3">Email Signature</h6>
                    <textarea name="signature" class="form-control" rows="5"><?= e((string) ($emailTemplates['signature'] ?? '')) ?></textarea>
                    <div class="form-text">This signature is added below contract and payslip emails.</div>
                </div>

                <div class="mt-3">
                    <button type="submit" class="btn btn-primary">Save Email Templates</button>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="bg-light border rounded-3 p-3 h-100">
                    <h6 class="mb-2">Available Fields</h6>
                    <p class="small text-gray">Use these fields inside the subject, message, or signature. The system fills them in automatically.</p>
                    <?php foreach ($emailTokens as $group => $tokens): ?>
                        <div class="mb-3">
                            <div class="fw-semibold small mb-2"><?= e((string) $group) ?></div>
                            <div class="d-flex flex-wrap gap-2">
                                <?php foreach ($tokens as $token => $label): ?>
                                    <span class="badge bg-white text-dark border" title="<?= e((string) $label) ?>"><?= e((string) $token) ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <hr>
                    <p class="small text-gray mb-0">Example: <strong>Dear {{employee_name}}</strong> becomes the employee's actual name when the email is sent.</p>
                </div>
            </div>
        </form>
    </div>
</div>
