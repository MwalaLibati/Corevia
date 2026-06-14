<?php

declare(strict_types=1);

class SubscriptionInvoice extends Model
{
    protected string $table = 'subscription_invoices';

    public function listAll(): array
    {
        return $this->db->query(
            "SELECT si.*, c.name AS company_name, c.slug, s.plan AS subscription_plan
             FROM subscription_invoices si
             JOIN companies c ON c.id = si.company_id
             LEFT JOIN subscriptions s ON s.id = si.subscription_id
             ORDER BY si.created_at DESC, si.id DESC"
        )->fetchAll();
    }

    public function findDetailed(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT si.*, c.name AS company_name, c.slug, c.email AS company_email, c.phone AS company_phone,
                    c.address AS company_address, s.plan AS subscription_plan, s.billing_cycle
             FROM subscription_invoices si
             JOIN companies c ON c.id = si.company_id
             LEFT JOIN subscriptions s ON s.id = si.subscription_id
             WHERE si.id = :id
             LIMIT 1"
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function lines(int $invoiceId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM subscription_invoice_lines WHERE invoice_id = :id ORDER BY id ASC'
        );
        $stmt->execute(['id' => $invoiceId]);

        return $stmt->fetchAll();
    }

    public function payments(int $invoiceId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM subscription_invoice_payments WHERE invoice_id = :id ORDER BY paid_at DESC, id DESC'
        );
        $stmt->execute(['id' => $invoiceId]);

