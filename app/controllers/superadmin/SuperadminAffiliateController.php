<?php

declare(strict_types=1);

class SuperadminAffiliateController extends Controller
{
    public function index(): void
    {
        redirect('superadmin/invoice/affiliates');
    }

    public function create(): void
    {
        redirect('superadmin/invoice/affiliateCreate');
    }

    public function store(): void
    {
        require_superadmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('superadmin/affiliate/index'); }
        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid token.');
            redirect('superadmin/affiliate/create');
        }

        $name = trim((string) $this->input('full_name', ''));
        $email = strtolower(trim((string) $this->input('email', '')));
        $phone = trim((string) $this->input('phone', ''));
        $password = (string) $this->input('password', '');
        $rate = max(0.0, min(100.0, (float) $this->input('commission_rate', '5')));

        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8) {
            Session::flash('error', 'Name, valid email, and password of at least 8 characters are required.');
            redirect('superadmin/affiliate/create');
        }

        try {
            $stmt = db()->prepare(
                "INSERT INTO affiliates (affiliate_code, full_name, email, phone, password_hash, must_change_password, commission_rate, payout_method, payout_details, is_active)
                 VALUES (:code, :name, :email, :phone, :hash, 1, :rate, :method, :details, 1)"
            );
            $stmt->execute([
                'code' => $this->generateCode($name),
                'name' => $name,
                'email' => $email,
                'phone' => $phone ?: null,
                'hash' => password_hash($password, PASSWORD_DEFAULT),
                'rate' => $rate,
                'method' => trim((string) $this->input('payout_method', '')) ?: null,
                'details' => trim((string) $this->input('payout_details', '')) ?: null,
            ]);
            AuditLog::recordPlatform('affiliate_created', 'Created affiliate ' . $email, 'Affiliate', (int) db()->lastInsertId());
            Session::flash('success', 'Affiliate created.');
            redirect('superadmin/affiliate/index');
        } catch (Throwable $e) {
            Session::flash('error', 'Affiliate could not be created: ' . $e->getMessage());
            redirect('superadmin/affiliate/create');
        }
    }

    public function view(string $id = ''): void
    {
        require_superadmin();
        $affiliateId = (int) $id;
        $affiliate = $this->affiliate($affiliateId);
        $model = new Affiliate();

        $assigned = $model->companies($affiliateId);
        $assignedIds = array_map(static fn(array $row): int => (int) $row['company_id'], $assigned);
        $companies = db()->query('SELECT id, name, email, account_status FROM companies ORDER BY name ASC')->fetchAll();

        $this->renderSuperAdmin('superadmin/affiliates/view', [
            'title' => 'Affiliate: ' . (string) $affiliate['full_name'],
            'affiliate' => $affiliate,
            'dashboard' => $model->dashboard($affiliateId),
            'assignedCompanyIds' => $assignedIds,
            'companies' => $companies,
            'csrf' => Session::csrfToken(),
            'flash' => Session::flash('success'),
            'flashErr' => Session::flash('error'),
        ]);
    }

    public function assignCompany(string $id = ''): void
    {
        require_superadmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('superadmin/affiliate/view/' . (int) $id); }
        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid token.');
            redirect('superadmin/affiliate/view/' . (int) $id);
        }

        $affiliate = $this->affiliate((int) $id);
        $companyId = (int) $this->input('company_id', 0);
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
                'company_id' => $companyId,
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

        redirect('superadmin/affiliate/view/' . (int) $affiliate['id']);
    }

    public function markPaid(string $id = ''): void
    {
        require_superadmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('superadmin/affiliate/index'); }
        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid token.');
            redirect('superadmin/affiliate/view/' . (int) $id);
        }

        $affiliate = $this->affiliate((int) $id);
        $reference = trim((string) $this->input('payout_reference', ''));
        db()->prepare(
            "UPDATE affiliate_commissions
             SET status = 'Paid', paid_at = CURDATE(), payout_reference = :reference
             WHERE affiliate_id = :id AND status IN ('Pending','Approved')"
        )->execute(['reference' => $reference ?: null, 'id' => (int) $affiliate['id']]);

        AuditLog::recordPlatform('affiliate_commission_paid', 'Marked affiliate commissions as paid.', 'Affiliate', (int) $affiliate['id']);
        Session::flash('success', 'Outstanding affiliate commissions marked as paid.');
        redirect('superadmin/affiliate/view/' . (int) $affiliate['id']);
    }

    public function sync(): void
    {
        require_superadmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('superadmin/affiliate/index'); }
        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid token.');
            redirect('superadmin/affiliate/index');
        }

        $count = (new Affiliate())->syncCommissions();
        AuditLog::recordPlatform('affiliate_commission_sync', "Synced affiliate commissions from paid receipts. Created {$count} record(s).", 'Affiliate', null);
        Session::flash('success', "Commission sync complete. {$count} commission record(s) created.");
        redirect('superadmin/affiliate/index');
    }

    private function affiliate(int $id): array
    {
        $stmt = db()->prepare('SELECT * FROM affiliates WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        if (!$row) { http_response_code(404); exit('Affiliate not found.'); }
        return $row;
    }

    private function generateCode(string $name): string
    {
        $base = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $name) ?: 'AFF', 0, 4));
        do {
            $code = $base . random_int(1000, 9999);
            $stmt = db()->prepare('SELECT COUNT(*) FROM affiliates WHERE affiliate_code = :code');
            $stmt->execute(['code' => $code]);
        } while ((int) $stmt->fetchColumn() > 0);

        return $code;
    }
}
