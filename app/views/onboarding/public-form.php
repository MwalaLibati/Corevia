<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Employee Onboarding</title>
    <link rel="stylesheet" href="<?= e(base_url('assets/vendor/bootstrap/css/bootstrap.min.css')) ?>">
    <link rel="stylesheet" href="<?= e(base_url('assets/css/main.css')) ?>">
</head>
<body class="bg-light">
<main class="container py-5" style="max-width:980px">
    <div class="mb-4">
        <h1 class="h3 mb-1"><?= e((string)$request['company_name']) ?> Employee Onboarding</h1>
        <p class="text-muted mb-0">Complete your details for HR review. Your profile is created only after HR approves the submission.</p>
    </div>

    <?php if (!empty($flashError)): ?><div class="alert alert-danger"><?= e((string)$flashError) ?></div><?php endif; ?>

    <form method="post" action="<?= e(base_url('onboarding/submit/' . (string)$request['token'])) ?>" enctype="multipart/form-data" class="card border-0 shadow-sm">
        <div class="card-body row g-3">
            <input type="hidden" name="_csrf" value="<?= e((string)$csrf) ?>">

            <div class="col-12"><h5>Personal Details</h5></div>
            <div class="col-md-6">
                <label class="form-label">Full Name *</label>
                <input type="text" name="full_name" class="form-control" value="<?= e((string)($old['full_name'] ?? $request['invited_full_name'] ?? '')) ?>" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Email *</label>
                <input type="email" name="email" class="form-control" value="<?= e((string)($old['email'] ?? $request['invited_email'] ?? '')) ?>" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Phone *</label>
                <input type="text" name="phone" class="form-control" value="<?= e((string)($old['phone'] ?? $request['invited_phone'] ?? '')) ?>" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">NRC Number *</label>
                <input type="text" name="nrc_number" class="form-control" value="<?= e((string)($old['nrc_number'] ?? '')) ?>" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Date of Birth *</label>
                <input type="date" name="date_of_birth" class="form-control" value="<?= e((string)($old['date_of_birth'] ?? '')) ?>" required>
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
                <textarea name="address" class="form-control" rows="2"><?= e((string)($old['address'] ?? '')) ?></textarea>
            </div>

            <div class="col-12 pt-2"><h5>Statutory & Bank Details</h5></div>
            <div class="col-md-4">
                <label class="form-label">NAPSA / Social Security Number</label>
                <input type="text" name="napsa_number" class="form-control" value="<?= e((string)($old['napsa_number'] ?? '')) ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">TPIN</label>
                <input type="text" name="tpin" class="form-control" value="<?= e((string)($old['tpin'] ?? '')) ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">NHIMA Number</label>
                <input type="text" name="nhima_number" class="form-control" value="<?= e((string)($old['nhima_number'] ?? '')) ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Bank Name</label>
                <input type="text" name="bank_name" class="form-control" value="<?= e((string)($old['bank_name'] ?? '')) ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Bank Account Number</label>
                <input type="text" name="bank_account_number" class="form-control" value="<?= e((string)($old['bank_account_number'] ?? '')) ?>">
            </div>

            <div class="col-12 pt-2"><h5>Next of Kin</h5></div>
            <div class="col-md-4">
                <label class="form-label">Name</label>
                <input type="text" name="next_of_kin_name" class="form-control" value="<?= e((string)($old['next_of_kin_name'] ?? '')) ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Phone</label>
                <input type="text" name="next_of_kin_phone" class="form-control" value="<?= e((string)($old['next_of_kin_phone'] ?? '')) ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Relationship</label>
                <input type="text" name="next_of_kin_relationship" class="form-control" value="<?= e((string)($old['next_of_kin_relationship'] ?? '')) ?>">
            </div>

            <div class="col-12 pt-2"><h5>Documents</h5></div>
            <div class="col-12">
                <label class="form-label">Upload NRC, certificates, bank confirmation, or other documents</label>
                <input type="file" name="documents[]" class="form-control" multiple accept=".pdf,.jpg,.jpeg,.png">
                <div class="form-text">PDF, JPG, or PNG only. Maximum 5MB per file.</div>
            </div>
            <div class="col-12">
                <label class="form-label">Additional Notes</label>
                <textarea name="notes" class="form-control" rows="3"><?= e((string)($old['notes'] ?? '')) ?></textarea>
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-primary">Submit Details</button>
            </div>
        </div>
    </form>
</main>
</body>
</html>
