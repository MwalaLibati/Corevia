<?php $s = $dashboard['summary'] ?? []; ?>
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="mb-0"><?= e((string)$affiliate['full_name']) ?></h4>
        <p class="text-muted mb-0 small"><?= e((string)$affiliate['email']) ?> &bull; Code <?= e((string)$affiliate['affiliate_code']) ?></p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= e(base_url('superadmin/invoice/affiliateEdit/' . (string)$affiliate['id'])) ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil-square me-1"></i>Edit Affiliate</a>
        <a href="<?= e(base_url('superadmin/invoice/affiliates')) ?>" class="btn btn-sm btn-outline-secondary">Back</a>
    </div>
</div>

<?php if (!empty($flash)): ?><div class="alert alert-success"><?= e((string)$flash) ?></div><?php endif; ?>
<?php if (!empty($flashErr)): ?><div class="alert alert-danger"><?= e((string)$flashErr) ?></div><?php endif; ?>

<div class="row g-3 mb-4">
    <?php foreach ([
        ['Companies', (string)($s['company_count'] ?? 0), 'bi-buildings', '#dbeafe', '#2563eb'],
        ['Active Companies', (string)($s['active_companies'] ?? 0), 'bi-check2-circle', '#dcfce7', '#16a34a'],
        ['Year Earnings', 'ZMW '.number_format((float)($s['current_year_commission'] ?? 0), 2), 'bi-graph-up-arrow', '#ede9fe', '#7c3aed'],
        ['Pending Payout', 'ZMW '.number_format((float)(($s['pending_commission'] ?? 0) + ($s['approved_commission'] ?? 0)), 2), 'bi-wallet2', '#fef3c7', '#d97706'],
    ] as $card): ?>
    <div class="col-sm-6 col-xl-3"><div class="ent-stat-card h-100" style="--ent-stat-accent:<?= e($card[4]) ?>"><span class="stat-icon" style="background:<?= e($card[3]) ?>;color:<?= e($card[4]) ?>"><i class="bi <?= e($card[2]) ?>"></i></span><span class="stat-label"><?= e($card[0]) ?></span><div class="stat-value" style="font-size:1.25rem"><?= e($card[1]) ?></div></div></div>
    <?php endforeach; ?>
</div>

