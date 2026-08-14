<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="mb-0">Create Affiliate</h4>
        <p class="text-muted mb-0 small">Add a referral partner for Corevia.</p>
    </div>
    <a href="<?= e(base_url('superadmin/invoice/affiliates')) ?>" class="btn btn-sm btn-outline-secondary">Back</a>
</div>

<?php if (!empty($flashErr)): ?><div class="alert alert-danger"><?= e((string)$flashErr) ?></div><?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="post" action="<?= e(base_url('superadmin/invoice/affiliateStore')) ?>">
            <input type="hidden" name="_csrf" value="<?= e((string)$csrf) ?>">
            <h6 class="fw-bold mb-3">Affiliate Identity</h6>
            <div class="row g-3">
                <div class="col-md-4"><label class="form-label">Affiliate Type</label><select name="affiliate_type" class="form-select"><option>Individual</option><option>Company</option><option>Consultant</option><option>Reseller</option><option>Agency</option></select></div>
                <div class="col-md-6"><label class="form-label">Full Name</label><input name="full_name" class="form-control" required></div>
                <div class="col-md-2"><label class="form-label">KYC Status</label><select name="kyc_status" class="form-select"><option>Draft</option><option>Pending Review</option><option>Approved</option><option>Rejected</option></select></div>
                <div class="col-md-6"><label class="form-label">Trading Name</label><input name="trading_name" class="form-control"></div>
                <div class="col-md-6"><label class="form-label">Email</label><input type="email" name="email" class="form-control" required></div>
                <div class="col-md-6"><label class="form-label">Alternate Email</label><input type="email" name="alternate_email" class="form-control"></div>
                <div class="col-md-3"><label class="form-label">Phone</label><input name="phone" class="form-control"></div>
                <div class="col-md-3"><label class="form-label">Alternate Phone</label><input name="alternate_phone" class="form-control"></div>
                <div class="col-md-3"><label class="form-label">NRC Number</label><input name="nrc_number" class="form-control"></div>
                <div class="col-md-3"><label class="form-label">TPIN</label><input name="tpin" class="form-control"></div>
                <div class="col-md-4"><label class="form-label">Date of Birth / Incorporation</label><input type="date" name="date_of_birth" class="form-control"></div>
                <div class="col-md-4">
                    <label class="form-label">Province</label>
                    <select name="province" id="affiliateProvince" class="form-select">
                        <option value="">Select province</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">City</label>
                    <select name="city" id="affiliateCity" class="form-select" disabled>
                        <option value="">Select province first</option>
                    </select>
                </div>
                <div class="col-12"><label class="form-label">Address</label><textarea name="address" class="form-control" rows="2"></textarea></div>
            </div>

            <hr class="my-4">
            <h6 class="fw-bold mb-3">Commission & Payout Details</h6>
            <div class="row g-3">
                <div class="col-md-3"><label class="form-label">Commission %</label><input type="number" step="0.01" min="0" max="100" name="commission_rate" class="form-control" value="5.00"></div>
                <div class="col-md-3"><label class="form-label">Commission Basis</label><select name="commission_basis" class="form-select"><option>Paid Amount</option><option>Invoice Amount</option><option>Net Amount</option></select></div>
                <div class="col-md-3"><label class="form-label">Duration</label><select name="commission_duration" class="form-select"><option>First Year</option><option>Lifetime</option><option>Fixed Months</option></select></div>
                <div class="col-md-3"><label class="form-label">Fixed Months</label><input type="number" min="0" name="commission_months" class="form-control"></div>
                <div class="col-md-3"><label class="form-label">One-off Bonus</label><input type="number" step="0.01" min="0" name="one_off_bonus" class="form-control" value="0.00"></div>
                <div class="col-md-3"><label class="form-label">Payout Tax %</label><input type="number" step="0.01" min="0" max="100" name="payout_tax_rate" class="form-control" value="0.00"></div>
                <div class="col-md-3"><label class="form-label">Payout Method</label><input name="payout_method" class="form-control" placeholder="Bank transfer, mobile money"></div>
                <div class="col-md-3"><label class="form-label">Bank Name</label><input name="bank_name" class="form-control"></div>
                <div class="col-md-3"><label class="form-label">Mobile Money Number</label><input name="mobile_money_number" class="form-control"></div>
                <div class="col-md-4"><label class="form-label">Account Name</label><input name="bank_account_name" class="form-control"></div>
                <div class="col-md-4"><label class="form-label">Account Number</label><input name="bank_account_number" class="form-control"></div>
                <div class="col-md-4"><label class="form-label">Additional Payout Details</label><textarea name="payout_details" class="form-control" rows="1"></textarea></div>
            </div>

            <hr class="my-4">
            <div class="p-3 rounded border" style="background:#f8fafc">
                <h6 class="fw-bold mb-2"><i class="bi bi-key me-1"></i>Temporary Login Password</h6>
                <p class="text-muted small mb-3">Enter a one-time password or leave it blank and Corevia will generate one automatically. The affiliate will be forced to change it on first login.</p>
                <div class="row g-3">
                    <div class="col-md-7">
                        <label class="form-label">Temporary Password</label>
                        <div class="input-group">
                            <input type="password" name="password" id="affiliatePassword" class="form-control" placeholder="Leave blank to auto-generate">
                            <button type="button" class="btn btn-outline-secondary" id="toggleAffiliatePassword"><i class="bi bi-eye"></i></button>
                            <button type="button" class="btn btn-outline-primary" id="generateAffiliatePassword"><i class="bi bi-magic me-1"></i>Generate</button>
                        </div>
                        <div class="form-text">Use at least 10 characters with uppercase, lowercase, number, and special character.</div>
                    </div>
                </div>
            </div>
            <div class="mt-4"><button class="btn text-white" style="background:#7c3aed">Create Affiliate</button></div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var provinceSelect = document.getElementById('affiliateProvince');
    var citySelect = document.getElementById('affiliateCity');
    var passwordInput = document.getElementById('affiliatePassword');
    var toggleButton = document.getElementById('toggleAffiliatePassword');
    var generateButton = document.getElementById('generateAffiliatePassword');
    var citiesByProvince = {
        'Central': ['Kabwe', 'Kapiri Mposhi', 'Mkushi', 'Serenje', 'Chibombo', 'Mumbwa'],
        'Copperbelt': ['Kitwe', 'Ndola', 'Chingola', 'Mufulira', 'Luanshya', 'Kalulushi', 'Chililabombwe', 'Lufwanyama', 'Mpongwe', 'Masaiti'],
        'Eastern': ['Chipata', 'Petauke', 'Katete', 'Lundazi', 'Nyimba', 'Sinda'],
        'Luapula': ['Mansa', 'Samfya', 'Nchelenge', 'Kawambwa', 'Mwense'],
        'Lusaka': ['Lusaka', 'Kafue', 'Chongwe', 'Chilanga', 'Rufunsa'],
        'Muchinga': ['Chinsali', 'Mpika', 'Nakonde', 'Isoka', 'Mafinga'],
        'Northern': ['Kasama', 'Mbala', 'Mpulungu', 'Mporokoso', 'Luwingu'],
        'North-Western': ['Solwezi', 'Mwinilunga', 'Kasempa', 'Zambezi', 'Kabompo'],
        'Southern': ['Livingstone', 'Choma', 'Mazabuka', 'Monze', 'Kalomo', 'Siavonga'],
        'Western': ['Mongu', 'Sesheke', 'Senanga', 'Kalabo', 'Kaoma']
    };

    Object.keys(citiesByProvince).forEach(function (province) {
        provinceSelect.add(new Option(province, province));
    });

    function populateCities() {
        var cities = citiesByProvince[provinceSelect.value] || [];
        citySelect.innerHTML = '';
        if (cities.length === 0) {
            citySelect.add(new Option('Select province first', ''));
            citySelect.disabled = true;
            return;
        }
        citySelect.add(new Option('Select city', ''));
        cities.forEach(function (city) {
            citySelect.add(new Option(city, city));
        });
        citySelect.disabled = false;
    }

    function strongPassword() {
        var chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789';
        var specials = '!@#$%';
        var password = 'Corevia@';
        for (var i = 0; i < 6; i++) {
            password += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        password += specials.charAt(Math.floor(Math.random() * specials.length));
        return password;
    }

    provinceSelect.addEventListener('change', populateCities);
    generateButton.addEventListener('click', function () {
        passwordInput.value = strongPassword();
        passwordInput.type = 'text';
    });
    toggleButton.addEventListener('click', function () {
        passwordInput.type = passwordInput.type === 'password' ? 'text' : 'password';
    });
});
</script>
