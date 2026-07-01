<?php

declare(strict_types=1);

/**
 * System settings controller.
 */

class SettingsController extends Controller
{
    public function index(): void
    {
        require_auth();
        require_role(['Super Admin']);

        $model = new Setting();
        $search = trim((string) $this->input('search', ''));
        $settings = $search === '' ? $model->findAll() : $model->search($search);
        $gratuityRate = $model->numericValue('gratuity_rate_percent', 5.0);
        $gratuityQualifyingYears = $model->numericValue('gratuity_qualifying_years', 2.0);
        $gratuityBasis = $model->value('gratuity_basis', 'annual_basic_earned');
        $gratuityPaymentTiming = $model->value('gratuity_payment_timing', 'End of contract');
        $documentSettings = [
            'document_letterhead_footer' => $model->value('document_letterhead_footer', ''),
            'document_default_signatory_name' => $model->value('document_default_signatory_name', ''),
            'document_default_signatory_title' => $model->value('document_default_signatory_title', ''),
            'document_signature_placeholders_enabled' => $model->value('document_signature_placeholders_enabled', '1'),
        ];
        $statutorySettings = [
            'statutory_registered_employer_name' => $model->value('statutory_registered_employer_name', (string) (current_company()['name'] ?? '')),
            'company_registration_number' => $model->value('company_registration_number', ''),
            'statutory_napsa_account_number' => $model->value('statutory_napsa_account_number', ''),
            'statutory_tpin' => $model->value('statutory_tpin', ''),
            'statutory_paye_account_number' => $model->value('statutory_paye_account_number', ''),
            'statutory_nhima_employer_number' => $model->value('statutory_nhima_employer_number', ''),
            'statutory_contact_person' => $model->value('statutory_contact_person', ''),
            'statutory_contact_phone' => $model->value('statutory_contact_phone', ''),
        ];

        $this->render('settings/index', [
            'title' => 'System Settings',
            'settings' => $settings,
            'gratuityRate' => $gratuityRate,
            'gratuityQualifyingYears' => $gratuityQualifyingYears,
            'gratuityBasis' => $gratuityBasis,
            'gratuityPaymentTiming' => $gratuityPaymentTiming,
            'documentSettings' => $documentSettings,
            'statutorySettings' => $statutorySettings,
            'search' => $search,
            'company' => current_company(),
            'csrf' => Session::csrfToken(),
            'flashSuccess' => Session::flash('success'),
            'flashError' => Session::flash('error'),
        ]);
    }

    public function create(): void
    {
        require_auth();
        require_role(['Super Admin']);

        $this->render('settings/create', [
            'title' => 'Create Setting',
            'csrf' => Session::csrfToken(),
            'flashError' => Session::flash('error'),
            'old' => $_SESSION['_old_setting_input'] ?? [],
        ]);

        unset($_SESSION['_old_setting_input']);
    }

    public function store(): void
    {
        require_auth();
        require_role(['Super Admin']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('settings/index');
        }

        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid request token.');
            redirect('settings/create');
        }

        $data = $this->collectInput();
        $_SESSION['_old_setting_input'] = $data;

        if ($data['setting_key'] === '') {
            Session::flash('error', 'Setting key is required.');
            redirect('settings/create');
        }

        $model = new Setting();
        if ($model->keyExists($data['setting_key'])) {
            Session::flash('error', 'Setting key already exists.');
            redirect('settings/create');
        }

