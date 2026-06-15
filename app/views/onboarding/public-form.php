<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Employee Onboarding</title>
    <link rel="stylesheet" href="<?= e(base_url('assets/vendor/bootstrap/css/bootstrap.min.css')) ?>">
    <link rel="stylesheet" href="<?= e(base_url('assets/css/main.css')) ?>">
    <style>
        .onboarding-stepper{display:grid;grid-template-columns:repeat(5,1fr);gap:.5rem;margin-bottom:1rem}
        .onboarding-step{border:1px solid #d9e2ef;border-radius:8px;padding:.75rem;background:#fff;color:#5f6f89;font-weight:600;font-size:.875rem}
        .onboarding-step.active{border-color:#2563eb;background:#eff6ff;color:#0f172a}
        .onboarding-section{display:none}
        .onboarding-section.active{display:block}
        .required-chip{display:inline-flex;align-items:center;border-radius:999px;padding:.25rem .6rem;background:#eff6ff;color:#1d4ed8;font-size:.8rem;margin:.15rem}
        @media (max-width: 768px){.onboarding-stepper{grid-template-columns:1fr 1fr}.onboarding-step{font-size:.8rem}}
    </style>
</head>
<body class="bg-light">
<main class="container py-5" style="max-width:980px">
    <?php
    $requiredFields = is_array($requiredFields ?? null) ? $requiredFields : [];
    $isRequired = static fn(string $field): bool => array_key_exists($field, $requiredFields);
    $requiredMark = static fn(string $field): string => array_key_exists($field, $requiredFields) ? ' *' : '';
    $fieldDefault = static function (string $field, array $old, array $request): string {
        $selectedKey = 'selected_employee_' . $field;
        $inviteKey = 'invited_' . $field;
        return (string)($old[$field] ?? $request[$field] ?? $request[$selectedKey] ?? $request[$inviteKey] ?? '');
    };
    ?>
    <div class="mb-4">
        <h1 class="h3 mb-1"><?= e((string)$request['company_name']) ?> Employee Onboarding</h1>
        <p class="text-muted mb-2">Complete your details for HR review. Your profile is created or updated only after HR approves the submission.</p>
        <?php if (!empty($request['selected_employee_number'])): ?>
            <div class="alert alert-info py-2 mb-0">This link is connected to employee profile <?= e((string)$request['selected_employee_number']) ?>.</div>
        <?php endif; ?>
    </div>

    <?php if (!empty($flashError)): ?><div class="alert alert-danger"><?= e((string)$flashError) ?></div><?php endif; ?>

    <form method="post" action="<?= e(base_url('onboarding/submit/' . (string)$request['token'])) ?>" enctype="multipart/form-data" class="card border-0 shadow-sm">
        <div class="card-body">
            <input type="hidden" name="_csrf" value="<?= e((string)$csrf) ?>">

            <div class="onboarding-stepper" aria-label="Onboarding progress">
                <button type="button" class="onboarding-step active" data-step-target="0">1. Personal</button>
                <button type="button" class="onboarding-step" data-step-target="1">2. Statutory</button>
                <button type="button" class="onboarding-step" data-step-target="2">3. Bank</button>
                <button type="button" class="onboarding-step" data-step-target="3">4. Emergency</button>
                <button type="button" class="onboarding-step" data-step-target="4">5. Documents</button>
            </div>

            <?php if (!empty($requiredFields)): ?>
                <div class="mb-3">
                    <div class="small text-muted mb-1">Required by HR</div>
                    <?php foreach ($requiredFields as $label): ?>
                        <span class="required-chip"><?= e((string)$label) ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <section class="onboarding-section active" data-step="0">
                <div class="row g-3">
                    <div class="col-12"><h5>Personal Details</h5></div>
            <div class="col-md-6">
                <label class="form-label">Full Name<?= e($requiredMark('full_name')) ?></label>
                <input type="text" name="full_name" class="form-control" value="<?= e($fieldDefault('full_name', $old, $request)) ?>" <?= $isRequired('full_name') ? 'required' : '' ?>>
            </div>
            <div class="col-md-3">
                <label class="form-label">Email<?= e($requiredMark('email')) ?></label>
                <input type="email" name="email" class="form-control" value="<?= e($fieldDefault('email', $old, $request)) ?>" <?= $isRequired('email') ? 'required' : '' ?>>
            </div>
            <div class="col-md-3">
                <label class="form-label">Phone<?= e($requiredMark('phone')) ?></label>
                <input type="text" name="phone" class="form-control" value="<?= e($fieldDefault('phone', $old, $request)) ?>" <?= $isRequired('phone') ? 'required' : '' ?>>
            </div>
            <div class="col-md-4">
                <label class="form-label">NRC Number<?= e($requiredMark('nrc_number')) ?></label>
                <input type="text" name="nrc_number" class="form-control" value="<?= e($fieldDefault('nrc_number', $old, $request)) ?>" <?= $isRequired('nrc_number') ? 'required' : '' ?>>
            </div>
            <div class="col-md-4">
                <label class="form-label">Date of Birth<?= e($requiredMark('date_of_birth')) ?></label>
                <input type="date" name="date_of_birth" class="form-control" value="<?= e($fieldDefault('date_of_birth', $old, $request)) ?>" <?= $isRequired('date_of_birth') ? 'required' : '' ?>>
            </div>
            <div class="col-md-4">
                <label class="form-label">Gender</label>
                <select name="gender" class="form-select">
                    <?php foreach (['' => 'Prefer not to say', 'Female' => 'Female', 'Male' => 'Male', 'Other' => 'Other'] as $value => $label): ?>
                        <option value="<?= e($value) ?>" <?= (string)($old['gender'] ?? '') === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12">
                <label class="form-label">Residential Address</label>
                <textarea name="address" class="form-control" rows="2"><?= e($fieldDefault('address', $old, $request)) ?></textarea>
            </div>
                </div>
            </section>

            <section class="onboarding-section" data-step="1">
                <div class="row g-3">
            <div class="col-12"><h5>Statutory Details</h5></div>
            <div class="col-md-4">
                <label class="form-label">NAPSA / Social Security Number<?= e($requiredMark('napsa_number')) ?></label>
                <input type="text" name="napsa_number" class="form-control" value="<?= e($fieldDefault('napsa_number', $old, $request)) ?>" <?= $isRequired('napsa_number') ? 'required' : '' ?>>
            </div>
            <div class="col-md-4">
                <label class="form-label">TPIN<?= e($requiredMark('tpin')) ?></label>
                <input type="text" name="tpin" class="form-control" value="<?= e($fieldDefault('tpin', $old, $request)) ?>" <?= $isRequired('tpin') ? 'required' : '' ?>>
            </div>
            <div class="col-md-4">
                <label class="form-label">NHIMA Number<?= e($requiredMark('nhima_number')) ?></label>
                <input type="text" name="nhima_number" class="form-control" value="<?= e($fieldDefault('nhima_number', $old, $request)) ?>" <?= $isRequired('nhima_number') ? 'required' : '' ?>>
            </div>
                </div>
            </section>

            <section class="onboarding-section" data-step="2">
                <div class="row g-3">
            <div class="col-12"><h5>Bank Details</h5></div>
            <div class="col-md-6">
                <label class="form-label">Bank Name<?= e($requiredMark('bank_name')) ?></label>
                <input type="text" name="bank_name" class="form-control" value="<?= e($fieldDefault('bank_name', $old, $request)) ?>" <?= $isRequired('bank_name') ? 'required' : '' ?>>
            </div>
            <div class="col-md-6">
                <label class="form-label">Bank Account Number<?= e($requiredMark('bank_account_number')) ?></label>
                <input type="text" name="bank_account_number" class="form-control" value="<?= e($fieldDefault('bank_account_number', $old, $request)) ?>" <?= $isRequired('bank_account_number') ? 'required' : '' ?>>
            </div>
                </div>
            </section>

            <section class="onboarding-section" data-step="3">
                <div class="row g-3">
            <div class="col-12"><h5>Next of Kin</h5></div>
            <div class="col-md-4">
                <label class="form-label">Name<?= e($requiredMark('next_of_kin_name')) ?></label>
                <input type="text" name="next_of_kin_name" class="form-control" value="<?= e((string)($old['next_of_kin_name'] ?? '')) ?>" <?= $isRequired('next_of_kin_name') ? 'required' : '' ?>>
            </div>
            <div class="col-md-4">
                <label class="form-label">Phone<?= e($requiredMark('next_of_kin_phone')) ?></label>
                <input type="text" name="next_of_kin_phone" class="form-control" value="<?= e((string)($old['next_of_kin_phone'] ?? '')) ?>" <?= $isRequired('next_of_kin_phone') ? 'required' : '' ?>>
            </div>
            <div class="col-md-4">
                <label class="form-label">Relationship</label>
                <input type="text" name="next_of_kin_relationship" class="form-control" value="<?= e((string)($old['next_of_kin_relationship'] ?? '')) ?>">
            </div>
                </div>
            </section>

            <section class="onboarding-section" data-step="4">
                <div class="row g-3">
            <div class="col-12"><h5>Documents & Review</h5></div>
            <div class="col-12">
                <label class="form-label">Upload NRC, certificates, bank confirmation, or other documents</label>
                <input type="file" name="documents[]" class="form-control" multiple accept=".pdf,.jpg,.jpeg,.png">
                <div class="form-text">PDF, JPG, or PNG only. Maximum 5MB per file.</div>
            </div>
            <div class="col-12">
                <label class="form-label">Additional Notes</label>
                <textarea name="notes" class="form-control" rows="3"><?= e((string)($old['notes'] ?? '')) ?></textarea>
            </div>
                </div>
            </section>

            <div class="d-flex justify-content-between gap-2 mt-4">
                <button type="button" class="btn btn-outline-secondary" id="prevStep" disabled>Back</button>
                <div>
                    <button type="button" class="btn btn-primary" id="nextStep">Next</button>
                    <button type="submit" class="btn btn-success d-none" id="submitOnboarding">Submit Details</button>
                </div>
            </div>
        </div>
    </form>
</main>
<script>
    (function () {
        const sections = Array.from(document.querySelectorAll('.onboarding-section'));
        const steps = Array.from(document.querySelectorAll('.onboarding-step'));
        const prev = document.getElementById('prevStep');
        const next = document.getElementById('nextStep');
        const submit = document.getElementById('submitOnboarding');
        let current = 0;

        function show(index) {
            current = Math.max(0, Math.min(index, sections.length - 1));
            sections.forEach((section, i) => section.classList.toggle('active', i === current));
            steps.forEach((step, i) => step.classList.toggle('active', i === current));
            prev.disabled = current === 0;
            next.classList.toggle('d-none', current === sections.length - 1);
            submit.classList.toggle('d-none', current !== sections.length - 1);
            window.scrollTo({top: 0, behavior: 'smooth'});
        }

        function currentSectionValid() {
            const controls = Array.from(sections[current].querySelectorAll('input, select, textarea'));
            for (const control of controls) {
                if (!control.checkValidity()) {
                    control.reportValidity();
                    return false;
                }
            }
            return true;
        }

        steps.forEach((step, index) => step.addEventListener('click', () => {
            if (index <= current || currentSectionValid()) {
                show(index);
            }
        }));
        prev.addEventListener('click', () => show(current - 1));
        next.addEventListener('click', () => {
            if (currentSectionValid()) {
                show(current + 1);
            }
        });
    })();
</script>
</body>
</html>