<div class="row g-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                    <h6 class="fw-bold mb-0">Affiliate Profile & Compliance</h6>
                    <span class="badge <?= (int)$affiliate['is_active'] === 1 ? 'bg-success' : 'bg-secondary' ?>"><?= (int)$affiliate['is_active'] === 1 ? 'Active' : 'Inactive' ?></span>
                    <span class="badge bg-light text-dark border">KYC: <?= e((string)($affiliate['kyc_status'] ?? 'Draft')) ?></span>
                </div>
                <div class="row g-3 small">
                    <div class="col-md-3"><div class="text-muted">Affiliate Type</div><strong><?= e((string)($affiliate['affiliate_type'] ?? 'Individual')) ?></strong></div>
                    <div class="col-md-3"><div class="text-muted">Trading Name</div><strong><?= e((string)($affiliate['trading_name'] ?? '-')) ?></strong></div>
                    <div class="col-md-3"><div class="text-muted">NRC Number</div><strong><?= e((string)($affiliate['nrc_number'] ?? '-')) ?></strong></div>
                    <div class="col-md-3"><div class="text-muted">TPIN</div><strong><?= e((string)($affiliate['tpin'] ?? '-')) ?></strong></div>
                    <div class="col-md-3"><div class="text-muted">Payout Method</div><strong><?= e((string)($affiliate['payout_method'] ?? '-')) ?></strong></div>
                    <div class="col-md-3"><div class="text-muted">Commission Rate</div><strong><?= e((string)$affiliate['commission_rate']) ?>%</strong></div>
                    <div class="col-md-3"><div class="text-muted">Commission Basis</div><strong><?= e((string)($affiliate['commission_basis'] ?? 'Paid Amount')) ?></strong></div>
                    <div class="col-md-3"><div class="text-muted">Duration</div><strong><?= e((string)($affiliate['commission_duration'] ?? 'First Year')) ?></strong></div>
                    <div class="col-md-3"><div class="text-muted">One-off Bonus</div><strong>ZMW <?= number_format((float)($affiliate['one_off_bonus'] ?? 0), 2) ?></strong></div>
                    <div class="col-md-3"><div class="text-muted">Payout Tax</div><strong><?= e((string)($affiliate['payout_tax_rate'] ?? '0.00')) ?>%</strong></div>
                    <div class="col-md-3"><div class="text-muted">Bank</div><strong><?= e((string)($affiliate['bank_name'] ?? '-')) ?></strong></div>
                    <div class="col-md-3"><div class="text-muted">Account Name</div><strong><?= e((string)($affiliate['bank_account_name'] ?? '-')) ?></strong></div>
                    <div class="col-md-3"><div class="text-muted">Account Number</div><strong><?= e((string)($affiliate['bank_account_number'] ?? '-')) ?></strong></div>
                    <div class="col-md-3"><div class="text-muted">Mobile Money</div><strong><?= e((string)($affiliate['mobile_money_number'] ?? '-')) ?></strong></div>
                    <div class="col-12"><div class="text-muted">Address</div><strong><?= e((string)($affiliate['address'] ?? '-')) ?></strong></div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h6 class="fw-bold mb-3">Assign Company</h6>
                <form method="post" action="<?= e(base_url('superadmin/invoice/affiliateAssignCompany/' . (string)$affiliate['id'])) ?>">
                    <input type="hidden" name="_csrf" value="<?= e((string)$csrf) ?>">
                    <div class="mb-3"><label class="form-label">Company</label><select name="company_id" class="form-select" required>
                        <option value="">Select company</option>
                        <?php foreach ($companies as $c): ?>
                            <option value="<?= (int)$c['id'] ?>"><?= e((string)$c['name']) ?><?= in_array((int)$c['id'], $assignedCompanyIds, true) ? ' (already assigned)' : '' ?></option>
                        <?php endforeach; ?>
                    </select></div>
                    <div class="row g-2">
                        <div class="col-md-6"><label class="form-label">Status</label><select name="referral_status" class="form-select"><option>Active</option><option>Trial</option><option>Prospect</option><option>Suspended</option><option>Cancelled</option></select></div>
                        <div class="col-md-6"><label class="form-label">Override %</label><input type="number" step="0.01" min="0" max="100" name="commission_rate" class="form-control" placeholder="<?= e((string)$affiliate['commission_rate']) ?>"></div>
                        <div class="col-md-6"><label class="form-label">Referred At</label><input type="date" name="referred_at" class="form-control" value="<?= e(date('Y-m-d')) ?>"></div>
                        <div class="col-md-6"><label class="form-label">Notes</label><input name="notes" class="form-control"></div>
                    </div>
                    <button class="btn btn-sm text-white mt-3" style="background:#7c3aed">Assign Company</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold mb-0">Referred Companies</h6>
                    <form method="post" action="<?= e(base_url('superadmin/invoice/affiliateMarkPaid/' . (string)$affiliate['id'])) ?>" class="d-flex gap-2" onsubmit="return confirm('Mark all outstanding commissions as paid?')">
                        <input type="hidden" name="_csrf" value="<?= e((string)$csrf) ?>">
                        <input name="payout_reference" class="form-control form-control-sm" placeholder="Payout ref">
                        <button class="btn btn-sm btn-outline-success">Mark Paid</button>
                    </form>
                </div>
                <div class="table-responsive"><table class="table table-sm align-middle mb-0">
                    <thead><tr><th>Company</th><th>Status</th><th>Total</th><th>Unpaid</th></tr></thead>
                    <tbody>
                    <?php foreach (($dashboard['companies'] ?? []) as $c): ?>
                    <tr><td><strong><?= e((string)$c['company_name']) ?></strong><div class="text-muted small"><?= e((string)$c['company_email']) ?></div></td><td><?= e((string)$c['referral_status']) ?></td><td>ZMW <?= number_format((float)$c['total_commission'], 2) ?></td><td>ZMW <?= number_format((float)$c['unpaid_commission'], 2) ?></td></tr>
                    <?php endforeach; if (empty($dashboard['companies'])): ?><tr><td colspan="4" class="text-center text-muted py-4">No referred companies yet.</td></tr><?php endif; ?>
                    </tbody>
                </table></div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mt-4">
    <div class="card-body">
        <h6 class="fw-bold mb-3">Lead Pipeline</h6>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead><tr><th>Company</th><th>Contact</th><th>Stage</th><th>Value</th><th>Follow-up</th><th class="text-end">Update</th></tr></thead>
                <tbody>
                <?php foreach (($leads ?? []) as $lead): ?>
                    <tr>
                        <td><strong><?= e((string)$lead['company_name']) ?></strong><div class="text-muted small"><?= e((string)($lead['industry'] ?? '')) ?></div></td>
                        <td><?= e((string)($lead['contact_person'] ?? '-')) ?><div class="text-muted small"><?= e((string)($lead['contact_phone'] ?? '')) ?></div></td>
                        <td><span class="badge bg-light text-dark border"><?= e((string)$lead['stage']) ?></span></td>
                        <td>ZMW <?= number_format((float)($lead['estimated_value'] ?? 0), 2) ?></td>
                        <td><?= e((string)($lead['next_follow_up'] ?? '-')) ?></td>
                        <td class="text-end">
                            <form method="post" action="<?= e(base_url('superadmin/invoice/affiliateLeadUpdate/' . (string)$lead['id'])) ?>" class="d-flex gap-2 justify-content-end">
                                <input type="hidden" name="_csrf" value="<?= e((string)$csrf) ?>">
                                <select name="stage" class="form-select form-select-sm" style="max-width:150px">
                                    <?php foreach (['New','Contacted','Demo Scheduled','Negotiating','Won','Lost'] as $stage): ?><option <?= (string)$lead['stage'] === $stage ? 'selected' : '' ?>><?= e($stage) ?></option><?php endforeach; ?>
                                </select>
                                <select name="converted_company_id" class="form-select form-select-sm" style="max-width:180px">
                                    <option value="">No company link</option>
                                    <?php foreach ($companies as $c): ?><option value="<?= (int)$c['id'] ?>" <?= (int)($lead['converted_company_id'] ?? 0) === (int)$c['id'] ? 'selected' : '' ?>><?= e((string)$c['name']) ?></option><?php endforeach; ?>
                                </select>
                                <button class="btn btn-sm btn-outline-primary">Save</button>
                            </form>
                            <?php if ((string)$lead['stage'] !== 'Won'): ?>
                                <form method="post" action="<?= e(base_url('superadmin/invoice/affiliateLeadConvert/' . (string)$lead['id'])) ?>" class="d-flex gap-1 justify-content-end mt-1">
                                    <input type="hidden" name="_csrf" value="<?= e((string)$csrf) ?>">
                                    <select name="plan_id" class="form-select form-select-sm" style="max-width:180px">
                                        <option value="">No plan</option>
                                        <?php foreach ($plans as $plan): ?><option value="<?= (int)$plan['id'] ?>"><?= e((string)$plan['name']) ?></option><?php endforeach; ?>
                                    </select>
                                    <button class="btn btn-sm btn-outline-success">Convert</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; if (empty($leads)): ?><tr><td colspan="6" class="text-center text-muted py-4">No leads submitted yet.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mt-4">
    <div class="card-body">
        <h6 class="fw-bold mb-3">Affiliate Communication / Internal Note</h6>
        <form method="post" action="<?= e(base_url('superadmin/invoice/affiliateMessageCreate/' . (string)$affiliate['id'])) ?>">
            <input type="hidden" name="_csrf" value="<?= e((string)$csrf) ?>">
            <div class="row g-3">
                <div class="col-md-4"><label class="form-label">Visibility</label><select name="visibility" class="form-select"><option>Specific Affiliate</option><option>All Affiliates</option><option>Internal Note</option></select></div>
                <div class="col-md-8"><label class="form-label">Subject</label><input name="subject" class="form-control" required></div>
                <div class="col-12"><label class="form-label">Message / Note</label><textarea name="message" class="form-control" rows="3" required></textarea></div>
            </div>
            <button class="btn btn-sm btn-outline-primary mt-3">Save Message</button>
        </form>
    </div>
