<?php

declare(strict_types=1);

class Affiliate extends Model
{
    protected string $table = 'affiliates';

    public function tableReady(): bool
    {
        return $this->tableExists('affiliates')
            && $this->tableExists('affiliate_referrals')
            && $this->tableExists('affiliate_commissions')
            && $this->tableExists('affiliate_documents')
            && $this->tableExists('affiliate_leads');
    }

    public function ensureSchema(): void
    {
        $this->db->exec(
            "CREATE TABLE IF NOT EXISTS affiliate_documents (
              id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
              affiliate_id BIGINT UNSIGNED NOT NULL,
              document_type VARCHAR(80) NOT NULL DEFAULT 'Other',
              file_name VARCHAR(255) NULL,
              file_path VARCHAR(255) NOT NULL,
              file_size BIGINT UNSIGNED NULL,
              mime_type VARCHAR(120) NULL,
              notes TEXT NULL,
              uploaded_by BIGINT UNSIGNED NULL,
              created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
              KEY idx_affiliate_documents_affiliate (affiliate_id),
              KEY idx_affiliate_documents_type (document_type)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $this->db->exec(
            "CREATE TABLE IF NOT EXISTS affiliate_leads (
              id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
              affiliate_id BIGINT UNSIGNED NOT NULL,
              company_name VARCHAR(190) NOT NULL,
              contact_person VARCHAR(160) NULL,
              contact_email VARCHAR(190) NULL,
              contact_phone VARCHAR(80) NULL,
              industry VARCHAR(120) NULL,
              employee_count INT NULL,
              estimated_value DECIMAL(14,2) NOT NULL DEFAULT 0.00,
              stage ENUM('New','Contacted','Demo Scheduled','Negotiating','Won','Lost') NOT NULL DEFAULT 'New',
              source VARCHAR(120) NULL,
              notes TEXT NULL,
              next_follow_up DATE NULL,
              converted_company_id BIGINT UNSIGNED NULL,
              created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
              updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              KEY idx_affiliate_leads_affiliate (affiliate_id),
              KEY idx_affiliate_leads_stage (stage)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $columns = [
            'address' => 'ALTER TABLE affiliates ADD COLUMN address TEXT NULL',
            'affiliate_type' => "ALTER TABLE affiliates ADD COLUMN affiliate_type ENUM('Individual','Company','Consultant','Reseller','Agency') NOT NULL DEFAULT 'Individual'",
            'trading_name' => 'ALTER TABLE affiliates ADD COLUMN trading_name VARCHAR(190) NULL',
            'alternate_email' => 'ALTER TABLE affiliates ADD COLUMN alternate_email VARCHAR(190) NULL',
            'alternate_phone' => 'ALTER TABLE affiliates ADD COLUMN alternate_phone VARCHAR(80) NULL',
            'city' => 'ALTER TABLE affiliates ADD COLUMN city VARCHAR(120) NULL',
            'province' => 'ALTER TABLE affiliates ADD COLUMN province VARCHAR(120) NULL',
            'date_of_birth' => 'ALTER TABLE affiliates ADD COLUMN date_of_birth DATE NULL',
            'mobile_money_number' => 'ALTER TABLE affiliates ADD COLUMN mobile_money_number VARCHAR(80) NULL',
            'kyc_status' => "ALTER TABLE affiliates ADD COLUMN kyc_status ENUM('Draft','Pending Review','Approved','Rejected') NOT NULL DEFAULT 'Draft'",
            'kyc_reviewed_by' => 'ALTER TABLE affiliates ADD COLUMN kyc_reviewed_by BIGINT UNSIGNED NULL',
            'kyc_reviewed_at' => 'ALTER TABLE affiliates ADD COLUMN kyc_reviewed_at DATETIME NULL',
            'kyc_rejection_reason' => 'ALTER TABLE affiliates ADD COLUMN kyc_rejection_reason TEXT NULL',
            'commission_basis' => "ALTER TABLE affiliates ADD COLUMN commission_basis ENUM('Paid Amount','Invoice Amount','Net Amount') NOT NULL DEFAULT 'Paid Amount'",
            'commission_duration' => "ALTER TABLE affiliates ADD COLUMN commission_duration ENUM('First Year','Lifetime','Fixed Months') NOT NULL DEFAULT 'First Year'",
            'commission_months' => 'ALTER TABLE affiliates ADD COLUMN commission_months INT NULL',
            'one_off_bonus' => 'ALTER TABLE affiliates ADD COLUMN one_off_bonus DECIMAL(14,2) NOT NULL DEFAULT 0.00',
            'payout_tax_rate' => 'ALTER TABLE affiliates ADD COLUMN payout_tax_rate DECIMAL(5,2) NOT NULL DEFAULT 0.00',
        ];

        foreach ($columns as $column => $sql) {
            if (!$this->columnExists('affiliates', $column)) {
                $this->db->exec($sql);
            }
        }

        $documentColumns = [
            'file_name' => 'ALTER TABLE affiliate_documents ADD COLUMN file_name VARCHAR(255) NULL AFTER document_type',
            'notes' => 'ALTER TABLE affiliate_documents ADD COLUMN notes TEXT NULL AFTER mime_type',
            'created_at' => 'ALTER TABLE affiliate_documents ADD COLUMN created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP AFTER uploaded_by',
        ];
        foreach ($documentColumns as $column => $sql) {
            if (!$this->columnExists('affiliate_documents', $column)) {
                $this->db->exec($sql);
            }
        }

        if ($this->columnExists('affiliate_documents', 'original_name')) {
            $this->db->exec('ALTER TABLE affiliate_documents MODIFY COLUMN original_name VARCHAR(255) NULL');
            $this->db->exec("UPDATE affiliate_documents SET file_name = COALESCE(file_name, original_name) WHERE file_name IS NULL OR file_name = ''");
        }
        if ($this->columnExists('affiliate_documents', 'stored_name')) {
            $this->db->exec('ALTER TABLE affiliate_documents MODIFY COLUMN stored_name VARCHAR(255) NULL');
        }
    }

    public function listAll(): array
    {
        if (!$this->tableReady()) {
            return [];
        }

        return $this->db->query(
            "SELECT a.*,
                    COALESCE(ref.company_count, 0) AS company_count,
                    COALESCE(doc.document_count, 0) AS document_count,
                    COALESCE(com.lifetime_commission, 0) AS lifetime_commission,
                    COALESCE(com.paid_commission, 0) AS paid_commission,
                    COALESCE(com.pending_commission, 0) AS pending_commission
             FROM affiliates a
             LEFT JOIN (
                SELECT affiliate_id, COUNT(DISTINCT company_id) AS company_count
                FROM affiliate_referrals
                GROUP BY affiliate_id
             ) ref ON ref.affiliate_id = a.id
             LEFT JOIN (
                SELECT affiliate_id, COUNT(*) AS document_count
                FROM affiliate_documents
                GROUP BY affiliate_id
             ) doc ON doc.affiliate_id = a.id
             LEFT JOIN (
                SELECT affiliate_id,
                       SUM(CASE WHEN status <> 'Reversed' THEN commission_amount ELSE 0 END) AS lifetime_commission,
                       SUM(CASE WHEN status = 'Paid' THEN commission_amount ELSE 0 END) AS paid_commission,
                       SUM(CASE WHEN status IN ('Pending','Approved') THEN commission_amount ELSE 0 END) AS pending_commission
                FROM affiliate_commissions
                GROUP BY affiliate_id
             ) com ON com.affiliate_id = a.id
             ORDER BY a.full_name ASC"
        )->fetchAll();
    }

    public function findByEmail(string $email): ?array
    {
        if (!$this->tableExists('affiliates')) {
            return null;
        }

        $stmt = $this->db->prepare('SELECT * FROM affiliates WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => strtolower(trim($email))]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function dashboard(int $affiliateId): array
    {
        $summary = [
            'company_count' => 0,
            'active_companies' => 0,
            'lead_count' => 0,
            'open_leads' => 0,
            'won_leads' => 0,
            'lifetime_commission' => 0.0,
            'pending_commission' => 0.0,
            'approved_commission' => 0.0,
            'paid_commission' => 0.0,
            'current_year_commission' => 0.0,
        ];

        if (!$this->tableReady()) {
            return ['summary' => $summary, 'companies' => [], 'commissions' => [], 'monthly' => []];
        }

        $stmt = $this->db->prepare(
            "SELECT COUNT(DISTINCT ar.company_id) AS company_count,
                    SUM(CASE WHEN ar.referral_status = 'Active' THEN 1 ELSE 0 END) AS active_companies
             FROM affiliate_referrals ar
             WHERE ar.affiliate_id = :id"
        );
        $stmt->execute(['id' => $affiliateId]);
        $row = $stmt->fetch() ?: [];
        $summary['company_count'] = (int) ($row['company_count'] ?? 0);
        $summary['active_companies'] = (int) ($row['active_companies'] ?? 0);

        if ($this->tableExists('affiliate_leads')) {
            $stmt = $this->db->prepare(
                "SELECT COUNT(*) AS lead_count,
                        SUM(CASE WHEN stage NOT IN ('Won','Lost') THEN 1 ELSE 0 END) AS open_leads,
                        SUM(CASE WHEN stage = 'Won' THEN 1 ELSE 0 END) AS won_leads
                 FROM affiliate_leads
                 WHERE affiliate_id = :id"
            );
            $stmt->execute(['id' => $affiliateId]);
            $row = $stmt->fetch() ?: [];
            $summary['lead_count'] = (int) ($row['lead_count'] ?? 0);
            $summary['open_leads'] = (int) ($row['open_leads'] ?? 0);
            $summary['won_leads'] = (int) ($row['won_leads'] ?? 0);
        }

        $stmt = $this->db->prepare(
            "SELECT
                COALESCE(SUM(CASE WHEN status <> 'Reversed' THEN commission_amount ELSE 0 END), 0) AS lifetime_commission,
                COALESCE(SUM(CASE WHEN status = 'Pending' THEN commission_amount ELSE 0 END), 0) AS pending_commission,
                COALESCE(SUM(CASE WHEN status = 'Approved' THEN commission_amount ELSE 0 END), 0) AS approved_commission,
                COALESCE(SUM(CASE WHEN status = 'Paid' THEN commission_amount ELSE 0 END), 0) AS paid_commission,
                COALESCE(SUM(CASE WHEN status <> 'Reversed' AND YEAR(earned_at) = YEAR(CURDATE()) THEN commission_amount ELSE 0 END), 0) AS current_year_commission
             FROM affiliate_commissions
             WHERE affiliate_id = :id"
        );
        $stmt->execute(['id' => $affiliateId]);
        $row = $stmt->fetch() ?: [];
        foreach (['lifetime_commission', 'pending_commission', 'approved_commission', 'paid_commission', 'current_year_commission'] as $key) {
            $summary[$key] = (float) ($row[$key] ?? 0);
        }

        return [
            'summary' => $summary,
            'companies' => $this->companies($affiliateId),
            'commissions' => $this->commissions($affiliateId, 25),
            'monthly' => $this->monthlyTrend($affiliateId),
            'leads' => $this->leads($affiliateId, 8),
        ];
    }

    public function find(int $affiliateId): ?array
    {
        if (!$this->tableExists('affiliates')) {
            return null;
        }

        $stmt = $this->db->prepare('SELECT * FROM affiliates WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $affiliateId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function companies(int $affiliateId): array
    {
        if (!$this->tableReady()) {
            return [];
        }

        $stmt = $this->db->prepare(
            "SELECT ar.*, c.name AS company_name, c.email AS company_email, c.account_status,
                    COALESCE(SUM(ac.commission_amount), 0) AS total_commission,
                    COALESCE(SUM(CASE WHEN ac.status = 'Paid' THEN ac.commission_amount ELSE 0 END), 0) AS paid_commission,
                    COALESCE(SUM(CASE WHEN ac.status IN ('Pending','Approved') THEN ac.commission_amount ELSE 0 END), 0) AS unpaid_commission
             FROM affiliate_referrals ar
             JOIN companies c ON c.id = ar.company_id
             LEFT JOIN affiliate_commissions ac ON ac.referral_id = ar.id AND ac.status <> 'Reversed'
             WHERE ar.affiliate_id = :id
             GROUP BY ar.id
             ORDER BY ar.referred_at DESC, c.name ASC"
        );
        $stmt->execute(['id' => $affiliateId]);

        return $stmt->fetchAll();
    }

    public function commissions(int $affiliateId, int $limit = 100): array
    {
        if (!$this->tableReady()) {
            return [];
        }

        $stmt = $this->db->prepare(
            "SELECT ac.*, c.name AS company_name, si.invoice_number, sip.payment_reference
             FROM affiliate_commissions ac
             JOIN companies c ON c.id = ac.company_id
             JOIN subscription_invoices si ON si.id = ac.invoice_id
             JOIN subscription_invoice_payments sip ON sip.id = ac.payment_id
             WHERE ac.affiliate_id = :id
             ORDER BY ac.earned_at DESC, ac.id DESC
             LIMIT {$limit}"
        );
        $stmt->execute(['id' => $affiliateId]);

        return $stmt->fetchAll();
    }

    public function documents(int $affiliateId): array
    {
        if (!$this->tableExists('affiliate_documents')) {
            return [];
        }

        $stmt = $this->db->prepare(
            'SELECT * FROM affiliate_documents WHERE affiliate_id = :id ORDER BY created_at DESC, id DESC'
        );
        $stmt->execute(['id' => $affiliateId]);

        return $stmt->fetchAll();
    }

    public function leads(int $affiliateId, int $limit = 200): array
    {
        if (!$this->tableExists('affiliate_leads')) {
            return [];
        }

        $limit = max(1, min(500, $limit));
        $stmt = $this->db->prepare(
            "SELECT al.*, c.name AS converted_company_name
             FROM affiliate_leads al
             LEFT JOIN companies c ON c.id = al.converted_company_id
             WHERE al.affiliate_id = :id
             ORDER BY al.updated_at DESC, al.id DESC
             LIMIT {$limit}"
        );
        $stmt->execute(['id' => $affiliateId]);

        return $stmt->fetchAll();
    }

    public function createLead(int $affiliateId, array $data): int
    {
        $companyName = trim((string) ($data['company_name'] ?? ''));
        $email = trim((string) ($data['contact_email'] ?? ''));
        $phone = trim((string) ($data['contact_phone'] ?? ''));

        $dupe = $this->db->prepare(
            "SELECT id FROM affiliate_leads
             WHERE affiliate_id = :affiliate_id
               AND (LOWER(company_name) = LOWER(:company_name)
                    OR (:email <> '' AND contact_email = :email)
                    OR (:phone <> '' AND contact_phone = :phone))
             LIMIT 1"
        );
        $dupe->execute([
            'affiliate_id' => $affiliateId,
            'company_name' => $companyName,
            'email' => $email,
            'phone' => $phone,
        ]);
        if ($dupe->fetch()) {
            throw new RuntimeException('This lead already exists in your pipeline.');
        }

        $stmt = $this->db->prepare(
            "INSERT INTO affiliate_leads
             (affiliate_id, company_name, contact_person, contact_email, contact_phone, industry, employee_count, estimated_value, source, notes, next_follow_up)
             VALUES (:affiliate_id, :company_name, :contact_person, :contact_email, :contact_phone, :industry, :employee_count, :estimated_value, :source, :notes, :next_follow_up)"
        );
        $stmt->execute([
            'affiliate_id' => $affiliateId,
            'company_name' => $companyName,
            'contact_person' => trim((string) ($data['contact_person'] ?? '')) ?: null,
            'contact_email' => $email ?: null,
            'contact_phone' => $phone ?: null,
            'industry' => trim((string) ($data['industry'] ?? '')) ?: null,
            'employee_count' => (int) ($data['employee_count'] ?? 0) ?: null,
            'estimated_value' => max(0, (float) ($data['estimated_value'] ?? 0)),
            'source' => trim((string) ($data['source'] ?? '')) ?: null,
            'notes' => trim((string) ($data['notes'] ?? '')) ?: null,
            'next_follow_up' => trim((string) ($data['next_follow_up'] ?? '')) ?: null,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function updateLeadStage(int $leadId, string $stage, ?int $companyId = null, ?string $notes = null): void
    {
        $allowed = ['New','Contacted','Demo Scheduled','Negotiating','Won','Lost'];
        if (!in_array($stage, $allowed, true)) {
            $stage = 'New';
        }

        $this->db->prepare(
            "UPDATE affiliate_leads
             SET stage = :stage,
                 converted_company_id = :company_id,
                 notes = COALESCE(:notes, notes)
             WHERE id = :id"
        )->execute([
            'stage' => $stage,
            'company_id' => $companyId ?: null,
            'notes' => $notes,
            'id' => $leadId,
        ]);
    }

    public function document(int $documentId): ?array
    {
        if (!$this->tableExists('affiliate_documents')) {
            return null;
        }

        $stmt = $this->db->prepare('SELECT * FROM affiliate_documents WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $documentId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function monthlyTrend(int $affiliateId): array
    {
        if (!$this->tableReady()) {
            return [];
        }

        $stmt = $this->db->prepare(
            "SELECT DATE_FORMAT(earned_at, '%Y-%m') AS period,
                    COALESCE(SUM(commission_amount), 0) AS amount
             FROM affiliate_commissions
             WHERE affiliate_id = :id AND status <> 'Reversed'
             GROUP BY DATE_FORMAT(earned_at, '%Y-%m')
             ORDER BY period DESC
             LIMIT 12"
        );
        $stmt->execute(['id' => $affiliateId]);

        return array_reverse($stmt->fetchAll());
    }

    public function createCommissionForPayment(int $paymentId): ?int
    {
        if (!$this->tableReady()) {
            return null;
        }

        $exists = $this->db->prepare('SELECT id FROM affiliate_commissions WHERE payment_id = :id LIMIT 1');
        $exists->execute(['id' => $paymentId]);
        if ((int) ($exists->fetchColumn() ?: 0) > 0) {
            return null;
        }

        $stmt = $this->db->prepare(
            "SELECT sip.id AS payment_id, sip.amount, sip.paid_at,
                    si.id AS invoice_id, si.company_id, si.currency,
                    ar.id AS referral_id, ar.affiliate_id,
                    COALESCE(ar.commission_rate, a.commission_rate, 5.00) AS commission_rate
             FROM subscription_invoice_payments sip
             JOIN subscription_invoices si ON si.id = sip.invoice_id
             JOIN affiliate_referrals ar ON ar.company_id = si.company_id
             JOIN affiliates a ON a.id = ar.affiliate_id
             WHERE sip.id = :id AND a.is_active = 1
             LIMIT 1"
        );
        $stmt->execute(['id' => $paymentId]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }

        $paymentAmount = (float) $row['amount'];
        $rate = (float) $row['commission_rate'];
        $commission = round($paymentAmount * ($rate / 100), 2);
        if ($commission <= 0) {
            return null;
        }

        $insert = $this->db->prepare(
            "INSERT INTO affiliate_commissions
             (affiliate_id, referral_id, company_id, invoice_id, payment_id, payment_amount, commission_rate, commission_amount, currency, earned_at, status)
             VALUES (:affiliate_id, :referral_id, :company_id, :invoice_id, :payment_id, :payment_amount, :commission_rate, :commission_amount, :currency, :earned_at, 'Pending')"
        );
        $insert->execute([
            'affiliate_id' => (int) $row['affiliate_id'],
            'referral_id' => (int) $row['referral_id'],
            'company_id' => (int) $row['company_id'],
            'invoice_id' => (int) $row['invoice_id'],
            'payment_id' => $paymentId,
            'payment_amount' => $paymentAmount,
            'commission_rate' => $rate,
            'commission_amount' => $commission,
            'currency' => (string) ($row['currency'] ?? 'ZMW'),
            'earned_at' => (string) ($row['paid_at'] ?? date('Y-m-d')),
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function syncCommissions(): int
    {
        if (!$this->tableReady()) {
            return 0;
        }

        $payments = $this->db->query(
            "SELECT sip.id
             FROM subscription_invoice_payments sip
             JOIN subscription_invoices si ON si.id = sip.invoice_id
             JOIN affiliate_referrals ar ON ar.company_id = si.company_id
             LEFT JOIN affiliate_commissions ac ON ac.payment_id = sip.id
             WHERE ac.id IS NULL
             ORDER BY sip.id ASC"
        )->fetchAll();

        $count = 0;
        foreach ($payments as $payment) {
            if ($this->createCommissionForPayment((int) $payment['id']) !== null) {
                $count++;
            }
        }

        return $count;
    }

    private function tableExists(string $table): bool
    {
        static $cache = [];
        if (array_key_exists($table, $cache)) {
            return $cache[$table];
        }

        try {
            $stmt = $this->db->prepare(
                'SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table_name'
            );
            $stmt->execute(['table_name' => $table]);
            $cache[$table] = (int) $stmt->fetchColumn() > 0;
        } catch (Throwable) {
            $cache[$table] = false;
        }

        return $cache[$table];
    }

    private function columnExists(string $table, string $column): bool
    {
        try {
            $stmt = $this->db->prepare(
                'SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table_name AND COLUMN_NAME = :column_name'
            );
            $stmt->execute(['table_name' => $table, 'column_name' => $column]);

            return (int) $stmt->fetchColumn() > 0;
        } catch (Throwable) {
            return false;
        }
    }
}
