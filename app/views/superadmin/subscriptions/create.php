<div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between gap-3 mb-4">
    <div>
        <h2 class="text-dark mb-0 fw-bold">New Subscription</h2>
        <p class="text-muted mb-0 mt-1 small">Create a subscription using plan defaults or negotiated billing terms.</p>
    </div>
    <a href="<?= e(base_url('superadmin/subscription/index')) ?>" class="btn btn-sm btn-outline-secondary flex-shrink-0">
        <i class="bi bi-arrow-left me-1"></i>Back
    </a>
</div>

<?php if ($flash ?? null): ?>
<div class="alert alert-danger"><?= e((string)$flash) ?></div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <form method="POST" action="<?= e(base_url('superadmin/subscription/store')) ?>" id="subForm">
                    <input type="hidden" name="_csrf" value="<?= e((string)$csrf) ?>">

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Company <span class="text-danger">*</span></label>
                        <select name="company_id" id="companySelect" class="form-select" required>
                            <option value="">Select company</option>
                            <?php foreach ($companies as $co): ?>
                            <option value="<?= (int)$co['id'] ?>"
                                    data-emp="<?= (int)$co['emp_count'] ?>"
                                    data-name="<?= e((string)$co['name']) ?>"
                                    <?= ($selected && (int)$selected['id'] === (int)$co['id']) ? 'selected' : '' ?>>
                                <?= e((string)$co['name']) ?> (<?= (int)$co['emp_count'] ?> employees)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Plan</label>
                            <select name="plan" id="planSelect" class="form-select">
                                <?php foreach (($plans ?? []) as $idx => $p): ?>
                                <option value="<?= e((string) $p['name']) ?>"
                                        data-rate="<?= e((string) $p['default_monthly_rate']) ?>"
                                        data-cycle="<?= e((string) $p['default_billing_cycle']) ?>"
                                        data-currency="<?= e((string) $p['currency']) ?>"
                                        <?= (string) $p['name'] === 'Standard' || ($idx === 0 && empty($plans[1])) ? 'selected' : '' ?>>
                                    <?= e((string) $p['name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Billing Model</label>
                            <select name="billing_model" id="billingModel" class="form-select">
                                <option value="per_user" selected>Per user / employee</option>
                                <option value="flat">Flat monthly fee</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Billing Cycle</label>
                            <select name="billing_cycle" id="cycleSelect" class="form-select">
                                <option value="Annual" selected>Annual</option>
                                <option value="Monthly">Monthly</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Negotiated Monthly Rate</label>
                            <div class="input-group">
                                <span class="input-group-text" id="currencyLabel">ZMW</span>
                                <input type="number" step="0.01" min="0" name="monthly_rate" id="monthlyRate" class="form-control">
                            </div>
                            <div class="form-text">Leave blank to use the plan default.</div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Start Date</label>
                        <input type="date" name="starts_at" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Notes</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Optional billing notes"></textarea>
                    </div>

                    <button type="submit" class="btn text-white px-4" style="background:#7c3aed">
                        <i class="bi bi-check-circle me-1"></i>Create Subscription
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card border-0 shadow-sm" style="top:1rem;position:sticky">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-4"><i class="bi bi-calculator me-1"></i>Billing Preview</h6>
                <div id="noSelection" class="text-center text-muted py-4">
                    <i class="bi bi-building" style="font-size:2rem;opacity:.3"></i>
                    <div class="mt-2">Select a company to see billing preview</div>
                </div>
                <div id="billingPreview" style="display:none">
                    <div class="mb-3 p-3 rounded" style="background:#f8fafc">
                        <div class="fw-semibold" id="previewCompany"></div>
                        <div class="text-muted" style="font-size:.8rem" id="previewEmpCount"></div>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted" style="font-size:.85rem">Monthly rate</span>
                        <span class="fw-semibold" id="previewRate"></span>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted" style="font-size:.85rem">Billing model</span>
                        <span class="fw-semibold" id="previewModel"></span>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted" style="font-size:.85rem">Billing period</span>
                        <span class="fw-semibold" id="previewPeriod"></span>
                    </div>
                    <div class="d-flex justify-content-between py-3 mt-1 rounded" style="background:#f0fdf4;padding:12px!important">
                        <span class="fw-bold">Total Bill</span>
                        <span class="fw-bold text-success" style="font-size:1.15rem" id="previewTotal"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function(){
    var companySelect = document.getElementById('companySelect');
    var planSelect = document.getElementById('planSelect');
    var billingModel = document.getElementById('billingModel');
    var cycleSelect = document.getElementById('cycleSelect');
    var monthlyRate = document.getElementById('monthlyRate');
    var currencyLabel = document.getElementById('currencyLabel');

    function selectedPlan() {
        return planSelect.options[planSelect.selectedIndex];
    }

    function rate() {
        var override = parseFloat(monthlyRate.value || '');
        if (!isNaN(override)) return override;
        return parseFloat(selectedPlan().getAttribute('data-rate') || '0');
    }

    function currency() {
        return selectedPlan().getAttribute('data-currency') || 'ZMW';
    }

    function updatePreview() {
        var opt = companySelect.options[companySelect.selectedIndex];
        var plan = selectedPlan();
        if (plan) {
            monthlyRate.placeholder = Number(plan.getAttribute('data-rate') || 0).toFixed(2);
            currencyLabel.textContent = currency();
            if (!cycleSelect.dataset.touched) {
                cycleSelect.value = plan.getAttribute('data-cycle') || 'Annual';
            }
        }

        if (!opt || !opt.value) {
            document.getElementById('noSelection').style.display = '';
            document.getElementById('billingPreview').style.display = 'none';
            return;
        }

        var emp = parseInt(opt.getAttribute('data-emp') || '0', 10);
        var months = cycleSelect.value === 'Monthly' ? 1 : 12;
        var currentRate = rate();
        var model = billingModel.value === 'flat' ? 'Flat monthly fee' : 'Per user / employee';
        var monthly = billingModel.value === 'flat' ? currentRate : emp * currentRate;
        var total = monthly * months;

        document.getElementById('noSelection').style.display = 'none';
        document.getElementById('billingPreview').style.display = '';
        document.getElementById('previewCompany').textContent = opt.getAttribute('data-name') || '';
        document.getElementById('previewEmpCount').textContent = emp + ' active employees';
        document.getElementById('previewRate').textContent = currency() + ' ' + currentRate.toLocaleString('en-ZM', {minimumFractionDigits:2, maximumFractionDigits:2});
        document.getElementById('previewModel').textContent = model;
        document.getElementById('previewPeriod').textContent = months + ' month' + (months > 1 ? 's' : '');
        document.getElementById('previewTotal').textContent = currency() + ' ' + total.toLocaleString('en-ZM', {minimumFractionDigits:2, maximumFractionDigits:2});
    }

    companySelect.addEventListener('change', updatePreview);
    planSelect.addEventListener('change', updatePreview);
    billingModel.addEventListener('change', updatePreview);
    monthlyRate.addEventListener('input', updatePreview);
    cycleSelect.addEventListener('change', function(){ cycleSelect.dataset.touched = '1'; updatePreview(); });
    updatePreview();
})();
</script>
