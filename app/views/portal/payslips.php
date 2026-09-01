<div class="portal-page-header">
    <h2><i class="bi bi-receipt me-2"></i>My Payslips</h2>
    <p>All approved payslips for your account.</p>
</div>

<div class="portal-card">
    <?php if (empty($payslips)): ?>
        <div class="text-center py-5 text-muted">
            <i class="bi bi-receipt fs-1 d-block mb-3"></i>
            No approved payslips found.
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped align-middle">
                <thead>
                    <tr>
                        <th>Pay Period</th>
                        <th class="text-end">Gross Pay</th>
                        <th class="text-end">Deductions</th>
                        <th class="text-end">Net Pay</th>
                        <th class="text-center">Payment</th>
                        <th class="text-center">Date</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($payslips as $slip): ?>
                    <tr>
                        <td><strong><?= e((string)($slip['pay_period'] ?? $slip['run_date'])) ?></strong></td>
                        <td class="text-end"><?= e(format_currency((float)($slip['gross_pay'] ?? 0))) ?></td>
                        <td class="text-end text-danger">- <?= e(format_currency((float)($slip['total_deductions'] ?? 0))) ?></td>
                        <td class="text-end fw-bold text-success"><?= e(format_currency((float)($slip['net_pay'] ?? 0))) ?></td>
                        <td class="text-center">
                            <?php
                            $paid = (float) ($slip['paid_amount'] ?? 0);
                            $balance = (float) ($slip['balance_due'] ?? $slip['net_pay'] ?? 0);
                            $status = $balance <= 0.005 ? 'Paid' : ($paid > 0 ? 'Partially Paid' : 'Not Paid');
                            $badge = $status === 'Paid' ? 'success' : ($status === 'Partially Paid' ? 'warning text-dark' : 'secondary');
                            ?>
                            <span class="badge bg-<?= e($badge) ?>"><?= e($status) ?></span>
                            <?php if ($paid > 0 || $balance > 0): ?>
                                <div class="text-muted" style="font-size:.72rem">
                                    Paid <?= e(format_currency($paid)) ?><?php if ($balance > 0): ?> / Bal <?= e(format_currency($balance)) ?><?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td class="text-center text-muted" style="font-size:.8rem"><?= e((string)($slip['run_date'] ?? '')) ?></td>
                        <td class="text-end">
                            <button type="button"
                                    class="btn btn-sm btn-outline-success js-payslip-preview"
                                    data-bs-toggle="modal"
                                    data-bs-target="#payslipPreviewModal"
                                    data-preview-url="<?= e(base_url('portal/payslipPreview/' . (string)$slip['id'])) ?>"
                                    data-pdf-url="<?= e(base_url('portal/payslipPdf/' . (string)$slip['id'])) ?>"
                                    data-title="<?= e((string)($slip['pay_period'] ?? $slip['run_date'] ?? 'Payslip')) ?>">
                                <i class="bi bi-eye me-1"></i> View
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<div class="modal fade" id="payslipPreviewModal" tabindex="-1" aria-labelledby="payslipPreviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title mb-0" id="payslipPreviewModalLabel">Payslip Preview</h5>
                    <div class="text-muted small" id="payslipPreviewSubtitle"></div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <a href="#" class="btn btn-success btn-sm" id="payslipPreviewDownload">
                        <i class="bi bi-file-earmark-pdf me-1"></i> Download PDF
                    </a>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>
            <div class="modal-body p-0" style="min-height:70vh">
                <iframe id="payslipPreviewFrame" title="Payslip preview" src="about:blank" style="width:100%;height:72vh;border:0;background:#fff"></iframe>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('click', function (event) {
        const button = event.target.closest('.js-payslip-preview');
        if (!button) { return; }

        const frame = document.getElementById('payslipPreviewFrame');
        const download = document.getElementById('payslipPreviewDownload');
        const subtitle = document.getElementById('payslipPreviewSubtitle');

        if (frame) { frame.src = button.dataset.previewUrl || 'about:blank'; }
        if (download) { download.href = button.dataset.pdfUrl || '#'; }
        if (subtitle) { subtitle.textContent = button.dataset.title || ''; }
    });

    const payslipModal = document.getElementById('payslipPreviewModal');
    if (payslipModal) {
        payslipModal.addEventListener('hidden.bs.modal', function () {
            const frame = document.getElementById('payslipPreviewFrame');
            if (frame) { frame.src = 'about:blank'; }
        });
    }
</script>