        return $stmt->fetchAll();
    }

    public function paymentDetailed(int $paymentId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT sip.*, si.invoice_number, si.currency, si.total_amount, si.paid_amount, si.balance_due,
                    c.name AS company_name, c.email AS company_email, c.phone AS company_phone, c.address AS company_address
             FROM subscription_invoice_payments sip
             JOIN subscription_invoices si ON si.id = sip.invoice_id
             JOIN companies c ON c.id = si.company_id
             WHERE sip.id = :id
             LIMIT 1"
        );
        $stmt->execute(['id' => $paymentId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function createFromSubscription(int $subscriptionId, int $createdBy): int
    {
        $stmt = $this->db->prepare(
            "SELECT s.*, c.name AS company_name
             FROM subscriptions s
             JOIN companies c ON c.id = s.company_id
             WHERE s.id = :id
             LIMIT 1"
        );
        $stmt->execute(['id' => $subscriptionId]);
        $sub = $stmt->fetch();
        if (!$sub) {
            throw new RuntimeException('Subscription not found.');
        }

        $existing = $this->db->prepare('SELECT id FROM subscription_invoices WHERE subscription_id = :id LIMIT 1');
        $existing->execute(['id' => $subscriptionId]);
        $existingId = (int) ($existing->fetchColumn() ?: 0);
        if ($existingId > 0) {
            return $existingId;
        }

        $invoiceNumber = $this->generateInvoiceNumber();
        $issueDate = date('Y-m-d');
        $dueDate = date('Y-m-d', strtotime('+14 days'));
        $total = (float) ($sub['price'] ?? 0);
        $description = sprintf(
            '%s subscription (%s to %s)',
            (string) ($sub['plan'] ?? 'Subscription'),
            (string) ($sub['starts_at'] ?? ''),
            (string) ($sub['ends_at'] ?? '')
        );

        $this->db->beginTransaction();
        try {
            $insert = $this->db->prepare(
                "INSERT INTO subscription_invoices
                 (company_id, subscription_id, invoice_number, issue_date, due_date, subtotal, tax_amount, total_amount, paid_amount, balance_due, currency, status, notes, created_by)
                 VALUES (:company_id, :subscription_id, :invoice_number, :issue_date, :due_date, :subtotal, 0, :total, 0, :balance, :currency, 'Unpaid', :notes, :created_by)"
            );
            $insert->execute([
                'company_id' => (int) $sub['company_id'],
                'subscription_id' => $subscriptionId,
                'invoice_number' => $invoiceNumber,
                'issue_date' => $issueDate,
                'due_date' => $dueDate,
                'subtotal' => $total,
                'total' => $total,
                'balance' => $total,
                'currency' => (string) ($sub['currency'] ?? 'ZMW'),
                'notes' => (string) ($sub['notes'] ?? '') ?: null,
                'created_by' => $createdBy > 0 ? $createdBy : null,
            ]);
            $invoiceId = (int) $this->db->lastInsertId();

            $this->db->prepare(
                "INSERT INTO subscription_invoice_lines (invoice_id, description, quantity, unit_price, line_total)
                 VALUES (:invoice_id, :description, 1, :unit_price, :line_total)"
            )->execute([
                'invoice_id' => $invoiceId,
                'description' => $description,
                'unit_price' => $total,
                'line_total' => $total,
            ]);

            $this->db->prepare('UPDATE subscriptions SET invoice_id = :invoice_id WHERE id = :id')
                ->execute(['invoice_id' => $invoiceId, 'id' => $subscriptionId]);

            $this->db->commit();
            return $invoiceId;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function recordPayment(int $invoiceId, float $amount, string $paidAt, string $method, string $reference, string $notes, int $recordedBy): void
    {
        $invoice = $this->findDetailed($invoiceId);
        if (!$invoice) {
            throw new RuntimeException('Invoice not found.');
        }
        if ($amount <= 0) {
            throw new RuntimeException('Payment amount must be greater than zero.');
        }

        $this->db->beginTransaction();
        try {
            $this->db->prepare(
                "INSERT INTO subscription_invoice_payments
                 (invoice_id, paid_at, amount, payment_method, payment_reference, notes, recorded_by)
                 VALUES (:invoice_id, :paid_at, :amount, :method, :reference, :notes, :recorded_by)"
            )->execute([
                'invoice_id' => $invoiceId,
                'paid_at' => $paidAt,
                'amount' => $amount,
                'method' => $method ?: null,
                'reference' => $reference ?: null,
                'notes' => $notes ?: null,
                'recorded_by' => $recordedBy > 0 ? $recordedBy : null,
            ]);
            $paymentId = (int) $this->db->lastInsertId();

            $this->recalculate($invoiceId);
            $this->reactivateCompanyIfSettled($invoiceId);
            (new Affiliate())->createCommissionForPayment($paymentId);
            $this->db->commit();
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function markSent(int $invoiceId, bool $emailed = false): void
    {
        $sql = $emailed
            ? "UPDATE subscription_invoices SET status = CASE WHEN paid_amount > 0 THEN status ELSE 'Sent' END, sent_at = COALESCE(sent_at, NOW()), emailed_at = NOW() WHERE id = :id"
            : "UPDATE subscription_invoices SET status = CASE WHEN paid_amount > 0 THEN status ELSE 'Sent' END, sent_at = COALESCE(sent_at, NOW()) WHERE id = :id";
        $this->db->prepare($sql)->execute(['id' => $invoiceId]);
    }

    private function reactivateCompanyIfSettled(int $invoiceId): void
    {
        $invoice = $this->findDetailed($invoiceId);
        if (!$invoice || (float) ($invoice['balance_due'] ?? 0) > 0) {
            return;
        }

        $open = $this->db->prepare('SELECT COUNT(*) FROM subscription_invoices WHERE company_id = :cid AND balance_due > 0');
        $open->execute(['cid' => (int) $invoice['company_id']]);
        if ((int) $open->fetchColumn() === 0) {
            $this->db->prepare("UPDATE companies SET is_active = 1, account_status = 'Active', reactivated_at = NOW(), suspension_reason = NULL WHERE id = :cid AND account_status = 'Suspended'")
                ->execute(['cid' => (int) $invoice['company_id']]);
        }
    }

    public function recalculate(int $invoiceId): void
    {
        $stmt = $this->db->prepare(
            "SELECT total_amount, COALESCE((SELECT SUM(amount) FROM subscription_invoice_payments WHERE invoice_id = :payment_invoice_id), 0) AS paid
             FROM subscription_invoices
             WHERE id = :invoice_id
             LIMIT 1"
        );
        $stmt->execute(['payment_invoice_id' => $invoiceId, 'invoice_id' => $invoiceId]);
        $row = $stmt->fetch();
        if (!$row) {
            return;
        }

        $total = (float) $row['total_amount'];
        $paid = (float) $row['paid'];
        $balance = max(0, $total - $paid);
        $status = 'Unpaid';
        if ($paid > 0 && $balance > 0) {
            $status = 'Partially Paid';
        } elseif ($paid >= $total && $total > 0) {
            $status = 'Paid';
        }

        $this->db->prepare(
            'UPDATE subscription_invoices SET paid_amount = :paid, balance_due = :balance, status = :status WHERE id = :id'
        )->execute(['paid' => $paid, 'balance' => $balance, 'status' => $status, 'id' => $invoiceId]);
    }

    private function generateInvoiceNumber(): string
    {
        $prefix = 'INV-' . date('Ym') . '-';
        $stmt = $this->db->prepare(
            'SELECT invoice_number FROM subscription_invoices WHERE invoice_number LIKE :prefix ORDER BY invoice_number DESC LIMIT 1'
        );
        $stmt->execute(['prefix' => $prefix . '%']);
        $last = (string) ($stmt->fetchColumn() ?: '');
        $next = 1;
        if ($last !== '') {
            $next = ((int) substr($last, -5)) + 1;
        }

        return $prefix . str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }
}
