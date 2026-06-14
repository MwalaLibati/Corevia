<div class="d-flex align-items-center justify-content-between mb-4">
    <h2 class="text-dark mb-0">Create Company</h2>
    <a href="<?= e(base_url('superadmin/company/index')) ?>" class="btn btn-sm btn-outline-secondary">Back</a>
</div>

<?php
$old = is_array($old ?? null) ? $old : [];
$plans = is_array($plans ?? null) ? $plans : [];
$selectedPlan = (string)($old['subscription_plan'] ?? ($plans[0]['name'] ?? 'Trial'));
$selectedPlanRow = null;
foreach ($plans as $planRow) {
    if ((string) $planRow['name'] === $selectedPlan) {
        $selectedPlanRow = $planRow;
        break;
    }
}
$defaultRate = (float) ($selectedPlanRow['default_monthly_rate'] ?? 0);
$selectedCycle = (string)($old['billing_cycle'] ?? ($selectedPlanRow['default_billing_cycle'] ?? 'Annual'));
$selectedEntityId = (int)($old['client_entity_id'] ?? ($selectedClientEntityId ?? 0));
?>

<div class="card border-0 shadow-sm" style="max-width:820px">
    <div class="card-body p-4">
        <?php if (!empty($flash)): ?>
        <div class="alert alert-danger py-2 mb-3" style="font-size:.83rem"><?= e($flash) ?></div>
        <?php endif; ?>

        <form method="post" action="<?= e(base_url('superadmin/company/store')) ?>">
            <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label fw-semibold">Company Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" required placeholder="e.g. Stonesoft Demo Company"
                           value="<?= e((string)($old['name'] ?? '')) ?>"
                           oninput="document.getElementById('slug').value=this.value.toLowerCase().replace(/[^a-z0-9]+/g,'-').replace(/^-|-$/g,'')">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Client Entity / Group</label>
                    <select name="client_entity_id" class="form-select">
                        <option value="">Create as single-company entity</option>
                        <?php foreach (($clientEntities ?? []) as $entity): ?>
                            <option value="<?= e((string) $entity['id']) ?>" <?= $selectedEntityId === (int) $entity['id'] ? 'selected' : '' ?>>
                                <?= e((string) $entity['name']) ?><?= !empty($entity['code']) ? ' (' . e((string) $entity['code']) . ')' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">Use when one client owns multiple companies.</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">New Entity Name</label>
                    <input type="text" name="new_client_entity_name" class="form-control" value="<?= e((string)($old['new_client_entity_name'] ?? '')) ?>" placeholder="Optional, e.g. Libati Group Limited">
                    <div class="form-text">If entered, this creates a new group and attaches the company.</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Slug <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="text" name="slug" id="slug" class="form-control" required placeholder="stonesoft-demo"
                               value="<?= e((string)($old['slug'] ?? '')) ?>"
                               pattern="[a-z0-9\-]+" title="Lowercase letters, numbers, hyphens only">
                        <span class="input-group-text text-muted" style="font-size:.78rem">.<?= e(app_platform_domain()) ?></span>
                    </div>
                    <div class="form-text">Used as subdomain. Lowercase, no spaces.</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Subscription Plan</label>
                    <select name="subscription_plan" id="planSelect" class="form-select">
                        <?php foreach ($plans as $p): ?>
                        <option value="<?= e((string) $p['name']) ?>"
                                data-rate="<?= e((string) $p['default_monthly_rate']) ?>"
                                data-cycle="<?= e((string) $p['default_billing_cycle']) ?>"
                                data-currency="<?= e((string) $p['currency']) ?>"
                                <?= $selectedPlan === (string) $p['name'] ? 'selected' : '' ?>>
                            <?= e((string) $p['name']) ?> - <?= e((string) $p['currency']) ?> <?= number_format((float) $p['default_monthly_rate'], 2) ?>/user/mo
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Email</label>
                    <input type="email" name="email" class="form-control" placeholder="company@example.com" value="<?= e((string)($old['email'] ?? '')) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Phone</label>
                    <input type="text" name="phone" class="form-control" placeholder="+260 XXX XXX XXX" value="<?= e((string)($old['phone'] ?? '')) ?>">
                </div>
                <div class="col-12">
                    <hr class="my-2">
                    <h5 class="mb-1">Billing Terms</h5>
                    <p class="text-muted mb-2" style="font-size:.86rem">Use the plan default, or enter a negotiated monthly rate for this company.</p>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Billing Model</label>
                    <?php $selectedBillingModel = (string)($old['billing_model'] ?? 'per_user'); ?>
                    <select name="billing_model" class="form-select">
                        <option value="per_user" <?= $selectedBillingModel === 'per_user' ? 'selected' : '' ?>>Per user / employee</option>
                        <option value="flat" <?= $selectedBillingModel === 'flat' ? 'selected' : '' ?>>Flat monthly fee</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Billing Cycle</label>
                    <select name="billing_cycle" id="cycleSelect" class="form-select">
                        <?php foreach (['Monthly','Annual'] as $cycle): ?>
                        <option value="<?= $cycle ?>" <?= $selectedCycle === $cycle ? 'selected' : '' ?>><?= $cycle ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Negotiated Monthly Rate</label>
                    <div class="input-group">
                        <span class="input-group-text" id="currencyLabel"><?= e((string)($selectedPlanRow['currency'] ?? 'ZMW')) ?></span>
                        <input type="number" step="0.01" min="0" name="monthly_rate" id="monthlyRate" class="form-control" placeholder="<?= e(number_format($defaultRate, 2, '.', '')) ?>" value="<?= e((string)($old['monthly_rate'] ?? '')) ?>">
                    </div>
                    <div class="form-text">Leave blank to use the plan default.</div>
                </div>

                <div class="col-12">
                    <hr class="my-2">
                    <h5 class="mb-1">First Company Admin</h5>
                    <p class="text-muted mb-2" style="font-size:.86rem">Create the first login for the company admin. They will be emailed login instructions and must change the one-time password on first login.</p>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Admin Name <span class="text-danger">*</span></label>
                    <input type="text" name="admin_full_name" class="form-control" required placeholder="e.g. Emmanuel Libati" value="<?= e((string)($old['admin_full_name'] ?? '')) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Admin Email <span class="text-danger">*</span></label>
                    <input type="email" name="admin_email" class="form-control" required placeholder="admin@company.com" value="<?= e((string)($old['admin_email'] ?? '')) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">One-Time Password</label>
                    <div class="input-group">
                        <input type="password" name="one_time_password" id="oneTimePassword" class="form-control" autocomplete="new-password" placeholder="Leave blank to auto-generate">
                        <button class="btn btn-outline-secondary" type="button" id="toggleOneTimePassword">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                    <div class="form-text">Optional. Minimum 10 characters with uppercase, lowercase, number, and special character.</div>
                </div>
                <div class="col-md-6 d-flex align-items-end">
                    <button type="button" class="btn btn-outline-primary w-100" id="generateOneTimePassword">
                        <i class="bi bi-shuffle me-1"></i>Generate Password
                    </button>
                </div>

                <div class="col-12 pt-2">
                    <button type="submit" class="btn text-white" style="background:#7c3aed">
                        <i class="bi bi-building-add me-1"></i>Create Company
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
(function(){
    var planSelect = document.getElementById('planSelect');
    var monthlyRate = document.getElementById('monthlyRate');
    var currencyLabel = document.getElementById('currencyLabel');
    var cycleSelect = document.getElementById('cycleSelect');

    function syncPlanDefaults() {
        var opt = planSelect.options[planSelect.selectedIndex];
        if (!opt) return;
        monthlyRate.placeholder = Number(opt.getAttribute('data-rate') || 0).toFixed(2);
        currencyLabel.textContent = opt.getAttribute('data-currency') || 'ZMW';
        if (!cycleSelect.dataset.touched) {
            cycleSelect.value = opt.getAttribute('data-cycle') || 'Annual';
        }
    }

    planSelect.addEventListener('change', syncPlanDefaults);
    cycleSelect.addEventListener('change', function(){ cycleSelect.dataset.touched = '1'; });
    syncPlanDefaults();

    var passwordInput = document.getElementById('oneTimePassword');
    var generateBtn = document.getElementById('generateOneTimePassword');
    var toggleBtn = document.getElementById('toggleOneTimePassword');
    var chars = {
        upper: 'ABCDEFGHJKLMNPQRSTUVWXYZ',
        lower: 'abcdefghijkmnopqrstuvwxyz',
        number: '23456789',
        special: '@#$%!'
    };

    function randomFrom(set) {
        return set[Math.floor(Math.random() * set.length)];
    }

    function shuffle(value) {
        return value.split('').sort(function(){ return Math.random() - 0.5; }).join('');
    }

    generateBtn.addEventListener('click', function(){
        var pool = chars.upper + chars.lower + chars.number + chars.special;
        var value = randomFrom(chars.upper) + randomFrom(chars.lower) + randomFrom(chars.number) + randomFrom(chars.special);
        for (var i = value.length; i < 14; i++) {
            value += randomFrom(pool);
        }
        passwordInput.type = 'text';
        passwordInput.value = shuffle(value);
        passwordInput.focus();
    });

    toggleBtn.addEventListener('click', function(){
        passwordInput.type = passwordInput.type === 'password' ? 'text' : 'password';
        toggleBtn.innerHTML = passwordInput.type === 'password' ? '<i class="bi bi-eye"></i>' : '<i class="bi bi-eye-slash"></i>';
    });
})();
</script>
