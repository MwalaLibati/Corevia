<div class="d-flex align-items-center justify-content-between mr-bottom-30">
    <div>
        <h2 class="text-dark">Onboarding Request</h2>
        <p class="text-gray mb-0"><?= e((string)$request['invited_full_name']) ?></p>
    </div>
    <a href="<?= e(base_url('onboarding/index')) ?>" class="btn btn-outline-secondary">Back</a>
</div>

<?php if (!empty($flashSuccess)): ?><div class="alert alert-success"><?= e((string)$flashSuccess) ?></div><?php endif; ?>
<?php if (!empty($flashError)): ?><div class="alert alert-danger"><?= e((string)$flashError) ?></div><?php endif; ?>

<div class="row g-3">
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <h5 class="mb-0">Secure Link</h5>
                    <span class="badge bg-secondary"><?= e((string)$request['status']) ?></span>
                </div>
                <label class="form-label">Link to send</label>
                <div class="input-group mb-3">
                    <input type="text" class="form-control" value="<?= e((string)$publicLink) ?>" readonly id="onboardingLink">
                    <button class="btn btn-outline-primary" type="button" onclick="navigator.clipboard.writeText(document.getElementById('onboardingLink').value)">Copy</button>
                </div>
                <div class="small text-gray">Expires: <?= e((string)$request['expires_at']) ?></div>
                <hr>
                <dl class="row mb-0">
                    <?php if (!empty($request['selected_employee_number'])): ?>
                        <dt class="col-5">Linked Employee</dt><dd class="col-7"><?= e((string)$request['selected_employee_number']) ?> - <?= e((string)($request['selected_employee_name'] ?? '')) ?></dd>
                    <?php endif; ?>
                    <dt class="col-5">Department</dt><dd class="col-7"><?= e((string)($request['department_name'] ?? 'Unassigned')) ?></dd>
                    <dt class="col-5">Designation</dt><dd class="col-7"><?= e((string)($request['designation'] ?? '-')) ?></dd>
                    <dt class="col-5">Employment Type</dt><dd class="col-7"><?= e((string)$request['employment_type']) ?></dd>
                    <dt class="col-5">Expected Start</dt><dd class="col-7"><?= e((string)($request['expected_start_date'] ?? '-')) ?></dd>
                </dl>
                <?php
                $requiredFields = json_decode((string)($request['required_fields_json'] ?? ''), true);
                $requiredLabels = [
                    'full_name' => 'Full name',
                    'email' => 'Email',
                    'phone' => 'Phone',
                    'nrc_number' => 'NRC number',
                    'date_of_birth' => 'Date of birth',
                    'napsa_number' => 'NAPSA number',
                    'tpin' => 'TPIN',
                    'nhima_number' => 'NHIMA number',
                    'bank_name' => 'Bank name',
                    'bank_account_number' => 'Bank account number',
                    'next_of_kin_name' => 'Next of kin name',
                    'next_of_kin_phone' => 'Next of kin phone',
                ];
                ?>
                <?php if (is_array($requiredFields) && $requiredFields !== []): ?>
                    <hr>
                    <div class="small text-gray mb-1">Requested information</div>
                    <div class="d-flex flex-wrap gap-1">
                        <?php foreach ($requiredFields as $field): ?>
                            <?php if (isset($requiredLabels[$field])): ?>
                                <span class="badge bg-light text-dark border"><?= e($requiredLabels[$field]) ?></span>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <?php if (in_array((string)$request['status'], ['Sent','Opened'], true)): ?>
                    <form method="post" action="<?= e(base_url('onboarding/cancel/' . (string)$request['id'])) ?>" class="mt-3">
                        <input type="hidden" name="_csrf" value="<?= e((string)$csrf) ?>">
                        <button type="submit" class="btn btn-outline-danger" onclick="return confirm('Cancel this onboarding link?')">Cancel Link</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <h5 class="mb-0">Submitted Details</h5>
                        <?php if (!empty($approvalStep)): ?>
                            <div class="text-gray small mt-1">
                                Approval step: <?= e((string)$approvalStep['step_name']) ?> by <?= e((string)$approvalStep['required_role']) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php if ((string)$request['status'] === 'Submitted' && !empty($canApproveOnboarding)): ?>
                        <form method="post" action="<?= e(base_url('onboarding/approve/' . (string)$request['id'])) ?>">
                            <input type="hidden" name="_csrf" value="<?= e((string)$csrf) ?>">
                            <button class="btn btn-success" type="submit" onclick="return confirm('Approve and create employee profile?')">
                                <?= e((string)($approvalStep['action_label'] ?? 'Approve & Create Employee')) ?>
                            </button>
                        </form>
                    <?php elseif ((string)$request['status'] === 'Submitted'): ?>
                        <span class="badge bg-warning text-dark">Awaiting <?= e((string)($approvalStep['required_role'] ?? 'approval')) ?></span>
                    <?php endif; ?>
                </div>

                <div class="row g-3">
                    <?php foreach ([
                        'Full Name' => $request['full_name'] ?? '',
                        'Email' => $request['email'] ?? '',
                        'Phone' => $request['phone'] ?? '',
                        'NRC Number' => $request['nrc_number'] ?? '',
                        'Date of Birth' => $request['date_of_birth'] ?? '',
                        'NAPSA Number' => $request['napsa_number'] ?? '',
                        'TPIN' => $request['tpin'] ?? '',
                        'NHIMA Number' => $request['nhima_number'] ?? '',
                        'Bank' => trim((string)($request['bank_name'] ?? '') . ' ' . (string)($request['bank_account_number'] ?? '')),
                        'Next of Kin' => trim((string)($request['next_of_kin_name'] ?? '') . ' ' . (string)($request['next_of_kin_phone'] ?? '')),
                    ] as $label => $value): ?>
                        <div class="col-md-6">
                            <div class="text-gray small"><?= e($label) ?></div>
                            <strong><?= e((string)($value !== '' ? $value : '-')) ?></strong>
                        </div>
                    <?php endforeach; ?>
                    <div class="col-12">
                        <div class="text-gray small">Address / Notes</div>
                        <div><?= nl2br(e((string)(($request['address'] ?? '') . "\n" . ($request['notes'] ?? '')))) ?></div>
                    </div>
                </div>

                <hr>
                <h6>Uploaded Documents</h6>
                <?php if (empty($documents)): ?>
                    <p class="text-gray mb-0">No documents uploaded.</p>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($documents as $doc): ?>
                            <a class="list-group-item list-group-item-action" href="<?= e(base_url((string)$doc['stored_path'])) ?>" target="_blank">
                                <?= e((string)$doc['original_name']) ?>
                                <span class="text-gray small">(<?= e((string)round(((int)$doc['file_size']) / 1024, 1)) ?> KB)</span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