        try {
            $model->insert($data);
            unset($_SESSION['_old_setting_input']);
            Session::flash('success', 'Setting created successfully.');
            redirect('settings/index');
        } catch (PDOException) {
            Session::flash('error', 'Failed to create setting.');
            redirect('settings/create');
        }
    }

    public function edit(string $id): void
    {
        require_auth();
        require_role(['Super Admin']);

        $settingId = (int) $id;
        if ($settingId <= 0) {
            Session::flash('error', 'Invalid setting id.');
            redirect('settings/index');
        }

        $model = new Setting();
        $setting = $model->find($settingId);

        if (!$setting) {
            Session::flash('error', 'Setting not found.');
            redirect('settings/index');
        }

        $this->render('settings/edit', [
            'title' => 'Edit Setting',
            'setting' => $setting,
            'csrf' => Session::csrfToken(),
            'flashError' => Session::flash('error'),
            'old' => $_SESSION['_old_setting_input'] ?? [],
        ]);

        unset($_SESSION['_old_setting_input']);
    }

    public function update(string $id): void
    {
        require_auth();
        require_role(['Super Admin']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('settings/index');
        }

        $settingId = (int) $id;
        if ($settingId <= 0) {
            Session::flash('error', 'Invalid setting id.');
            redirect('settings/index');
        }

        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid request token.');
            redirect('settings/edit/' . $settingId);
        }

        $model = new Setting();
        $existing = $model->find($settingId);
        if (!$existing) {
            Session::flash('error', 'Setting not found.');
            redirect('settings/index');
        }

        $data = $this->collectInput();
        $_SESSION['_old_setting_input'] = $data;

        if ($data['setting_key'] === '') {
            Session::flash('error', 'Setting key is required.');
            redirect('settings/edit/' . $settingId);
        }

        if ($model->keyExists($data['setting_key'], $settingId)) {
            Session::flash('error', 'Setting key already exists.');
            redirect('settings/edit/' . $settingId);
        }

        try {
            $model->update($settingId, $data);
            unset($_SESSION['_old_setting_input']);
            Session::flash('success', 'Setting updated successfully.');
            redirect('settings/index');
        } catch (PDOException) {
            Session::flash('error', 'Failed to update setting.');
            redirect('settings/edit/' . $settingId);
        }
    }

    public function delete(string $id): void
    {
        require_auth();
        require_role(['Super Admin']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('settings/index');
        }

        $settingId = (int) $id;
        if ($settingId <= 0) {
            Session::flash('error', 'Invalid setting id.');
            redirect('settings/index');
        }

        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid request token.');
            redirect('settings/index');
        }

        $model = new Setting();

        try {
            $model->delete($settingId);
            Session::flash('success', 'Setting deleted successfully.');
        } catch (PDOException) {
            Session::flash('error', 'Failed to delete setting.');
        }

        redirect('settings/index');
    }

    public function email(): void
    {
        require_auth();
        require_role(['Super Admin']);

        $notifier = new ContractNotification();
        $s        = $notifier->emailSettings();
        $templateModel = new CompanyEmailTemplate();
        $passwordSaved = trim((string) ($s['smtp_password'] ?? '')) !== '';
        unset($s['smtp_password']);

        $this->render('settings/email', [
            'title'        => 'Email & Notification Settings',
            's'            => $s,
            'emailTemplates' => $templateModel->all(),
            'emailTokens' => $templateModel->tokens(),
            'passwordSaved'=> $passwordSaved,
            'csrf'         => Session::csrfToken(),
            'flashSuccess' => Session::flash('success'),
            'flashError'   => Session::flash('error'),
        ]);
    }

    public function updateEmail(): void
    {
        require_auth();
        require_role(['Super Admin']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('settings/email');
        }

        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid request token.');
            redirect('settings/email');
        }

        $db   = db();
        $cid  = company_id();
        $keys = [
            'email_notifications_enabled',
            'smtp_host', 'smtp_port', 'smtp_encryption',
            'smtp_username', 'smtp_from_email', 'smtp_from_name', 'smtp_hr_email',
        ];

        foreach ($keys as $key) {
            $value = trim((string) $this->input($key, ''));
            $db->prepare(
                "INSERT INTO settings (company_id, setting_key, setting_value)
                 VALUES (:cid, :k, :v)
                 ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)"
            )->execute(['cid' => $cid, 'k' => $key, 'v' => $value]);
        }

        $newPass = trim((string) $this->input('smtp_password', ''));
        if ($newPass !== '') {
            $db->prepare(
                "INSERT INTO settings (company_id, setting_key, setting_value)
                 VALUES (:cid, 'smtp_password', :v)
                 ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)"
            )->execute(['cid' => $cid, 'v' => SecretBox::encrypt($newPass)]);
        }

        Session::flash('success', 'Email settings saved.');
        redirect('settings/email');
    }

    public function updateEmailTemplates(): void
    {
        require_auth();
        require_role(['Super Admin']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('settings/email');
        }

        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid request token.');
            redirect('settings/email');
        }

        (new CompanyEmailTemplate())->save([
            'contract_subject' => (string) $this->input('contract_subject', ''),
            'contract_body' => (string) $this->input('contract_body', ''),
            'contract_attach_document' => (string) $this->input('contract_attach_document', '') === '1',
            'payslip_subject' => (string) $this->input('payslip_subject', ''),
            'payslip_body' => (string) $this->input('payslip_body', ''),
            'payslip_attach_document' => (string) $this->input('payslip_attach_document', '') === '1',
            'signature' => (string) $this->input('signature', ''),
        ]);

        AuditLog::record('settings_update', 'Updated company email templates and signature.', 'Settings');
        Session::flash('success', 'Email templates and signature saved.');
        redirect('settings/email');
    }

    public function updatePayrollPolicy(): void
    {
        require_auth();
        require_role(['Super Admin']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('settings/index');
        }

        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid request token.');
            redirect('settings/index');
        }

        $rate = trim((string) $this->input('gratuity_rate_percent', '5'));
        if (!is_numeric($rate)) {
            Session::flash('error', 'Gratuity rate must be a number.');
            redirect('settings/index');
        }

        $rateValue = (float) $rate;
        if ($rateValue < 0 || $rateValue > 100) {
            Session::flash('error', 'Gratuity rate must be between 0 and 100.');
            redirect('settings/index');
        }

        $years = trim((string) $this->input('gratuity_qualifying_years', '2'));
        if (!is_numeric($years)) {
            Session::flash('error', 'Qualifying years must be a number.');
            redirect('settings/index');
        }

        $yearsValue = (float) $years;
        if ($yearsValue < 0 || $yearsValue > 50) {
            Session::flash('error', 'Qualifying years must be between 0 and 50.');
            redirect('settings/index');
        }

        $basis = (string) $this->input('gratuity_basis', 'annual_basic_earned');
        if (!in_array($basis, ['annual_basic_earned', 'monthly_basic_served'], true)) {
            Session::flash('error', 'Invalid gratuity calculation basis.');
            redirect('settings/index');
        }

        $paymentTiming = trim((string) $this->input('gratuity_payment_timing', 'End of contract'));
        if ($paymentTiming === '') {
            $paymentTiming = 'End of contract';
        }

        $settingModel = new Setting();
        $formatNumber = static fn(float $value): string => rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');

        $settingModel->upsert('gratuity_rate_percent', $formatNumber($rateValue));
        $settingModel->upsert('gratuity_qualifying_years', $formatNumber($yearsValue));
        $settingModel->upsert('gratuity_basis', $basis);
        $settingModel->upsert('gratuity_payment_timing', $paymentTiming);

        Session::flash('success', 'Payroll policy settings saved.');
        redirect('settings/index');
    }

    public function updateStatutorySettings(): void
    {
        require_auth();
        require_role(['Super Admin']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('settings/index');
        }

        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid request token.');
            redirect('settings/index');
        }

        $settings = new Setting();
        foreach ([
            'statutory_registered_employer_name',
            'company_registration_number',
            'statutory_napsa_account_number',
            'statutory_tpin',
            'statutory_paye_account_number',
            'statutory_nhima_employer_number',
            'statutory_contact_person',
            'statutory_contact_phone',
        ] as $key) {
            $settings->upsert($key, trim((string) $this->input($key, '')));
        }

        AuditLog::record('settings_update', 'Updated statutory registration details.', 'Settings');
        Session::flash('success', 'Statutory registration details saved.');
        redirect('settings/index');
    }

    public function testEmail(): void
    {
        require_auth();
        require_role(['Super Admin']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('settings/email');
        }

        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid request token.');
            redirect('settings/email');
        }

        $to = trim((string) $this->input('test_email', ''));
        if ($to === '') {
            Session::flash('error', 'Please enter a recipient email address.');
            redirect('settings/email');
        }

        $notifier = new ContractNotification();
        $mailer   = $notifier->buildMailer();

        $html = '<html><body style="font-family:Arial,sans-serif;padding:20px">
                 <h2 style="color:#15803d">&#10003; Test Email</h2>
                 <p>This is a test email from <strong>' . e(app_product_name()) . '</strong>.</p>
                 <p>If you received this, your SMTP settings are configured correctly.</p>
                 <p style="color:#888;font-size:11px">Sent: ' . date('Y-m-d H:i:s') . '</p>
                 </body></html>';

        $ok = $mailer->send($to, '', 'Test Email - ' . app_product_name(), $html);

        if ($ok) {
            Session::flash('success', "Test email sent successfully to {$to}.");
        } else {
            $detail = $mailer->lastError() !== '' ? $mailer->lastError() : 'Check SMTP settings and error logs.';
            Session::flash('error', 'Failed to send test email. ' . $detail);
        }

        redirect('settings/email');
    }

    public function updateCompanyLogo(): void
    {
        require_auth();
        require_role(['Super Admin']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('settings/index');
        }

        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid request token.');
            redirect('settings/index');
        }

        $company = current_company();
        if (!$company) {
            Session::flash('error', 'No active company found.');
            redirect('settings/index');
        }

        try {
            $logoPath = $this->storeCompanyLogo((int) $company['id'], $_FILES['company_logo'] ?? null);
            $this->deleteStoredLogo((string) ($company['logo_path'] ?? ''));

            db()->prepare('UPDATE companies SET logo_path = :logo WHERE id = :id')
                ->execute(['logo' => $logoPath, 'id' => (int) $company['id']]);

            if (Tenant::id() === (int) $company['id']) {
                $updated = db()->prepare('SELECT * FROM companies WHERE id = :id LIMIT 1');
                $updated->execute(['id' => (int) $company['id']]);
                $row = $updated->fetch();
                if ($row) {
                    Tenant::set($row);
                }
            }

            Session::flash('success', 'Company logo updated.');
        } catch (Throwable $e) {
            Session::flash('error', $e->getMessage());
        }

        redirect('settings/index');
    }

    public function removeCompanyLogo(): void
    {
        require_auth();
        require_role(['Super Admin']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('settings/index');
        }

        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid request token.');
            redirect('settings/index');
        }

        $company = current_company();
        if ($company) {
            $this->deleteStoredLogo((string) ($company['logo_path'] ?? ''));
            db()->prepare('UPDATE companies SET logo_path = NULL WHERE id = :id')
                ->execute(['id' => (int) $company['id']]);
            $updated = db()->prepare('SELECT * FROM companies WHERE id = :id LIMIT 1');
            $updated->execute(['id' => (int) $company['id']]);
            $row = $updated->fetch();
            if ($row) {
                Tenant::set($row);
            }
        }

        Session::flash('success', 'Company logo removed.');
        redirect('settings/index');
    }

    public function updateDocumentSettings(): void
    {
        require_auth();
        require_role(['Super Admin']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('settings/index');
        }

        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid request token.');
            redirect('settings/index');
        }

        $settings = new Setting();
        $settings->upsert('document_letterhead_footer', trim((string) $this->input('document_letterhead_footer', '')));
        $settings->upsert('document_default_signatory_name', trim((string) $this->input('document_default_signatory_name', '')));
        $settings->upsert('document_default_signatory_title', trim((string) $this->input('document_default_signatory_title', '')));
        $settings->upsert('document_signature_placeholders_enabled', $this->input('document_signature_placeholders_enabled', '0') === '1' ? '1' : '0');

        AuditLog::record('settings_update', 'Updated document letterhead and signature settings.', 'Settings');
        Session::flash('success', 'Document settings saved.');
        redirect('settings/index');
    }

    private function collectInput(): array
    {
        return [
            'setting_key' => strtolower(trim((string) $this->input('setting_key', ''))),
            'setting_value' => trim((string) $this->input('setting_value', '')),
        ];
    }

    private function storeCompanyLogo(int $companyId, ?array $file): string
    {
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Please choose a logo file to upload.');
        }

        if ((int) ($file['size'] ?? 0) > 2 * 1024 * 1024) {
            throw new RuntimeException('Logo must be 2 MB or smaller.');
        }

        $mime = mime_content_type((string) $file['tmp_name']);
        $extensions = [
            'image/png' => 'png',
            'image/jpeg' => 'jpg',
            'image/webp' => 'webp',
        ];

        if (!isset($extensions[$mime])) {
            throw new RuntimeException('Logo must be a PNG, JPG, or WebP image.');
        }

        $dir = BASE_PATH . '/public/uploads/company_logos';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $filename = 'company_' . $companyId . '_' . bin2hex(random_bytes(8)) . '.' . $extensions[$mime];
        $dest = $dir . '/' . $filename;

        if (!move_uploaded_file((string) $file['tmp_name'], $dest)) {
            throw new RuntimeException('Failed to save company logo.');
        }

        return 'public/uploads/company_logos/' . $filename;
    }

    private function deleteStoredLogo(string $logoPath): void
    {
        if ($logoPath === '' || !str_starts_with($logoPath, 'public/uploads/company_logos/')) {
            return;
        }

        $path = BASE_PATH . '/' . $logoPath;
        if (is_file($path)) {
            @unlink($path);
        }
    }
}
