<?php

declare(strict_types=1);

class SuperadminInvoiceController extends Controller
{
    public function index(): void
    {
        require_superadmin();
        $model = new SubscriptionInvoice();

        $this->renderSuperAdmin('superadmin/invoices/index', [
            'title' => 'Invoices & Payments',
            'invoices' => $model->listAll(),
            'csrf' => Session::csrfToken(),
            'flash' => Session::flash('success'),
            'flashErr' => Session::flash('error'),
        ]);
    }

    public function view(string $id = ''): void
    {
        require_superadmin();
        $invoiceId = (int) $id;
        $model = new SubscriptionInvoice();
        $invoice = $model->findDetailed($invoiceId);
        if (!$invoice) {
            Session::flash('error', 'Invoice not found.');
            redirect('superadmin/invoice/index');
        }

        $this->renderSuperAdmin('superadmin/invoices/view', [
            'title' => 'Invoice ' . (string) $invoice['invoice_number'],
            'invoice' => $invoice,
            'lines' => $model->lines($invoiceId),
            'payments' => $model->payments($invoiceId),
            'csrf' => Session::csrfToken(),
            'flash' => Session::flash('success'),
            'flashErr' => Session::flash('error'),
        ]);
    }

    public function createFromSubscription(string $subscriptionId = ''): void
    {
        require_superadmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('superadmin/subscription/index'); }
        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid token.');
            redirect('superadmin/subscription/index');
        }

        try {
            $invoiceId = (new SubscriptionInvoice())->createFromSubscription(
                (int) $subscriptionId,
                (int) ($_SESSION['superadmin_user']['id'] ?? 0)
            );
            AuditLog::recordPlatform('created', 'Created invoice from subscription ' . (string) $subscriptionId, 'SubscriptionInvoice', $invoiceId);
            Session::flash('success', 'Invoice created.');
            redirect('superadmin/invoice/view/' . $invoiceId);
        } catch (Throwable $e) {
            Session::flash('error', 'Invoice could not be created: ' . $e->getMessage());
            redirect('superadmin/subscription/index');
        }
    }

    public function recordPayment(string $id = ''): void
    {
        require_superadmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('superadmin/invoice/index'); }
        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid token.');
            redirect('superadmin/invoice/view/' . (int) $id);
        }

        $invoiceId = (int) $id;
        $amount = $this->money((string) $this->input('amount', '0'));
        $paidAt = $this->dateOrToday((string) $this->input('paid_at', ''));
        $method = trim((string) $this->input('payment_method', ''));
        $reference = trim((string) $this->input('payment_reference', ''));
        $notes = trim((string) $this->input('notes', ''));

        try {
            (new SubscriptionInvoice())->recordPayment(
                $invoiceId,
                $amount,
                $paidAt,
                $method,
                $reference,
                $notes,
                (int) ($_SESSION['superadmin_user']['id'] ?? 0)
            );
            AuditLog::recordPlatform('payment_recorded', 'Recorded payment on invoice ' . (string) $invoiceId, 'SubscriptionInvoice', $invoiceId);
            Session::flash('success', 'Payment recorded.');
        } catch (Throwable $e) {
            Session::flash('error', $e->getMessage());
        }

        redirect('superadmin/invoice/view/' . $invoiceId);
    }

    public function print(string $id = ''): void
    {
        require_superadmin();
        $invoiceId = (int) $id;
        $model = new SubscriptionInvoice();
        $invoice = $model->findDetailed($invoiceId);
        if (!$invoice) {
            Session::flash('error', 'Invoice not found.');
            redirect('superadmin/invoice/index');
        }

        $this->renderAuth('superadmin/invoices/print', [
            'invoice' => $invoice,
            'lines' => $model->lines($invoiceId),
            'payments' => $model->payments($invoiceId),
        ]);
    }

    public function receipt(string $paymentId = ''): void
    {
        require_superadmin();
        $payment = (new SubscriptionInvoice())->paymentDetailed((int) $paymentId);
        if (!$payment) {
            Session::flash('error', 'Payment receipt not found.');
            redirect('superadmin/invoice/index');
        }

        $this->renderAuth('superadmin/invoices/receipt', ['payment' => $payment]);
    }

    public function email(string $id = ''): void
    {
        require_superadmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('superadmin/invoice/view/' . (int) $id); }
        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid token.');
            redirect('superadmin/invoice/view/' . (int) $id);
        }

        $invoiceId = (int) $id;
        $model = new SubscriptionInvoice();
        $invoice = $model->findDetailed($invoiceId);
        if (!$invoice) {
            Session::flash('error', 'Invoice not found.');
            redirect('superadmin/invoice/index');
        }

        $to = trim((string) ($invoice['company_email'] ?? ''));
        $subject = 'Invoice ' . (string) $invoice['invoice_number'] . ' - ' . app_vendor_name();
        $html = $this->invoiceEmailHtml($invoice, $model->lines($invoiceId));
        $mailer = new MailService([
            'email_notifications_enabled' => '1',
            'smtp_from_email' => 'emmanuel.libati@gmail.com',
            'smtp_from_name' => app_vendor_name(),
        ]);

        if ($mailer->send($to, (string) $invoice['company_name'], $subject, $html)) {
            $model->markSent($invoiceId, true);
            AuditLog::recordPlatform('invoice_emailed', 'Emailed invoice ' . (string) $invoice['invoice_number'], 'SubscriptionInvoice', $invoiceId);
            Session::flash('success', 'Invoice emailed to ' . $to . '.');
        } else {
            Session::flash('error', 'Invoice email could not be sent: ' . $mailer->lastError());
        }

        redirect('superadmin/invoice/view/' . $invoiceId);
    }

    public function markSent(string $id = ''): void
    {
        require_superadmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('superadmin/invoice/index'); }
        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid token.');
            redirect('superadmin/invoice/view/' . (int) $id);
        }

        $invoiceId = (int) $id;
        $invoice = (new SubscriptionInvoice())->findDetailed($invoiceId);
        if (!$invoice) {
            Session::flash('error', 'Invoice not found.');
            redirect('superadmin/invoice/index');
        }

        (new SubscriptionInvoice())->markSent($invoiceId, false);

        AuditLog::recordPlatform('marked_sent', 'Marked invoice as sent', 'SubscriptionInvoice', $invoiceId);
        Session::flash('success', 'Invoice marked as sent.');
        redirect('superadmin/invoice/view/' . $invoiceId);
    }

    public function affiliates(): void
    {
        require_superadmin();
        $this->ensureAffiliateSchema();
        $model = new Affiliate();

        $this->renderSuperAdmin('superadmin/affiliates/index', [
            'title' => 'Corevia Affiliates',
            'ready' => $model->tableReady(),
            'affiliates' => $model->listAll(),
            'analytics' => (new AffiliateOperations())->analytics(),
            'csrf' => Session::csrfToken(),
            'flash' => Session::flash('success'),
            'flashErr' => Session::flash('error'),
        ]);
    }

    public function affiliateCreate(): void
    {
        require_superadmin();
        $this->ensureAffiliateSchema();

        $this->renderSuperAdmin('superadmin/affiliates/create', [
            'title' => 'Create Affiliate',
            'csrf' => Session::csrfToken(),
            'flashErr' => Session::flash('error'),
        ]);
    }

    public function affiliateStore(): void
    {
        require_superadmin();
        $this->ensureAffiliateSchema();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('superadmin/invoice/affiliates'); }
        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid token.');
            redirect('superadmin/invoice/affiliateCreate');
        }

        $name = trim((string) $this->input('full_name', ''));
        $email = strtolower(trim((string) $this->input('email', '')));
        $phone = trim((string) $this->input('phone', ''));
        $password = (string) $this->input('password', '');
        $rate = max(0.0, min(100.0, (float) $this->input('commission_rate', '5')));
        $type = (string) $this->input('affiliate_type', 'Individual');
        if (!in_array($type, ['Individual','Company','Consultant','Reseller','Agency'], true)) {
            $type = 'Individual';
        }

        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8) {
            Session::flash('error', 'Name, valid email, and password of at least 8 characters are required.');
            redirect('superadmin/invoice/affiliateCreate');
        }

        try {
            $stmt = db()->prepare(
                "INSERT INTO affiliates
                 (affiliate_code, affiliate_type, full_name, trading_name, email, alternate_email, phone, alternate_phone, nrc_number, tpin, address, city, province, date_of_birth, password_hash, must_change_password, commission_rate, commission_basis, commission_duration, commission_months, one_off_bonus, payout_tax_rate, payout_method, payout_details, bank_name, bank_account_name, bank_account_number, mobile_money_number, kyc_status, is_active)
                 VALUES (:code, :type, :name, :trading_name, :email, :alternate_email, :phone, :alternate_phone, :nrc, :tpin, :address, :city, :province, :date_of_birth, :hash, 1, :rate, :basis, :duration, :months, :bonus, :tax_rate, :method, :details, :bank_name, :bank_account_name, :bank_account_number, :mobile_money_number, :kyc_status, 1)"
            );
            $stmt->execute([
                'code' => $this->generateAffiliateCode($name),
                'type' => $type,
                'name' => $name,
                'trading_name' => trim((string) $this->input('trading_name', '')) ?: null,
                'email' => $email,
                'alternate_email' => trim((string) $this->input('alternate_email', '')) ?: null,
                'phone' => $phone ?: null,
                'alternate_phone' => trim((string) $this->input('alternate_phone', '')) ?: null,
                'nrc' => trim((string) $this->input('nrc_number', '')) ?: null,
                'tpin' => trim((string) $this->input('tpin', '')) ?: null,
                'address' => trim((string) $this->input('address', '')) ?: null,
                'city' => trim((string) $this->input('city', '')) ?: null,
                'province' => trim((string) $this->input('province', '')) ?: null,
                'date_of_birth' => trim((string) $this->input('date_of_birth', '')) ?: null,
                'hash' => password_hash($password, PASSWORD_DEFAULT),
                'rate' => $rate,
                'basis' => $this->allowedValue((string) $this->input('commission_basis', 'Paid Amount'), ['Paid Amount','Invoice Amount','Net Amount'], 'Paid Amount'),
                'duration' => $this->allowedValue((string) $this->input('commission_duration', 'First Year'), ['First Year','Lifetime','Fixed Months'], 'First Year'),
                'months' => (int) $this->input('commission_months', 0) ?: null,
                'bonus' => max(0, (float) $this->input('one_off_bonus', 0)),
                'tax_rate' => max(0, min(100, (float) $this->input('payout_tax_rate', 0))),
                'method' => trim((string) $this->input('payout_method', '')) ?: null,
                'details' => trim((string) $this->input('payout_details', '')) ?: null,
                'bank_name' => trim((string) $this->input('bank_name', '')) ?: null,
                'bank_account_name' => trim((string) $this->input('bank_account_name', '')) ?: null,
                'bank_account_number' => trim((string) $this->input('bank_account_number', '')) ?: null,
                'mobile_money_number' => trim((string) $this->input('mobile_money_number', '')) ?: null,
                'kyc_status' => $this->allowedValue((string) $this->input('kyc_status', 'Draft'), ['Draft','Pending Review','Approved','Rejected'], 'Draft'),
            ]);
            AuditLog::recordPlatform('affiliate_created', 'Created affiliate ' . $email, 'Affiliate', (int) db()->lastInsertId());
            Session::flash('success', 'Affiliate created.');
            redirect('superadmin/invoice/affiliates');
        } catch (Throwable $e) {
            Session::flash('error', 'Affiliate could not be created: ' . $e->getMessage());
            redirect('superadmin/invoice/affiliateCreate');
        }
    }

    public function affiliateView(string $id = ''): void
    {
        require_superadmin();
        $this->ensureAffiliateSchema();
        $affiliateId = (int) $id;
        $affiliate = $this->affiliateOrFail($affiliateId);
        $model = new Affiliate();
        $ops = new AffiliateOperations();
        $ops->ensureSchema();

        $assigned = $model->companies($affiliateId);
        $assignedIds = array_map(static fn(array $row): int => (int) $row['company_id'], $assigned);
        $companies = db()->query('SELECT id, name, email, account_status FROM companies ORDER BY name ASC')->fetchAll();
        $plans = db()->query('SELECT id, name, default_monthly_rate, currency FROM subscription_plans WHERE is_active = 1 ORDER BY sort_order ASC, name ASC')->fetchAll();

        $this->renderSuperAdmin('superadmin/affiliates/view', [
            'title' => 'Affiliate: ' . (string) $affiliate['full_name'],
            'affiliate' => $affiliate,
            'dashboard' => $model->dashboard($affiliateId),
            'documents' => $model->documents($affiliateId),
            'leads' => $model->leads($affiliateId, 100),
            'payouts' => $ops->payoutBatches($affiliateId),
            'paymentMethods' => $ops->paymentMethods(),
            'agreements' => $ops->agreements($affiliateId),
            'statement' => $ops->statement($affiliateId),
            'assignedCompanyIds' => $assignedIds,
            'companies' => $companies,
            'plans' => $plans,
            'csrf' => Session::csrfToken(),
            'flash' => Session::flash('success'),
            'flashErr' => Session::flash('error'),
        ]);
    }

    public function affiliateEdit(string $id = ''): void
    {
        require_superadmin();
        $this->ensureAffiliateSchema();
        $affiliate = $this->affiliateOrFail((int) $id);

        $this->renderSuperAdmin('superadmin/affiliates/edit', [
            'title' => 'Edit Affiliate',
            'affiliate' => $affiliate,
            'csrf' => Session::csrfToken(),
            'flash' => Session::flash('success'),
            'flashErr' => Session::flash('error'),
        ]);
    }

    public function affiliateUpdate(string $id = ''): void
    {
        require_superadmin();
        $this->ensureAffiliateSchema();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('superadmin/invoice/affiliateView/' . (int) $id); }
        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid token.');
            redirect('superadmin/invoice/affiliateEdit/' . (int) $id);
        }

        $affiliate = $this->affiliateOrFail((int) $id);
        $name = trim((string) $this->input('full_name', ''));
        $email = strtolower(trim((string) $this->input('email', '')));
        $password = (string) $this->input('password', '');
        $rate = max(0.0, min(100.0, (float) $this->input('commission_rate', '5')));
        $type = $this->allowedValue((string) $this->input('affiliate_type', 'Individual'), ['Individual','Company','Consultant','Reseller','Agency'], 'Individual');

        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Session::flash('error', 'Name and valid email are required.');
            redirect('superadmin/invoice/affiliateEdit/' . (int) $affiliate['id']);
        }

        if ($password !== '' && strlen($password) < 8) {
            Session::flash('error', 'Temporary password must be at least 8 characters.');
            redirect('superadmin/invoice/affiliateEdit/' . (int) $affiliate['id']);
        }

        $fields = [
            'affiliate_type = :type',
            'full_name = :name',
            'trading_name = :trading_name',
            'email = :email',
            'alternate_email = :alternate_email',
            'phone = :phone',
            'alternate_phone = :alternate_phone',
            'nrc_number = :nrc',
            'tpin = :tpin',
            'address = :address',
            'city = :city',
            'province = :province',
            'date_of_birth = :date_of_birth',
            'commission_rate = :rate',
            'commission_basis = :basis',
            'commission_duration = :duration',
            'commission_months = :months',
            'one_off_bonus = :bonus',
            'payout_tax_rate = :tax_rate',
            'payout_method = :method',
            'payout_details = :details',
            'bank_name = :bank_name',
            'bank_account_name = :bank_account_name',
            'bank_account_number = :bank_account_number',
            'mobile_money_number = :mobile_money_number',
            'kyc_status = :kyc_status',
            'kyc_reviewed_by = :kyc_reviewed_by',
            'kyc_reviewed_at = :kyc_reviewed_at',
            'kyc_rejection_reason = :kyc_rejection_reason',
            'is_active = :active',
        ];
        $params = [
            'type' => $type,
            'name' => $name,
            'trading_name' => trim((string) $this->input('trading_name', '')) ?: null,
            'email' => $email,
            'alternate_email' => trim((string) $this->input('alternate_email', '')) ?: null,
            'phone' => trim((string) $this->input('phone', '')) ?: null,
            'alternate_phone' => trim((string) $this->input('alternate_phone', '')) ?: null,
            'nrc' => trim((string) $this->input('nrc_number', '')) ?: null,
            'tpin' => trim((string) $this->input('tpin', '')) ?: null,
            'address' => trim((string) $this->input('address', '')) ?: null,
            'city' => trim((string) $this->input('city', '')) ?: null,
            'province' => trim((string) $this->input('province', '')) ?: null,
            'date_of_birth' => trim((string) $this->input('date_of_birth', '')) ?: null,
            'rate' => $rate,
            'basis' => $this->allowedValue((string) $this->input('commission_basis', 'Paid Amount'), ['Paid Amount','Invoice Amount','Net Amount'], 'Paid Amount'),
            'duration' => $this->allowedValue((string) $this->input('commission_duration', 'First Year'), ['First Year','Lifetime','Fixed Months'], 'First Year'),
            'months' => (int) $this->input('commission_months', 0) ?: null,
            'bonus' => max(0, (float) $this->input('one_off_bonus', 0)),
            'tax_rate' => max(0, min(100, (float) $this->input('payout_tax_rate', 0))),
            'method' => trim((string) $this->input('payout_method', '')) ?: null,
            'details' => trim((string) $this->input('payout_details', '')) ?: null,
            'bank_name' => trim((string) $this->input('bank_name', '')) ?: null,
            'bank_account_name' => trim((string) $this->input('bank_account_name', '')) ?: null,
            'bank_account_number' => trim((string) $this->input('bank_account_number', '')) ?: null,
            'mobile_money_number' => trim((string) $this->input('mobile_money_number', '')) ?: null,
            'kyc_status' => $this->allowedValue((string) $this->input('kyc_status', 'Draft'), ['Draft','Pending Review','Approved','Rejected'], 'Draft'),
            'kyc_reviewed_by' => (int) ($_SESSION['superadmin_user']['id'] ?? 0) ?: null,
            'kyc_reviewed_at' => in_array((string) $this->input('kyc_status', 'Draft'), ['Approved','Rejected'], true) ? date('Y-m-d H:i:s') : null,
            'kyc_rejection_reason' => trim((string) $this->input('kyc_rejection_reason', '')) ?: null,
            'active' => (int) $this->input('is_active', 0) === 1 ? 1 : 0,
            'id' => (int) $affiliate['id'],
        ];

        if ($password !== '') {
            $fields[] = 'password_hash = :hash';
            $fields[] = 'must_change_password = 1';
            $params['hash'] = password_hash($password, PASSWORD_DEFAULT);
        }

        try {
            db()->prepare('UPDATE affiliates SET ' . implode(', ', $fields) . ' WHERE id = :id')->execute($params);
            AuditLog::recordPlatform('affiliate_updated', 'Updated affiliate ' . (string) $affiliate['email'], 'Affiliate', (int) $affiliate['id']);
            Session::flash('success', $password !== '' ? 'Affiliate updated and password reset. They must change it on next login.' : 'Affiliate updated.');
            redirect('superadmin/invoice/affiliateView/' . (int) $affiliate['id']);
        } catch (Throwable $e) {
            Session::flash('error', 'Affiliate could not be updated: ' . $e->getMessage());
            redirect('superadmin/invoice/affiliateEdit/' . (int) $affiliate['id']);
        }
    }

    public function affiliateAssignCompany(string $id = ''): void
    {
        require_superadmin();
        $this->ensureAffiliateSchema();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('superadmin/invoice/affiliateView/' . (int) $id); }
        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid token.');
            redirect('superadmin/invoice/affiliateView/' . (int) $id);
        }

        $affiliate = $this->affiliateOrFail((int) $id);
        $status = (string) $this->input('referral_status', 'Active');
        if (!in_array($status, ['Prospect','Trial','Active','Suspended','Cancelled'], true)) {
            $status = 'Active';
        }
        $rateInput = trim((string) $this->input('commission_rate', ''));
        $rate = $rateInput !== '' ? max(0.0, min(100.0, (float) $rateInput)) : null;

        try {
            db()->prepare(
                "INSERT INTO affiliate_referrals (affiliate_id, company_id, referral_status, commission_rate, referred_at, notes)
                 VALUES (:affiliate_id, :company_id, :status, :rate, :referred_at, :notes)
                 ON DUPLICATE KEY UPDATE affiliate_id = VALUES(affiliate_id), referral_status = VALUES(referral_status), commission_rate = VALUES(commission_rate), notes = VALUES(notes)"
            )->execute([
                'affiliate_id' => (int) $affiliate['id'],
                'company_id' => (int) $this->input('company_id', 0),
                'status' => $status,
                'rate' => $rate,
                'referred_at' => (string) $this->input('referred_at', date('Y-m-d')),
                'notes' => trim((string) $this->input('notes', '')) ?: null,
            ]);
            AuditLog::recordPlatform('affiliate_company_assigned', 'Assigned company to affiliate.', 'Affiliate', (int) $affiliate['id']);
            Session::flash('success', 'Company assigned to affiliate.');
        } catch (Throwable $e) {
            Session::flash('error', 'Company could not be assigned: ' . $e->getMessage());
        }

        redirect('superadmin/invoice/affiliateView/' . (int) $affiliate['id']);
    }

    public function affiliateLeadUpdate(string $id = ''): void
    {
        require_superadmin();
        $this->ensureAffiliateSchema();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('superadmin/invoice/affiliates'); }
        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid token.');
            redirect('superadmin/invoice/affiliates');
        }

        $leadId = (int) $id;
        $lead = db()->prepare('SELECT affiliate_id FROM affiliate_leads WHERE id = :id LIMIT 1');
        $lead->execute(['id' => $leadId]);
        $affiliateId = (int) ($lead->fetchColumn() ?: 0);
        $stage = (string) $this->input('stage', 'New');
        $companyId = (int) $this->input('converted_company_id', 0) ?: null;
        $notes = trim((string) $this->input('notes', '')) ?: null;
        (new Affiliate())->updateLeadStage($leadId, $stage, $companyId, $notes);

        AuditLog::recordPlatform('affiliate_lead_updated', 'Updated affiliate lead status.', 'AffiliateLead', $leadId);
        Session::flash('success', 'Lead status updated.');
        redirect($affiliateId > 0 ? 'superadmin/invoice/affiliateView/' . $affiliateId : 'superadmin/invoice/affiliates');
    }

    public function affiliateLeadConvert(string $id = ''): void
    {
        require_superadmin();
        $this->ensureAffiliateSchema();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('superadmin/invoice/affiliates'); }
        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid token.');
            redirect('superadmin/invoice/affiliates');
        }

        try {
            $companyId = (new AffiliateOperations())->convertLead((int) $id, (int) $this->input('plan_id', 0), (int) ($_SESSION['superadmin_user']['id'] ?? 0));
            AuditLog::recordPlatform('affiliate_lead_converted', 'Converted affiliate lead into company.', 'Company', $companyId);
            Session::flash('success', 'Lead converted into a company account.');
        } catch (Throwable $e) {
            Session::flash('error', $e->getMessage());
        }
        redirect('superadmin/invoice/affiliates');
    }

    public function affiliatePayoutCreate(string $id = ''): void
    {
        require_superadmin();
        $this->ensureAffiliateSchema();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('superadmin/invoice/affiliateView/' . (int) $id); }
        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid token.');
            redirect('superadmin/invoice/affiliateView/' . (int) $id);
        }

        try {
            $batchId = (new AffiliateOperations())->createPayoutBatch(
                (int) $id,
                (int) $this->input('payment_method_id', 0),
                trim((string) $this->input('period_from', '')) ?: null,
                trim((string) $this->input('period_to', '')) ?: null,
                (int) ($_SESSION['superadmin_user']['id'] ?? 0)
            );
            AuditLog::recordPlatform('affiliate_payout_created', 'Created affiliate payout batch.', 'AffiliatePayout', $batchId);
            Session::flash('success', 'Payout batch created.');
            redirect('superadmin/invoice/affiliatePayoutView/' . $batchId);
        } catch (Throwable $e) {
            Session::flash('error', $e->getMessage());
            redirect('superadmin/invoice/affiliateView/' . (int) $id);
        }
    }

    public function affiliatePayoutView(string $id = ''): void
    {
        require_superadmin();
        $ops = new AffiliateOperations();
        $batch = $ops->payoutBatch((int) $id);
        if (!$batch) {
            Session::flash('error', 'Payout batch not found.');
            redirect('superadmin/invoice/affiliates');
        }

        $this->renderSuperAdmin('superadmin/affiliates/payout', [
            'title' => 'Affiliate Payout ' . (string) $batch['payout_reference'],
            'batch' => $batch,
            'items' => $ops->payoutItems((int) $batch['id']),
            'csrf' => Session::csrfToken(),
            'flash' => Session::flash('success'),
            'flashErr' => Session::flash('error'),
        ]);
    }

    public function affiliatePayoutStatus(string $id = ''): void
    {
        require_superadmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('superadmin/invoice/affiliatePayoutView/' . (int) $id); }
        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid token.');
            redirect('superadmin/invoice/affiliatePayoutView/' . (int) $id);
        }

        try {
            (new AffiliateOperations())->updatePayoutStatus(
                (int) $id,
                (string) $this->input('status', 'Submitted'),
                trim((string) $this->input('payment_reference', '')) ?: null,
                (int) ($_SESSION['superadmin_user']['id'] ?? 0)
            );
            AuditLog::recordPlatform('affiliate_payout_status', 'Updated affiliate payout status.', 'AffiliatePayout', (int) $id);
            Session::flash('success', 'Payout status updated.');
        } catch (Throwable $e) {
            Session::flash('error', $e->getMessage());
        }

        redirect('superadmin/invoice/affiliatePayoutView/' . (int) $id);
    }

    public function affiliatePayoutPrint(string $id = ''): void
    {
        require_superadmin();
        $ops = new AffiliateOperations();
        $batch = $ops->payoutBatch((int) $id);
        if (!$batch) { http_response_code(404); exit('Payout not found.'); }
        $this->renderAuth('superadmin/affiliates/payout-print', [
            'batch' => $batch,
            'items' => $ops->payoutItems((int) $batch['id']),
        ]);
    }

    public function affiliatePayoutEmail(string $id = ''): void
    {
        require_superadmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('superadmin/invoice/affiliatePayoutView/' . (int) $id); }
        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid token.');
            redirect('superadmin/invoice/affiliatePayoutView/' . (int) $id);
        }

        $ops = new AffiliateOperations();
        $batch = $ops->payoutBatch((int) $id);
        if (!$batch) {
            Session::flash('error', 'Payout not found.');
            redirect('superadmin/invoice/affiliates');
        }
        $html = $this->payoutStatementHtml($batch, $ops->payoutItems((int) $batch['id']));
        $mailer = new MailService([
            'email_notifications_enabled' => '1',
            'smtp_from_email' => 'emmanuel.libati@gmail.com',
            'smtp_from_name' => app_vendor_name(),
        ]);
        if ($mailer->send((string) $batch['affiliate_email'], (string) $batch['affiliate_name'], 'Corevia affiliate payout statement ' . (string) $batch['payout_reference'], $html)) {
            AuditLog::recordPlatform('affiliate_payout_emailed', 'Emailed affiliate payout statement.', 'AffiliatePayout', (int) $batch['id']);
            Session::flash('success', 'Payout statement emailed.');
        } else {
            Session::flash('error', 'Email could not be sent: ' . $mailer->lastError());
        }
        redirect('superadmin/invoice/affiliatePayoutView/' . (int) $id);
    }

    public function affiliateAgreementCreate(string $id = ''): void
    {
        require_superadmin();
        $this->ensureAffiliateSchema();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('superadmin/invoice/affiliateView/' . (int) $id); }
        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid token.');
            redirect('superadmin/invoice/affiliateView/' . (int) $id);
        }

        try {
            $agreementId = (new AffiliateOperations())->createAgreement((int) $id, $_POST, (int) ($_SESSION['superadmin_user']['id'] ?? 0));
            AuditLog::recordPlatform('affiliate_agreement_created', 'Generated affiliate agreement.', 'AffiliateAgreement', $agreementId);
            Session::flash('success', 'Agreement generated.');
        } catch (Throwable $e) {
            Session::flash('error', $e->getMessage());
        }
        redirect('superadmin/invoice/affiliateView/' . (int) $id);
    }

    public function affiliateAgreementStatus(string $id = ''): void
    {
        require_superadmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('superadmin/invoice/affiliates'); }
        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid token.');
            redirect('superadmin/invoice/affiliates');
        }
        (new AffiliateOperations())->updateAgreementStatus((int) $id, (string) $this->input('status', 'Draft'));
        AuditLog::recordPlatform('affiliate_agreement_status', 'Updated affiliate agreement status.', 'AffiliateAgreement', (int) $id);
        Session::flash('success', 'Agreement status updated.');
        redirect('superadmin/invoice/affiliates');
    }

    public function affiliateAgreementUpload(string $id = ''): void
    {
        require_superadmin();
        $this->ensureAffiliateSchema();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('superadmin/invoice/affiliates'); }
        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid token.');
            redirect('superadmin/invoice/affiliates');
        }

        $agreementId = (int) $id;
        $stmt = db()->prepare('SELECT affiliate_id FROM affiliate_agreements WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $agreementId]);
        $affiliateId = (int) ($stmt->fetchColumn() ?: 0);
        $file = $_FILES['signed_document'] ?? null;
        if (!is_array($file)) {
            Session::flash('error', 'Please choose a signed agreement.');
            redirect($affiliateId > 0 ? 'superadmin/invoice/affiliateView/' . $affiliateId : 'superadmin/invoice/affiliates');
        }

        try {
            $mime = UploadedFileGuard::validate($file, UploadedFileGuard::DOCUMENT_MIMES, 8 * 1024 * 1024);
            $safeName = UploadedFileGuard::safeStoredName('agreement_' . $agreementId, $mime, UploadedFileGuard::DOCUMENT_MIMES);
            $dir = BASE_PATH . '/uploads/affiliate_agreements/';
            if (!is_dir($dir)) { mkdir($dir, 0755, true); }
            if (!move_uploaded_file((string) $file['tmp_name'], $dir . $safeName)) {
                throw new RuntimeException('Failed to save signed agreement.');
            }
            db()->prepare("UPDATE affiliate_agreements SET signed_document_path = :path, status = 'Signed', signed_at = NOW() WHERE id = :id")
                ->execute(['path' => 'uploads/affiliate_agreements/' . $safeName, 'id' => $agreementId]);
            AuditLog::recordPlatform('affiliate_agreement_uploaded', 'Uploaded signed affiliate agreement.', 'AffiliateAgreement', $agreementId);
            Session::flash('success', 'Signed agreement uploaded.');
        } catch (Throwable $e) {
            Session::flash('error', $e->getMessage());
        }
        redirect($affiliateId > 0 ? 'superadmin/invoice/affiliateView/' . $affiliateId : 'superadmin/invoice/affiliates');
    }

    public function affiliateStatement(string $id = ''): void
    {
        require_superadmin();
        $ops = new AffiliateOperations();
        $statement = $ops->statement((int) $id);
        $format = (string) $this->input('export', '');
        if (in_array($format, ['csv','xls'], true)) {
            $this->exportAffiliateStatement($statement, $format);
        }
        $affiliate = $this->affiliateOrFail((int) $id);
        $this->renderAuth('superadmin/affiliates/statement-print', ['affiliate' => $affiliate, 'statement' => $statement]);
    }

    public function affiliateMarkPaid(string $id = ''): void
    {
        require_superadmin();
        $this->ensureAffiliateSchema();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('superadmin/invoice/affiliates'); }
        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid token.');
            redirect('superadmin/invoice/affiliateView/' . (int) $id);
        }

        $affiliate = $this->affiliateOrFail((int) $id);
        db()->prepare(
            "UPDATE affiliate_commissions
             SET status = 'Paid', paid_at = CURDATE(), payout_reference = :reference
             WHERE affiliate_id = :id AND status IN ('Pending','Approved')"
        )->execute([
            'reference' => trim((string) $this->input('payout_reference', '')) ?: null,
            'id' => (int) $affiliate['id'],
        ]);

        AuditLog::recordPlatform('affiliate_commission_paid', 'Marked affiliate commissions as paid.', 'Affiliate', (int) $affiliate['id']);
        Session::flash('success', 'Outstanding affiliate commissions marked as paid.');
        redirect('superadmin/invoice/affiliateView/' . (int) $affiliate['id']);
    }

    public function affiliateSync(): void
    {
        require_superadmin();
        $this->ensureAffiliateSchema();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('superadmin/invoice/affiliates'); }
        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid token.');
            redirect('superadmin/invoice/affiliates');
        }

        $count = (new Affiliate())->syncCommissions();
        AuditLog::recordPlatform('affiliate_commission_sync', "Synced affiliate commissions from paid receipts. Created {$count} record(s).", 'Affiliate', null);
        Session::flash('success', "Commission sync complete. {$count} commission record(s) created.");
        redirect('superadmin/invoice/affiliates');
    }

    public function affiliateCommissionReverse(string $id = ''): void
    {
        require_superadmin();
        $this->ensureAffiliateSchema();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('superadmin/invoice/affiliates'); }
        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid token.');
            redirect('superadmin/invoice/affiliates');
        }
        $stmt = db()->prepare('SELECT affiliate_id FROM affiliate_commissions WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => (int) $id]);
        $affiliateId = (int) ($stmt->fetchColumn() ?: 0);
        db()->prepare("UPDATE affiliate_commissions SET status = 'Reversed', reversed_at = NOW(), reversal_reason = :reason WHERE id = :id")
            ->execute(['reason' => trim((string) $this->input('reason', '')) ?: 'Manual reversal', 'id' => (int) $id]);
        AuditLog::recordPlatform('affiliate_commission_reversed', 'Reversed affiliate commission.', 'AffiliateCommission', (int) $id);
        Session::flash('success', 'Commission reversed.');
        redirect($affiliateId > 0 ? 'superadmin/invoice/affiliateView/' . $affiliateId : 'superadmin/invoice/affiliates');
    }

    public function affiliateCommissionAdjust(string $id = ''): void
    {
        require_superadmin();
        $this->ensureAffiliateSchema();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('superadmin/invoice/affiliates'); }
        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid token.');
            redirect('superadmin/invoice/affiliates');
        }
        $amount = $this->money((string) $this->input('commission_amount', '0'));
        $stmt = db()->prepare('SELECT affiliate_id FROM affiliate_commissions WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => (int) $id]);
        $affiliateId = (int) ($stmt->fetchColumn() ?: 0);
        db()->prepare("UPDATE affiliate_commissions SET commission_amount = :amount, adjustment_reason = :reason, status = 'Approved' WHERE id = :id")
            ->execute(['amount' => $amount, 'reason' => trim((string) $this->input('reason', '')) ?: 'Manual adjustment', 'id' => (int) $id]);
        AuditLog::recordPlatform('affiliate_commission_adjusted', 'Adjusted affiliate commission.', 'AffiliateCommission', (int) $id);
        Session::flash('success', 'Commission adjusted and approved.');
        redirect($affiliateId > 0 ? 'superadmin/invoice/affiliateView/' . $affiliateId : 'superadmin/invoice/affiliates');
    }

    public function affiliateMessageCreate(string $id = ''): void
    {
        require_superadmin();
        $this->ensureAffiliateSchema();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('superadmin/invoice/affiliateView/' . (int) $id); }
        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid token.');
            redirect('superadmin/invoice/affiliateView/' . (int) $id);
        }
        $subject = trim((string) $this->input('subject', ''));
        $message = trim((string) $this->input('message', ''));
        if ($subject === '' || $message === '') {
            Session::flash('error', 'Subject and message are required.');
            redirect('superadmin/invoice/affiliateView/' . (int) $id);
        }
        $visibility = $this->allowedValue((string) $this->input('visibility', 'Specific Affiliate'), ['All Affiliates','Specific Affiliate','Internal Note'], 'Specific Affiliate');
        db()->prepare(
            "INSERT INTO affiliate_messages (affiliate_id, subject, message, visibility, created_by)
             VALUES (:affiliate_id, :subject, :message, :visibility, :created_by)"
        )->execute([
            'affiliate_id' => $visibility === 'All Affiliates' ? null : (int) $id,
            'subject' => $subject,
            'message' => $message,
            'visibility' => $visibility,
            'created_by' => (int) ($_SESSION['superadmin_user']['id'] ?? 0) ?: null,
        ]);
        AuditLog::recordPlatform('affiliate_message_created', 'Created affiliate message/note.', 'Affiliate', (int) $id);
        Session::flash('success', 'Message saved.');
        redirect('superadmin/invoice/affiliateView/' . (int) $id);
    }

    public function affiliateUploadDocument(string $id = ''): void
    {
        require_superadmin();
        $this->ensureAffiliateSchema();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('superadmin/invoice/affiliateView/' . (int) $id); }
        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid token.');
            redirect('superadmin/invoice/affiliateView/' . (int) $id);
        }

        $affiliate = $this->affiliateOrFail((int) $id);
        $file = $_FILES['document_file'] ?? null;
        if (!is_array($file)) {
            Session::flash('error', 'Please choose a document to upload.');
            redirect('superadmin/invoice/affiliateView/' . (int) $affiliate['id']);
        }

        try {
            $mime = UploadedFileGuard::validate($file, UploadedFileGuard::DOCUMENT_MIMES, 5 * 1024 * 1024);
            $safeName = UploadedFileGuard::safeStoredName('affiliate_' . (int) $affiliate['id'], $mime, UploadedFileGuard::DOCUMENT_MIMES);
            $dir = BASE_PATH . '/uploads/affiliate_docs/';
            if (!is_dir($dir)) { mkdir($dir, 0755, true); }
            $path = $dir . $safeName;
            if (!move_uploaded_file((string) $file['tmp_name'], $path)) {
                throw new RuntimeException('Failed to save uploaded document.');
            }

            $type = (string) $this->input('document_type', 'Other');
            if (!in_array($type, ['NRC','TPIN','Affiliate Agreement','Bank Proof','Other'], true)) {
                $type = 'Other';
            }

            db()->prepare(
                "INSERT INTO affiliate_documents (affiliate_id, document_type, file_name, file_path, file_size, mime_type, notes, uploaded_by)
                 VALUES (:affiliate_id, :document_type, :file_name, :file_path, :file_size, :mime_type, :notes, :uploaded_by)"
            )->execute([
                'affiliate_id' => (int) $affiliate['id'],
                'document_type' => $type,
                'file_name' => (string) ($file['name'] ?? 'document'),
                'file_path' => 'uploads/affiliate_docs/' . $safeName,
                'file_size' => (int) ($file['size'] ?? 0),
                'mime_type' => $mime,
                'notes' => trim((string) $this->input('notes', '')) ?: null,
                'uploaded_by' => (int) ($_SESSION['superadmin_user']['id'] ?? 0) ?: null,
            ]);

            AuditLog::recordPlatform('affiliate_document_uploaded', 'Uploaded affiliate document.', 'Affiliate', (int) $affiliate['id']);
            Session::flash('success', 'Affiliate document uploaded.');
        } catch (Throwable $e) {
            Session::flash('error', $e->getMessage());
        }

        redirect('superadmin/invoice/affiliateView/' . (int) $affiliate['id']);
    }

    public function affiliateDownloadDocument(string $documentId = ''): void
    {
        require_superadmin();
        $this->ensureAffiliateSchema();
        $doc = (new Affiliate())->document((int) $documentId);
        if (!$doc) {
            Session::flash('error', 'Document not found.');
            redirect('superadmin/invoice/affiliates');
        }

        $path = BASE_PATH . '/' . ltrim((string) $doc['file_path'], '/');
        if (!is_file($path)) {
            Session::flash('error', 'Document file is missing.');
            redirect('superadmin/invoice/affiliateView/' . (int) $doc['affiliate_id']);
        }

        $downloadName = preg_replace('/[^A-Za-z0-9._-]/', '_', (string) ($doc['file_name'] ?? 'affiliate-document'));
        header('Content-Type: ' . ((string) ($doc['mime_type'] ?? '') ?: 'application/octet-stream'));
        header('X-Content-Type-Options: nosniff');
        header('Content-Disposition: attachment; filename="' . trim((string) $downloadName, '._') . '"');
        header('Content-Length: ' . filesize($path));
        readfile($path);
        exit;
    }

    public function affiliateDeleteDocument(string $documentId = ''): void
    {
        require_superadmin();
        $this->ensureAffiliateSchema();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('superadmin/invoice/affiliates'); }
        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid token.');
            redirect('superadmin/invoice/affiliates');
        }

        $doc = (new Affiliate())->document((int) $documentId);
        if (!$doc) {
            Session::flash('error', 'Document not found.');
            redirect('superadmin/invoice/affiliates');
        }

        $path = BASE_PATH . '/' . ltrim((string) $doc['file_path'], '/');
        if (is_file($path)) { @unlink($path); }
        db()->prepare('DELETE FROM affiliate_documents WHERE id = :id')->execute(['id' => (int) $doc['id']]);
        AuditLog::recordPlatform('affiliate_document_deleted', 'Deleted affiliate document.', 'Affiliate', (int) $doc['affiliate_id']);
        Session::flash('success', 'Affiliate document deleted.');
        redirect('superadmin/invoice/affiliateView/' . (int) $doc['affiliate_id']);
    }

    private function money(string $value): float
    {
        return round(max(0, (float) preg_replace('/[^\d.]/', '', $value)), 2);
    }

    private function dateOrToday(string $value): string
    {
        $date = date_create(trim($value));
        return $date ? $date->format('Y-m-d') : date('Y-m-d');
    }

    private function invoiceEmailHtml(array $invoice, array $lines): string
    {
        $rows = '';
        foreach ($lines as $line) {
            $rows .= '<tr><td style="padding:8px;border-bottom:1px solid #e5e7eb">' . e((string) $line['description']) . '</td><td style="padding:8px;border-bottom:1px solid #e5e7eb;text-align:right">' . e((string) $invoice['currency']) . ' ' . number_format((float) $line['line_total'], 2) . '</td></tr>';
        }
        $invoiceNo = e((string) $invoice['invoice_number']);
        $company = e((string) $invoice['company_name']);
        $currency = e((string) $invoice['currency']);
        $total = number_format((float) $invoice['total_amount'], 2);
        $balance = number_format((float) $invoice['balance_due'], 2);
        $due = e((string) $invoice['due_date']);

        return <<<HTML
        <div style="font-family:Arial,sans-serif;color:#111827;line-height:1.6">
            <h2 style="margin-bottom:4px">Invoice {$invoiceNo}</h2>
            <p>Dear {$company},</p>
            <p>Please find your subscription invoice summary below. You can print/save the invoice from the platform.</p>
            <table style="border-collapse:collapse;width:100%;max-width:680px">{$rows}</table>
            <p><strong>Total:</strong> {$currency} {$total}<br><strong>Balance Due:</strong> {$currency} {$balance}<br><strong>Due Date:</strong> {$due}</p>
            <p>Regards,<br>{$this->escapeVendor()}</p>
        </div>
        HTML;
    }

    private function escapeVendor(): string
    {
        return e(app_vendor_name());
    }

    private function payoutStatementHtml(array $batch, array $items): string
    {
        $rows = '';
        foreach ($items as $item) {
            $rows .= '<tr><td style="padding:8px;border-bottom:1px solid #e5e7eb">' . e((string)($item['earned_at'] ?? '')) . '</td>'
                . '<td style="padding:8px;border-bottom:1px solid #e5e7eb">' . e((string)($item['company_name'] ?? '')) . '</td>'
                . '<td style="padding:8px;border-bottom:1px solid #e5e7eb;text-align:right">ZMW ' . number_format((float)($item['gross_amount'] ?? 0), 2) . '</td>'
                . '<td style="padding:8px;border-bottom:1px solid #e5e7eb;text-align:right">ZMW ' . number_format((float)($item['tax_amount'] ?? 0), 2) . '</td>'
                . '<td style="padding:8px;border-bottom:1px solid #e5e7eb;text-align:right">ZMW ' . number_format((float)($item['net_amount'] ?? 0), 2) . '</td></tr>';
        }

        return '<div style="font-family:Arial,sans-serif;color:#111827;line-height:1.6">'
            . '<h2>Affiliate Payout Statement</h2>'
            . '<p><strong>Reference:</strong> ' . e((string)$batch['payout_reference']) . '<br>'
            . '<strong>Affiliate:</strong> ' . e((string)$batch['affiliate_name']) . '<br>'
            . '<strong>Status:</strong> ' . e((string)$batch['status']) . '</p>'
            . '<table style="border-collapse:collapse;width:100%;max-width:760px"><thead><tr><th>Date</th><th>Company</th><th>Gross</th><th>Tax</th><th>Net</th></tr></thead><tbody>' . $rows . '</tbody></table>'
            . '<p><strong>Gross:</strong> ZMW ' . number_format((float)$batch['gross_amount'], 2)
            . '<br><strong>Tax:</strong> ZMW ' . number_format((float)$batch['tax_amount'], 2)
            . '<br><strong>Net:</strong> ZMW ' . number_format((float)$batch['net_amount'], 2) . '</p>'
            . '<p>Regards,<br>' . e(app_vendor_name()) . '</p></div>';
    }

    private function exportAffiliateStatement(array $statement, string $format): void
    {
        $filename = 'affiliate-statement-' . date('Ymd-His') . ($format === 'xls' ? '.xls' : '.csv');
        header('Content-Type: ' . ($format === 'xls' ? 'application/vnd.ms-excel' : 'text/csv') . '; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Date', 'Company', 'Invoice', 'Payment', 'Rate', 'Commission', 'Status']);
        foreach ($statement['items'] as $item) {
            fputcsv($out, [
                (string) ($item['earned_at'] ?? ''),
                (string) ($item['company_name'] ?? ''),
                (string) ($item['invoice_number'] ?? ''),
                number_format((float) ($item['payment_amount'] ?? 0), 2, '.', ''),
                (string) ($item['commission_rate'] ?? ''),
                number_format((float) ($item['commission_amount'] ?? 0), 2, '.', ''),
                (string) ($item['status'] ?? ''),
            ]);
        }
        fclose($out);
        exit;
    }

    private function affiliateOrFail(int $id): array
    {
        $stmt = db()->prepare('SELECT * FROM affiliates WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        if (!$row) { http_response_code(404); exit('Affiliate not found.'); }

        return $row;
    }

    private function generateAffiliateCode(string $name): string
    {
        $base = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $name) ?: 'AFF', 0, 4));
        do {
            $code = $base . random_int(1000, 9999);
            $stmt = db()->prepare('SELECT COUNT(*) FROM affiliates WHERE affiliate_code = :code');
            $stmt->execute(['code' => $code]);
        } while ((int) $stmt->fetchColumn() > 0);

        return $code;
    }

    private function allowedValue(string $value, array $allowed, string $fallback): string
    {
        return in_array($value, $allowed, true) ? $value : $fallback;
    }

    private function ensureAffiliateSchema(): void
    {
        $this->createAffiliateTablesIfMissing();
        (new Affiliate())->ensureSchema();

        $affiliateColumns = [
            'password_hash' => 'ALTER TABLE affiliates ADD COLUMN password_hash VARCHAR(255) NULL AFTER address',
            'nrc_number' => 'ALTER TABLE affiliates ADD COLUMN nrc_number VARCHAR(80) NULL AFTER phone',
            'tpin' => 'ALTER TABLE affiliates ADD COLUMN tpin VARCHAR(80) NULL AFTER nrc_number',
            'address' => 'ALTER TABLE affiliates ADD COLUMN address TEXT NULL AFTER tpin',
            'must_change_password' => 'ALTER TABLE affiliates ADD COLUMN must_change_password TINYINT(1) NOT NULL DEFAULT 1 AFTER password_hash',
            'bank_name' => 'ALTER TABLE affiliates ADD COLUMN bank_name VARCHAR(160) NULL AFTER payout_details',
            'bank_account_name' => 'ALTER TABLE affiliates ADD COLUMN bank_account_name VARCHAR(160) NULL AFTER bank_name',
            'bank_account_number' => 'ALTER TABLE affiliates ADD COLUMN bank_account_number VARCHAR(80) NULL AFTER bank_account_name',
            'mobile_money_number' => 'ALTER TABLE affiliates ADD COLUMN mobile_money_number VARCHAR(80) NULL AFTER bank_account_number',
        ];

        foreach ($affiliateColumns as $column => $sql) {
            if (!$this->columnExists('affiliates', $column)) {
                db()->exec($sql);
            }
        }

        $referralColumns = [
            'referral_status' => "ALTER TABLE affiliate_referrals ADD COLUMN referral_status ENUM('Prospect','Trial','Active','Suspended','Cancelled') NOT NULL DEFAULT 'Active' AFTER company_id",
            'commission_rate' => 'ALTER TABLE affiliate_referrals ADD COLUMN commission_rate DECIMAL(5,2) NULL AFTER referral_status',
            'referred_at' => 'ALTER TABLE affiliate_referrals ADD COLUMN referred_at DATE NULL AFTER commission_rate',
        ];

        foreach ($referralColumns as $column => $sql) {
            if (!$this->columnExists('affiliate_referrals', $column)) {
                db()->exec($sql);
            }
        }

        if ($this->columnExists('affiliate_referrals', 'referral_date')) {
            db()->exec("UPDATE affiliate_referrals SET referred_at = COALESCE(referred_at, referral_date) WHERE referred_at IS NULL");
        }
        db()->exec("UPDATE affiliate_referrals SET referred_at = COALESCE(referred_at, CURDATE()) WHERE referred_at IS NULL");

        $commissionColumns = [
            'referral_id' => 'ALTER TABLE affiliate_commissions ADD COLUMN referral_id BIGINT UNSIGNED NULL',
            'payment_amount' => 'ALTER TABLE affiliate_commissions ADD COLUMN payment_amount DECIMAL(14,2) NOT NULL DEFAULT 0.00',
            'currency' => "ALTER TABLE affiliate_commissions ADD COLUMN currency VARCHAR(10) NOT NULL DEFAULT 'ZMW'",
            'earned_at' => 'ALTER TABLE affiliate_commissions ADD COLUMN earned_at DATE NULL',
            'paid_at' => 'ALTER TABLE affiliate_commissions ADD COLUMN paid_at DATE NULL',
            'payout_reference' => 'ALTER TABLE affiliate_commissions ADD COLUMN payout_reference VARCHAR(120) NULL',
        ];

        foreach ($commissionColumns as $column => $sql) {
            if (!$this->columnExists('affiliate_commissions', $column)) {
                db()->exec($sql);
            }
        }

        if ($this->columnExists('affiliate_commissions', 'created_at')) {
            db()->exec("UPDATE affiliate_commissions SET earned_at = COALESCE(earned_at, DATE(created_at)) WHERE earned_at IS NULL");
        }
        db()->exec("UPDATE affiliate_commissions SET earned_at = COALESCE(earned_at, CURDATE()) WHERE earned_at IS NULL");

        $documentColumns = [
            'file_name' => 'ALTER TABLE affiliate_documents ADD COLUMN file_name VARCHAR(255) NULL AFTER document_type',
            'notes' => 'ALTER TABLE affiliate_documents ADD COLUMN notes TEXT NULL AFTER mime_type',
            'created_at' => 'ALTER TABLE affiliate_documents ADD COLUMN created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP AFTER uploaded_by',
        ];

        foreach ($documentColumns as $column => $sql) {
            if (!$this->columnExists('affiliate_documents', $column)) {
                db()->exec($sql);
            }
        }

        if ($this->columnExists('affiliate_documents', 'original_name')) {
            db()->exec("UPDATE affiliate_documents SET file_name = COALESCE(file_name, original_name) WHERE file_name IS NULL OR file_name = ''");
        }
        if ($this->columnExists('affiliate_documents', 'uploaded_at')) {
            db()->exec("UPDATE affiliate_documents SET created_at = COALESCE(created_at, uploaded_at) WHERE created_at IS NULL");
        }
    }

    private function createAffiliateTablesIfMissing(): void
    {
        db()->exec(
            "CREATE TABLE IF NOT EXISTS affiliates (
              id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
              affiliate_code VARCHAR(30) NOT NULL,
              full_name VARCHAR(160) NOT NULL,
              email VARCHAR(190) NOT NULL,
              phone VARCHAR(60) NULL,
              password_hash VARCHAR(255) NOT NULL,
              must_change_password TINYINT(1) NOT NULL DEFAULT 1,
              commission_rate DECIMAL(5,2) NOT NULL DEFAULT 5.00,
              payout_method VARCHAR(80) NULL,
              payout_details TEXT NULL,
              is_active TINYINT(1) NOT NULL DEFAULT 1,
              last_login_at DATETIME NULL,
              created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
              updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              UNIQUE KEY uq_affiliates_code (affiliate_code),
              UNIQUE KEY uq_affiliates_email (email),
              KEY idx_affiliates_active (is_active)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        db()->exec(
            "CREATE TABLE IF NOT EXISTS affiliate_referrals (
              id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
              affiliate_id BIGINT UNSIGNED NOT NULL,
              company_id BIGINT UNSIGNED NOT NULL,
              referral_status ENUM('Prospect','Trial','Active','Suspended','Cancelled') NOT NULL DEFAULT 'Active',
              commission_rate DECIMAL(5,2) NULL,
              referred_at DATE NOT NULL,
              notes TEXT NULL,
              created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
              updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              UNIQUE KEY uq_affiliate_referrals_company (company_id),
              KEY idx_affiliate_referrals_affiliate (affiliate_id),
              KEY idx_affiliate_referrals_status (referral_status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        db()->exec(
            "CREATE TABLE IF NOT EXISTS affiliate_commissions (
              id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
              affiliate_id BIGINT UNSIGNED NOT NULL,
              referral_id BIGINT UNSIGNED NOT NULL,
              company_id BIGINT UNSIGNED NOT NULL,
              invoice_id BIGINT UNSIGNED NOT NULL,
              payment_id BIGINT UNSIGNED NOT NULL,
              payment_amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
              commission_rate DECIMAL(5,2) NOT NULL DEFAULT 5.00,
              commission_amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
              currency VARCHAR(10) NOT NULL DEFAULT 'ZMW',
              earned_at DATE NOT NULL,
              status ENUM('Pending','Approved','Paid','Reversed') NOT NULL DEFAULT 'Pending',
              paid_at DATE NULL,
              payout_reference VARCHAR(120) NULL,
              notes TEXT NULL,
              created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
              updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              UNIQUE KEY uq_affiliate_commissions_payment (payment_id),
              KEY idx_affiliate_commissions_affiliate_status (affiliate_id, status),
              KEY idx_affiliate_commissions_company (company_id),
              KEY idx_affiliate_commissions_invoice (invoice_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        db()->exec(
            "CREATE TABLE IF NOT EXISTS affiliate_documents (
              id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
              affiliate_id BIGINT UNSIGNED NOT NULL,
              document_type VARCHAR(80) NOT NULL DEFAULT 'Other',
              file_name VARCHAR(255) NOT NULL,
              file_path VARCHAR(255) NOT NULL,
              file_size BIGINT UNSIGNED NULL,
              mime_type VARCHAR(120) NULL,
              notes TEXT NULL,
              uploaded_by BIGINT UNSIGNED NULL,
              created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
              KEY idx_affiliate_documents_affiliate (affiliate_id),
              KEY idx_affiliate_documents_type (document_type)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }

    private function columnExists(string $table, string $column): bool
    {
        $stmt = db()->prepare(
            'SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table_name AND COLUMN_NAME = :column_name'
        );
        $stmt->execute(['table_name' => $table, 'column_name' => $column]);

        return (int) $stmt->fetchColumn() > 0;
    }
}
