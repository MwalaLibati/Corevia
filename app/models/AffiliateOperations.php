<?php

declare(strict_types=1);

class AffiliateOperations extends Model
{
    public function ensureSchema(): void
    {
        $this->db->exec(
            "CREATE TABLE IF NOT EXISTS payment_methods (
              id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
              code VARCHAR(40) NOT NULL UNIQUE,
              name VARCHAR(120) NOT NULL,
              is_active TINYINT(1) NOT NULL DEFAULT 1,
              sort_order INT NOT NULL DEFAULT 0,
              created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $this->db->exec(
            "CREATE TABLE IF NOT EXISTS affiliate_payout_batches (
              id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
              payout_reference VARCHAR(60) NOT NULL UNIQUE,
              affiliate_id BIGINT UNSIGNED NOT NULL,
              period_from DATE NULL,
              period_to DATE NULL,
              gross_amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
              tax_amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
              net_amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
              payment_method_id BIGINT UNSIGNED NULL,
              status ENUM('Draft','Submitted','Approved','Paid','Rejected','Voided') NOT NULL DEFAULT 'Draft',
              submitted_at DATETIME NULL,
              approved_at DATETIME NULL,
              approved_by BIGINT UNSIGNED NULL,
              paid_at DATETIME NULL,
              paid_by BIGINT UNSIGNED NULL,
              payment_reference VARCHAR(120) NULL,
              notes TEXT NULL,
              created_by BIGINT UNSIGNED NULL,
              created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
              updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              KEY idx_affiliate_payout_batches_affiliate (affiliate_id),
              KEY idx_affiliate_payout_batches_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $this->db->exec(
            "CREATE TABLE IF NOT EXISTS affiliate_payout_items (
              id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
              payout_batch_id BIGINT UNSIGNED NOT NULL,
              commission_id BIGINT UNSIGNED NOT NULL,
              gross_amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
              tax_amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
              net_amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
              created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
              UNIQUE KEY uq_affiliate_payout_item_commission (commission_id),
              KEY idx_affiliate_payout_items_batch (payout_batch_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $this->db->exec(
            "CREATE TABLE IF NOT EXISTS affiliate_agreements (
              id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
              affiliate_id BIGINT UNSIGNED NOT NULL,
              agreement_number VARCHAR(60) NOT NULL UNIQUE,
              title VARCHAR(180) NOT NULL,
              terms_html MEDIUMTEXT NOT NULL,
              status ENUM('Draft','Sent','Signed','Expired','Terminated') NOT NULL DEFAULT 'Draft',
              effective_date DATE NULL,
              expiry_date DATE NULL,
              sent_at DATETIME NULL,
              signed_at DATETIME NULL,
              signed_document_path VARCHAR(500) NULL,
              renewal_reminder_at DATE NULL,
              created_by BIGINT UNSIGNED NULL,
              created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
              updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              KEY idx_affiliate_agreements_affiliate (affiliate_id),
              KEY idx_affiliate_agreements_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $this->db->exec(
            "CREATE TABLE IF NOT EXISTS affiliate_messages (
              id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
              affiliate_id BIGINT UNSIGNED NULL,
              subject VARCHAR(180) NOT NULL,
              message TEXT NOT NULL,
              visibility ENUM('All Affiliates','Specific Affiliate','Internal Note') NOT NULL DEFAULT 'Specific Affiliate',
              is_read TINYINT(1) NOT NULL DEFAULT 0,
              created_by BIGINT UNSIGNED NULL,
              created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
              KEY idx_affiliate_messages_affiliate (affiliate_id),
              KEY idx_affiliate_messages_visibility (visibility)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $this->db->exec(
            "CREATE TABLE IF NOT EXISTS affiliate_support_tickets (
              id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
              ticket_number VARCHAR(60) NOT NULL UNIQUE,
              affiliate_id BIGINT UNSIGNED NOT NULL,
              subject VARCHAR(180) NOT NULL,
              message TEXT NOT NULL,
              status ENUM('Open','In Progress','Resolved','Closed') NOT NULL DEFAULT 'Open',
              priority ENUM('Low','Normal','High') NOT NULL DEFAULT 'Normal',
              admin_response TEXT NULL,
              resolved_at DATETIME NULL,
              created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
              updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              KEY idx_affiliate_support_affiliate (affiliate_id),
              KEY idx_affiliate_support_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $this->db->exec(
            "CREATE TABLE IF NOT EXISTS affiliate_login_history (
              id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
              affiliate_id BIGINT UNSIGNED NULL,
              email VARCHAR(190) NOT NULL,
              success TINYINT(1) NOT NULL DEFAULT 0,
              ip_address VARCHAR(64) NULL,
              user_agent VARCHAR(255) NULL,
              logged_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
              KEY idx_affiliate_login_history_affiliate (affiliate_id),
              KEY idx_affiliate_login_history_email (email)
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
              converted_at DATETIME NULL,
              converted_by BIGINT UNSIGNED NULL,
              created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
              updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              KEY idx_affiliate_leads_affiliate (affiliate_id),
              KEY idx_affiliate_leads_stage (stage)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $this->db->exec(
            "INSERT IGNORE INTO payment_methods (code, name, sort_order) VALUES
             ('BANK_TRANSFER','Bank Transfer',10),
             ('MOBILE_MONEY','Mobile Money',20),
             ('CASH','Cash',30),
             ('CHEQUE','Cheque',40),
             ('OTHER','Other',99)"
        );

        $commissionColumns = [
            'eligible_at' => 'ALTER TABLE affiliate_commissions ADD COLUMN eligible_at DATE NULL',
            'paid_at' => 'ALTER TABLE affiliate_commissions ADD COLUMN paid_at DATE NULL',
            'reversed_at' => 'ALTER TABLE affiliate_commissions ADD COLUMN reversed_at DATETIME NULL',
            'reversal_reason' => 'ALTER TABLE affiliate_commissions ADD COLUMN reversal_reason TEXT NULL',
            'adjustment_reason' => 'ALTER TABLE affiliate_commissions ADD COLUMN adjustment_reason TEXT NULL',
        ];
        foreach ($commissionColumns as $column => $sql) {
            if (!$this->columnExists('affiliate_commissions', $column)) {
                $this->db->exec($sql);
            }
        }
        $this->db->exec('UPDATE affiliate_commissions SET eligible_at = COALESCE(eligible_at, DATE_ADD(earned_at, INTERVAL 30 DAY)) WHERE eligible_at IS NULL');

        $leadColumns = [
            'converted_at' => 'ALTER TABLE affiliate_leads ADD COLUMN converted_at DATETIME NULL AFTER converted_company_id',
            'converted_by' => 'ALTER TABLE affiliate_leads ADD COLUMN converted_by BIGINT UNSIGNED NULL AFTER converted_at',
        ];
        foreach ($leadColumns as $column => $sql) {
            if ($this->tableExists('affiliate_leads') && !$this->columnExists('affiliate_leads', $column)) {
                $this->db->exec($sql);
            }
        }
    }

    public function paymentMethods(): array
    {
        $this->ensureSchema();
        return $this->db->query('SELECT * FROM payment_methods WHERE is_active = 1 ORDER BY sort_order ASC, name ASC')->fetchAll();
    }

    public function analytics(): array
    {
        $this->ensureSchema();
        return [
            'top_affiliates' => $this->db->query(
                "SELECT a.id, a.full_name, a.email, COALESCE(SUM(ac.commission_amount),0) AS revenue
                 FROM affiliates a
                 LEFT JOIN affiliate_commissions ac ON ac.affiliate_id = a.id AND ac.status <> 'Reversed'
                 GROUP BY a.id
                 ORDER BY revenue DESC, a.full_name ASC
                 LIMIT 10"
            )->fetchAll(),
            'leads_by_stage' => $this->db->query(
                "SELECT stage, COUNT(*) AS total FROM affiliate_leads GROUP BY stage ORDER BY FIELD(stage,'New','Contacted','Demo Scheduled','Negotiating','Won','Lost')"
            )->fetchAll(),
            'commission_liability' => (float) $this->db->query(
                "SELECT COALESCE(SUM(commission_amount),0) FROM affiliate_commissions WHERE status IN ('Pending','Approved')"
            )->fetchColumn(),
            'payouts_due_this_month' => (float) $this->db->query(
                "SELECT COALESCE(SUM(commission_amount),0) FROM affiliate_commissions WHERE status IN ('Pending','Approved') AND eligible_at <= LAST_DAY(CURDATE())"
            )->fetchColumn(),
            'monthly_acquisition' => $this->db->query(
                "SELECT DATE_FORMAT(created_at, '%Y-%m') AS period, COUNT(*) AS total
                 FROM affiliates
                 GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                 ORDER BY period DESC
                 LIMIT 12"
            )->fetchAll(),
        ];
    }

    public function statement(int $affiliateId): array
    {
        $this->ensureSchema();
        $rows = $this->db->prepare(
            "SELECT ac.*, c.name AS company_name, si.invoice_number
             FROM affiliate_commissions ac
             LEFT JOIN companies c ON c.id = ac.company_id
             LEFT JOIN subscription_invoices si ON si.id = ac.invoice_id
             WHERE ac.affiliate_id = :id
             ORDER BY ac.earned_at ASC, ac.id ASC"
        );
        $rows->execute(['id' => $affiliateId]);
        $items = $rows->fetchAll();

        $earned = $approved = $paid = $reversed = 0.0;
        foreach ($items as $item) {
            $amount = (float) ($item['commission_amount'] ?? 0);
            if ((string) $item['status'] === 'Reversed') {
                $reversed += $amount;
                continue;
            }
            $earned += $amount;
            if ((string) $item['status'] === 'Approved') { $approved += $amount; }
            if ((string) $item['status'] === 'Paid') { $paid += $amount; }
        }

        return [
            'opening_balance' => 0.0,
            'earned' => $earned,
            'approved' => $approved,
            'paid' => $paid,
            'reversed' => $reversed,
            'closing_balance' => $earned - $paid - $reversed,
            'items' => $items,
        ];
    }

    public function payoutBatches(?int $affiliateId = null): array
    {
        $this->ensureSchema();
        $where = $affiliateId ? 'WHERE apb.affiliate_id = :affiliate_id' : '';
        $stmt = $this->db->prepare(
            "SELECT apb.*, a.full_name AS affiliate_name, pm.name AS payment_method_name
             FROM affiliate_payout_batches apb
             JOIN affiliates a ON a.id = apb.affiliate_id
             LEFT JOIN payment_methods pm ON pm.id = apb.payment_method_id
             {$where}
             ORDER BY apb.created_at DESC, apb.id DESC"
        );
        $affiliateId ? $stmt->execute(['affiliate_id' => $affiliateId]) : $stmt->execute();
        return $stmt->fetchAll();
    }

    public function payoutBatch(int $id): ?array
    {
        $this->ensureSchema();
        $stmt = $this->db->prepare(
            "SELECT apb.*, a.full_name AS affiliate_name, a.email AS affiliate_email, pm.name AS payment_method_name
             FROM affiliate_payout_batches apb
             JOIN affiliates a ON a.id = apb.affiliate_id
             LEFT JOIN payment_methods pm ON pm.id = apb.payment_method_id
             WHERE apb.id = :id"
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function payoutItems(int $batchId): array
    {
        $stmt = $this->db->prepare(
            "SELECT api.*, ac.earned_at, ac.status AS commission_status, c.name AS company_name, si.invoice_number
             FROM affiliate_payout_items api
             JOIN affiliate_commissions ac ON ac.id = api.commission_id
             LEFT JOIN companies c ON c.id = ac.company_id
             LEFT JOIN subscription_invoices si ON si.id = ac.invoice_id
             WHERE api.payout_batch_id = :id
             ORDER BY ac.earned_at ASC, api.id ASC"
        );
        $stmt->execute(['id' => $batchId]);
        return $stmt->fetchAll();
    }

    public function createPayoutBatch(int $affiliateId, int $methodId, ?string $periodFrom, ?string $periodTo, int $createdBy): int
    {
        $this->ensureSchema();
        $affiliate = $this->affiliate($affiliateId);
        $taxRate = (float) ($affiliate['payout_tax_rate'] ?? 0);

        $stmt = $this->db->prepare(
            "SELECT * FROM affiliate_commissions
             WHERE affiliate_id = :affiliate_id
               AND status IN ('Pending','Approved')
               AND eligible_at <= CURDATE()
               AND id NOT IN (SELECT commission_id FROM affiliate_payout_items)
             ORDER BY earned_at ASC, id ASC"
        );
        $stmt->execute(['affiliate_id' => $affiliateId]);
        $commissions = $stmt->fetchAll();
        if ($commissions === []) {
            throw new RuntimeException('No eligible unpaid commissions found for this affiliate.');
        }

        $reference = $this->generateReference('APB', 'affiliate_payout_batches', 'payout_reference');
        $this->db->beginTransaction();
        try {
            $gross = $tax = $net = 0.0;
            $this->db->prepare(
                "INSERT INTO affiliate_payout_batches (payout_reference, affiliate_id, period_from, period_to, payment_method_id, created_by)
                 VALUES (:reference, :affiliate_id, :period_from, :period_to, :method_id, :created_by)"
            )->execute([
                'reference' => $reference,
                'affiliate_id' => $affiliateId,
                'period_from' => $periodFrom,
                'period_to' => $periodTo,
                'method_id' => $methodId ?: null,
                'created_by' => $createdBy ?: null,
            ]);
            $batchId = (int) $this->db->lastInsertId();

            $insert = $this->db->prepare(
                "INSERT INTO affiliate_payout_items (payout_batch_id, commission_id, gross_amount, tax_amount, net_amount)
                 VALUES (:batch_id, :commission_id, :gross, :tax, :net)"
            );
            foreach ($commissions as $commission) {
                $itemGross = (float) $commission['commission_amount'];
                $itemTax = round($itemGross * ($taxRate / 100), 2);
                $itemNet = round($itemGross - $itemTax, 2);
                $gross += $itemGross;
                $tax += $itemTax;
                $net += $itemNet;
                $insert->execute([
                    'batch_id' => $batchId,
                    'commission_id' => (int) $commission['id'],
                    'gross' => $itemGross,
                    'tax' => $itemTax,
                    'net' => $itemNet,
                ]);
            }

            $this->db->prepare('UPDATE affiliate_payout_batches SET gross_amount = :gross, tax_amount = :tax, net_amount = :net WHERE id = :id')
                ->execute(['gross' => $gross, 'tax' => $tax, 'net' => $net, 'id' => $batchId]);
            $this->db->prepare("UPDATE affiliate_commissions SET status = 'Approved' WHERE id IN (SELECT commission_id FROM affiliate_payout_items WHERE payout_batch_id = :id)")
                ->execute(['id' => $batchId]);
            $this->db->commit();
            return $batchId;
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function updatePayoutStatus(int $batchId, string $status, ?string $paymentReference, int $adminId): void
    {
        $allowed = ['Submitted','Approved','Paid','Rejected','Voided'];
        if (!in_array($status, $allowed, true)) {
            throw new RuntimeException('Invalid payout status.');
        }
        $fields = ['status = :status'];
        $params = ['status' => $status, 'id' => $batchId];
        if ($status === 'Submitted') { $fields[] = 'submitted_at = NOW()'; }
        if ($status === 'Approved') { $fields[] = 'approved_at = NOW()'; $fields[] = 'approved_by = :admin_id'; $params['admin_id'] = $adminId ?: null; }
        if ($status === 'Paid') {
            $fields[] = 'paid_at = NOW()';
            $fields[] = 'paid_by = :admin_id';
            $fields[] = 'payment_reference = :payment_reference';
            $params['admin_id'] = $adminId ?: null;
            $params['payment_reference'] = $paymentReference ?: $this->generateReference('APP', 'affiliate_payout_batches', 'payment_reference');
        }
        $this->db->prepare('UPDATE affiliate_payout_batches SET ' . implode(', ', $fields) . ' WHERE id = :id')->execute($params);

        if ($status === 'Paid') {
            $this->db->prepare(
                "UPDATE affiliate_commissions
                 SET status = 'Paid', paid_at = CURDATE(), payout_reference = (SELECT payment_reference FROM affiliate_payout_batches WHERE id = :id)
                 WHERE id IN (SELECT commission_id FROM affiliate_payout_items WHERE payout_batch_id = :id)"
            )->execute(['id' => $batchId]);
        }
    }

    public function agreements(int $affiliateId): array
    {
        $this->ensureSchema();
        $stmt = $this->db->prepare('SELECT * FROM affiliate_agreements WHERE affiliate_id = :id ORDER BY created_at DESC, id DESC');
        $stmt->execute(['id' => $affiliateId]);
        return $stmt->fetchAll();
    }

    public function createAgreement(int $affiliateId, array $data, int $createdBy): int
    {
        $affiliate = $this->affiliate($affiliateId);
        $number = $this->generateReference('AAG', 'affiliate_agreements', 'agreement_number');
        $terms = trim((string) ($data['terms_html'] ?? ''));
        if ($terms === '') {
            $terms = $this->defaultAgreementTerms($affiliate);
        }
        $this->db->prepare(
            "INSERT INTO affiliate_agreements (affiliate_id, agreement_number, title, terms_html, effective_date, expiry_date, renewal_reminder_at, created_by)
             VALUES (:affiliate_id, :number, :title, :terms, :effective_date, :expiry_date, :reminder, :created_by)"
        )->execute([
            'affiliate_id' => $affiliateId,
            'number' => $number,
            'title' => trim((string) ($data['title'] ?? 'Corevia Affiliate Agreement')) ?: 'Corevia Affiliate Agreement',
            'terms' => $terms,
            'effective_date' => trim((string) ($data['effective_date'] ?? '')) ?: null,
            'expiry_date' => trim((string) ($data['expiry_date'] ?? '')) ?: null,
            'reminder' => trim((string) ($data['renewal_reminder_at'] ?? '')) ?: null,
            'created_by' => $createdBy ?: null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function updateAgreementStatus(int $agreementId, string $status): void
    {
        $allowed = ['Draft','Sent','Signed','Expired','Terminated'];
        if (!in_array($status, $allowed, true)) {
            throw new RuntimeException('Invalid agreement status.');
        }
        $fields = ['status = :status'];
        if ($status === 'Sent') { $fields[] = 'sent_at = NOW()'; }
        if ($status === 'Signed') { $fields[] = 'signed_at = NOW()'; }
        $this->db->prepare('UPDATE affiliate_agreements SET ' . implode(', ', $fields) . ' WHERE id = :id')
            ->execute(['status' => $status, 'id' => $agreementId]);
    }

    public function convertLead(int $leadId, int $planId, int $adminId): int
    {
        $lead = $this->lead($leadId);
        if (!$lead) {
            throw new RuntimeException('Lead not found.');
        }
        if (!empty($lead['converted_company_id'])) {
            throw new RuntimeException('This lead has already been converted.');
        }
        $dupe = $this->db->prepare('SELECT id FROM companies WHERE email = :email OR phone = :phone OR name = :name LIMIT 1');
        $dupe->execute([
            'email' => trim((string) ($lead['contact_email'] ?? '')) ?: '__none__',
            'phone' => trim((string) ($lead['contact_phone'] ?? '')) ?: '__none__',
            'name' => (string) $lead['company_name'],
        ]);
        if ($dupe->fetch()) {
            throw new RuntimeException('A company with this name, email, or phone already exists.');
        }

        $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', (string) $lead['company_name']));
        $slug = trim($slug, '-') ?: 'company';
        $base = $slug;
        $i = 1;
        while ($this->slugExists($slug)) {
            $slug = $base . '-' . (++$i);
        }

        $this->db->beginTransaction();
        try {
            $this->db->prepare(
                "INSERT INTO companies (name, slug, phone, email, subscription_plan, account_status, is_active, created_at)
                 VALUES (:name, :slug, :phone, :email, 'Trial', 'Active', 1, NOW())"
            )->execute([
                'name' => (string) $lead['company_name'],
                'slug' => $slug,
                'phone' => trim((string) ($lead['contact_phone'] ?? '')) ?: null,
                'email' => trim((string) ($lead['contact_email'] ?? '')) ?: null,
            ]);
            $companyId = (int) $this->db->lastInsertId();

            if ($planId > 0) {
                $plan = $this->plan($planId);
                if ($plan) {
                    $this->db->prepare(
                        "INSERT INTO subscriptions (company_id, plan, billing_model, price, employee_count, monthly_rate, currency, billing_cycle, starts_at, ends_at, status, notes, created_by)
                         VALUES (:company_id, :plan, 'per_employee', 0, 0, :rate, :currency, :cycle, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 YEAR), 'Active', 'Converted from affiliate lead', :created_by)"
                    )->execute([
                        'company_id' => $companyId,
                        'plan' => (string) $plan['name'],
                        'rate' => (float) $plan['default_monthly_rate'],
                        'currency' => (string) $plan['currency'],
                        'cycle' => (string) $plan['default_billing_cycle'],
                        'created_by' => $adminId ?: null,
                    ]);
                }
            }

            $this->db->prepare(
                "INSERT INTO affiliate_referrals (affiliate_id, company_id, referral_status, commission_rate, referred_at, notes)
                 VALUES (:affiliate_id, :company_id, 'Active', NULL, CURDATE(), 'Converted from affiliate lead')"
            )->execute([
                'affiliate_id' => (int) $lead['affiliate_id'],
                'company_id' => $companyId,
            ]);

            $this->db->prepare(
                "UPDATE affiliate_leads SET stage = 'Won', converted_company_id = :company_id, converted_at = NOW(), converted_by = :admin_id WHERE id = :id"
            )->execute(['company_id' => $companyId, 'admin_id' => $adminId ?: null, 'id' => $leadId]);

            $this->db->commit();
            return $companyId;
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function messages(int $affiliateId): array
    {
        $this->ensureSchema();
        $stmt = $this->db->prepare(
            "SELECT * FROM affiliate_messages
             WHERE visibility = 'All Affiliates' OR affiliate_id = :id
             ORDER BY created_at DESC, id DESC"
        );
        $stmt->execute(['id' => $affiliateId]);
        return $stmt->fetchAll();
    }

    public function tickets(int $affiliateId): array
    {
        $this->ensureSchema();
        $stmt = $this->db->prepare('SELECT * FROM affiliate_support_tickets WHERE affiliate_id = :id ORDER BY created_at DESC, id DESC');
        $stmt->execute(['id' => $affiliateId]);
        return $stmt->fetchAll();
    }

    public function createTicket(int $affiliateId, string $subject, string $message, string $priority): int
    {
        $priority = in_array($priority, ['Low','Normal','High'], true) ? $priority : 'Normal';
        $number = $this->generateReference('AST', 'affiliate_support_tickets', 'ticket_number');
        $this->db->prepare(
            "INSERT INTO affiliate_support_tickets (ticket_number, affiliate_id, subject, message, priority)
             VALUES (:number, :affiliate_id, :subject, :message, :priority)"
        )->execute([
            'number' => $number,
            'affiliate_id' => $affiliateId,
            'subject' => $subject,
            'message' => $message,
            'priority' => $priority,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function logAffiliateLogin(?int $affiliateId, string $email, bool $success): void
    {
        $this->ensureSchema();
        $this->db->prepare(
            "INSERT INTO affiliate_login_history (affiliate_id, email, success, ip_address, user_agent)
             VALUES (:affiliate_id, :email, :success, :ip, :agent)"
        )->execute([
            'affiliate_id' => $affiliateId ?: null,
            'email' => $email,
            'success' => $success ? 1 : 0,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
            'agent' => substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255) ?: null,
        ]);
    }

    public function generateReference(string $prefix, string $table, string $column): string
    {
        do {
            $reference = $prefix . '-' . date('Ym') . '-' . str_pad((string) random_int(1, 99999), 5, '0', STR_PAD_LEFT);
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM {$table} WHERE {$column} = :reference");
            $stmt->execute(['reference' => $reference]);
        } while ((int) $stmt->fetchColumn() > 0);
        return $reference;
    }

    private function affiliate(int $id): array
    {
        $stmt = $this->db->prepare('SELECT * FROM affiliates WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        if (!$row) { throw new RuntimeException('Affiliate not found.'); }
        return $row;
    }

    private function lead(int $id): ?array
    {
        $this->ensureSchema();
        if (!$this->columnExists('affiliate_leads', 'converted_at')) {
            $this->db->exec('ALTER TABLE affiliate_leads ADD COLUMN converted_at DATETIME NULL AFTER converted_company_id');
        }
        if (!$this->columnExists('affiliate_leads', 'converted_by')) {
            $this->db->exec('ALTER TABLE affiliate_leads ADD COLUMN converted_by BIGINT UNSIGNED NULL AFTER converted_at');
        }
        $stmt = $this->db->prepare('SELECT * FROM affiliate_leads WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    private function plan(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM subscription_plans WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    private function slugExists(string $slug): bool
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM companies WHERE slug = :slug');
        $stmt->execute(['slug' => $slug]);
        return (int) $stmt->fetchColumn() > 0;
    }

    private function defaultAgreementTerms(array $affiliate): string
    {
        $name = htmlspecialchars((string) ($affiliate['full_name'] ?? 'Affiliate'), ENT_QUOTES, 'UTF-8');
        $rate = htmlspecialchars((string) ($affiliate['commission_rate'] ?? '5.00'), ENT_QUOTES, 'UTF-8');
        $duration = htmlspecialchars((string) ($affiliate['commission_duration'] ?? 'First Year'), ENT_QUOTES, 'UTF-8');
        return "<p>This Affiliate Agreement is entered between StoneSoft IT Solutions and {$name}.</p>"
            . "<p>The affiliate shall introduce prospective Corevia customers and shall be eligible for commission on qualifying paid subscription receipts.</p>"
            . "<p>The default commission rate is {$rate}% and the commission duration is {$duration}, subject to approval, reversals, and payout controls maintained in Corevia.</p>"
            . "<p>The affiliate shall maintain accurate KYC, tax, and payout details before payments are released.</p>";
    }

    private function columnExists(string $table, string $column): bool
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table_name AND COLUMN_NAME = :column_name'
        );
        $stmt->execute(['table_name' => $table, 'column_name' => $column]);
        return (int) $stmt->fetchColumn() > 0;
    }

    private function tableExists(string $table): bool
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table_name');
        $stmt->execute(['table_name' => $table]);
        return (int) $stmt->fetchColumn() > 0;
    }
}