</div>

<div class="row g-4 mt-1">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h6 class="fw-bold mb-3">Create Payout Batch</h6>
                <form method="post" action="<?= e(base_url('superadmin/invoice/affiliatePayoutCreate/' . (string)$affiliate['id'])) ?>">
                    <input type="hidden" name="_csrf" value="<?= e((string)$csrf) ?>">
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label">Payment Method</label><select name="payment_method_id" class="form-select"><?php foreach ($paymentMethods as $method): ?><option value="<?= (int)$method['id'] ?>"><?= e((string)$method['name']) ?></option><?php endforeach; ?></select></div>
                        <div class="col-md-3"><label class="form-label">From</label><input type="date" name="period_from" class="form-control"></div>
                        <div class="col-md-3"><label class="form-label">To</label><input type="date" name="period_to" class="form-control"></div>
                    </div>
                    <button class="btn btn-sm btn-outline-success mt-3">Create Eligible Payout</button>
                </form>
                <hr>
                <div class="table-responsive"><table class="table table-sm align-middle mb-0">
                    <thead><tr><th>Reference</th><th>Status</th><th>Net</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach (($payouts ?? []) as $p): ?><tr><td><?= e((string)$p['payout_reference']) ?></td><td><?= e((string)$p['status']) ?></td><td>ZMW <?= number_format((float)$p['net_amount'], 2) ?></td><td class="text-end"><a class="btn btn-sm btn-outline-primary" href="<?= e(base_url('superadmin/invoice/affiliatePayoutView/' . (string)$p['id'])) ?>">Open</a></td></tr><?php endforeach; if (empty($payouts)): ?><tr><td colspan="4" class="text-center text-muted py-3">No payout batches yet.</td></tr><?php endif; ?>
                    </tbody>
                </table></div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h6 class="fw-bold mb-3">Generate Affiliate Agreement</h6>
                <form method="post" action="<?= e(base_url('superadmin/invoice/affiliateAgreementCreate/' . (string)$affiliate['id'])) ?>">
                    <input type="hidden" name="_csrf" value="<?= e((string)$csrf) ?>">
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label">Title</label><input name="title" class="form-control" value="Corevia Affiliate Agreement"></div>
                        <div class="col-md-3"><label class="form-label">Effective</label><input type="date" name="effective_date" class="form-control" value="<?= e(date('Y-m-d')) ?>"></div>
                        <div class="col-md-3"><label class="form-label">Expiry</label><input type="date" name="expiry_date" class="form-control"></div>
                        <div class="col-md-4"><label class="form-label">Renewal Reminder</label><input type="date" name="renewal_reminder_at" class="form-control"></div>
                        <div class="col-md-8"><label class="form-label">Special Terms</label><input name="terms_html" class="form-control" placeholder="Leave blank to generate standard terms"></div>
                    </div>
                    <button class="btn btn-sm btn-outline-primary mt-3">Generate Agreement</button>
                </form>
                <hr>
                <div class="table-responsive"><table class="table table-sm align-middle mb-0">
                    <thead><tr><th>Agreement</th><th>Status</th><th>Expiry</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach (($agreements ?? []) as $agreement): ?><tr><td><?= e((string)$agreement['agreement_number']) ?><div class="text-muted small"><?= e((string)$agreement['title']) ?></div></td><td><?= e((string)$agreement['status']) ?></td><td><?= e((string)($agreement['expiry_date'] ?? '-')) ?></td><td><form method="post" action="<?= e(base_url('superadmin/invoice/affiliateAgreementStatus/' . (string)$agreement['id'])) ?>" class="d-flex gap-1 mb-1"><input type="hidden" name="_csrf" value="<?= e((string)$csrf) ?>"><select name="status" class="form-select form-select-sm"><?php foreach (['Draft','Sent','Signed','Expired','Terminated'] as $status): ?><option <?= (string)$agreement['status'] === $status ? 'selected' : '' ?>><?= e($status) ?></option><?php endforeach; ?></select><button class="btn btn-sm btn-outline-secondary">Save</button></form><form method="post" enctype="multipart/form-data" action="<?= e(base_url('superadmin/invoice/affiliateAgreementUpload/' . (string)$agreement['id'])) ?>" class="d-flex gap-1"><input type="hidden" name="_csrf" value="<?= e((string)$csrf) ?>"><input type="file" name="signed_document" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png" required><button class="btn btn-sm btn-outline-success">Upload Signed</button></form></td></tr><?php endforeach; if (empty($agreements)): ?><tr><td colspan="4" class="text-center text-muted py-3">No agreements generated yet.</td></tr><?php endif; ?>
                    </tbody>
                </table></div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mt-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="fw-bold mb-0">Affiliate Statement</h6>
            <div class="d-flex gap-2">
                <a href="<?= e(base_url('superadmin/invoice/affiliateStatement/' . (string)$affiliate['id'] . '?export=csv')) ?>" class="btn btn-sm btn-outline-primary">CSV</a>
                <a href="<?= e(base_url('superadmin/invoice/affiliateStatement/' . (string)$affiliate['id'] . '?export=xls')) ?>" class="btn btn-sm btn-outline-success">Excel</a>
                <a href="<?= e(base_url('superadmin/invoice/affiliateStatement/' . (string)$affiliate['id'])) ?>" target="_blank" class="btn btn-sm btn-outline-danger">PDF/Print</a>
            </div>
        </div>
        <div class="row g-3">
            <?php foreach ([['Earned',$statement['earned'] ?? 0], ['Approved',$statement['approved'] ?? 0], ['Paid',$statement['paid'] ?? 0], ['Reversed',$statement['reversed'] ?? 0], ['Closing',$statement['closing_balance'] ?? 0]] as $stat): ?>
                <div class="col-sm"><div class="p-3 rounded border"><div class="text-muted small"><?= e($stat[0]) ?></div><strong>ZMW <?= number_format((float)$stat[1], 2) ?></strong></div></div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="row g-4 mt-1">
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h6 class="fw-bold mb-3">Upload Compliance Document</h6>
                <form method="post" action="<?= e(base_url('superadmin/invoice/affiliateUploadDocument/' . (string)$affiliate['id'])) ?>" enctype="multipart/form-data">
                    <input type="hidden" name="_csrf" value="<?= e((string)$csrf) ?>">
                    <div class="mb-3">
                        <label class="form-label">Document Type</label>
                        <select name="document_type" class="form-select">
                            <option>NRC</option>
                            <option>TPIN</option>
                            <option>Affiliate Agreement</option>
                            <option>Bank Proof</option>
                            <option>Other</option>
                        </select>
                    </div>
                    <div class="mb-3"><label class="form-label">File</label><input type="file" name="document_file" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required></div>
                    <div class="mb-3"><label class="form-label">Notes</label><textarea name="notes" class="form-control" rows="2"></textarea></div>
                    <button class="btn btn-sm text-white" style="background:#7c3aed"><i class="bi bi-upload me-1"></i>Upload Document</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h6 class="fw-bold mb-3">Affiliate Documents</h6>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead><tr><th>Type</th><th>File</th><th>Uploaded</th><th class="text-end">Action</th></tr></thead>
                        <tbody>
                        <?php foreach (($documents ?? []) as $doc): ?>
                        <tr>
                            <td><?= e((string)$doc['document_type']) ?></td>
                            <td><strong><?= e((string)$doc['file_name']) ?></strong><div class="text-muted small"><?= e((string)($doc['notes'] ?? '')) ?></div></td>
                            <td><?= e((string)$doc['created_at']) ?></td>
                            <td class="text-end">
                                <a href="<?= e(base_url('superadmin/invoice/affiliateDownloadDocument/' . (string)$doc['id'])) ?>" class="btn btn-sm btn-outline-primary">Download</a>
                                <form method="post" action="<?= e(base_url('superadmin/invoice/affiliateDeleteDocument/' . (string)$doc['id'])) ?>" class="d-inline" onsubmit="return confirm('Delete this affiliate document?')">
                                    <input type="hidden" name="_csrf" value="<?= e((string)$csrf) ?>">
                                    <button class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; if (empty($documents)): ?><tr><td colspan="4" class="text-center text-muted py-4">No affiliate documents uploaded yet.</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mt-4">
    <div class="card-body">
        <h6 class="fw-bold mb-3">Commission Ledger</h6>
        <div class="table-responsive"><table class="table table-sm align-middle mb-0">
            <thead><tr><th>Date</th><th>Company</th><th>Invoice</th><th>Payment</th><th>Rate</th><th>Commission</th><th>Status</th><th>Controls</th></tr></thead>
            <tbody>
            <?php foreach (($dashboard['commissions'] ?? []) as $c): ?>
                <tr><td><?= e((string)$c['earned_at']) ?></td><td><?= e((string)$c['company_name']) ?></td><td><?= e((string)$c['invoice_number']) ?></td><td>ZMW <?= number_format((float)$c['payment_amount'], 2) ?></td><td><?= e((string)$c['commission_rate']) ?>%</td><td>ZMW <?= number_format((float)$c['commission_amount'], 2) ?></td><td><?= e((string)$c['status']) ?></td><td><form method="post" action="<?= e(base_url('superadmin/invoice/affiliateCommissionReverse/' . (string)$c['id'])) ?>" class="d-inline"><input type="hidden" name="_csrf" value="<?= e((string)$csrf) ?>"><input type="hidden" name="reason" value="Manual reversal"><button class="btn btn-sm btn-outline-danger">Reverse</button></form></td></tr>
            <?php endforeach; if (empty($dashboard['commissions'])): ?><tr><td colspan="8" class="text-center text-muted py-4">No commission has been earned yet.</td></tr><?php endif; ?>
            </tbody>
        </table></div>
    </div>
</div>
