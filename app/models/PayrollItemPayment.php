<?php

declare(strict_types=1);

class PayrollItemPayment extends Model
{
    protected string $table = 'payroll_item_payments';

    public function summaryForRun(int $runId): array
    {
        $stmt = $this->db->prepare(
            "SELECT pi.id AS payroll_item_id,
                    COALESCE(SUM(pip.amount), 0) AS paid_amount
             FROM payroll_items pi
             LEFT JOIN payroll_item_payments pip ON pip.payroll_item_id = pi.id
             WHERE pi.payroll_run_id = :run_id
             GROUP BY pi.id"
        );
        $stmt->execute(['run_id' => $runId]);

        $summary = [];
        foreach ($stmt->fetchAll() as $row) {
            $summary[(int) $row['payroll_item_id']] = (float) $row['paid_amount'];
        }

        return $summary;
    }

    public function allocateRunPayment(int $runId, int $runPaymentId, float $amount, array $data): void
    {
        if ($amount <= 0) {
            return;
        }

        $stmt = $this->db->prepare(
            "SELECT pi.id, pi.net_pay, COALESCE(SUM(pip.amount), 0) AS paid_amount
             FROM payroll_items pi
             LEFT JOIN payroll_item_payments pip ON pip.payroll_item_id = pi.id
             WHERE pi.payroll_run_id = :run_id
             GROUP BY pi.id, pi.net_pay
             HAVING pi.net_pay - paid_amount > 0.005
             ORDER BY pi.id ASC"
        );
        $stmt->execute(['run_id' => $runId]);
        $items = $stmt->fetchAll();

        $totalOutstanding = 0.0;
        foreach ($items as $item) {
            $totalOutstanding += max(0.0, (float) $item['net_pay'] - (float) $item['paid_amount']);
        }
        if ($totalOutstanding <= 0) {
            return;
        }

        $remaining = $amount;
        $lastIndex = count($items) - 1;
        $insert = $this->db->prepare(
            "INSERT INTO payroll_item_payments
             (payroll_item_id, payroll_run_payment_id, payment_date, amount, payment_method, reference_number, notes, created_by)
             VALUES (:item_id, :run_payment_id, :payment_date, :amount, :method, :reference, :notes, :created_by)"
        );

        foreach ($items as $index => $item) {
            $outstanding = max(0.0, (float) $item['net_pay'] - (float) $item['paid_amount']);
            $share = $index === $lastIndex
                ? $remaining
                : round($amount * ($outstanding / $totalOutstanding), 2);
            $share = min($outstanding, max(0.0, $share));
            if ($share <= 0) {
                continue;
            }

            $insert->execute([
                'item_id' => (int) $item['id'],
                'run_payment_id' => $runPaymentId,
                'payment_date' => $data['payment_date'] ?? date('Y-m-d'),
                'amount' => $share,
                'method' => ($data['payment_method'] ?? '') !== '' ? $data['payment_method'] : null,
                'reference' => ($data['reference_number'] ?? '') !== '' ? $data['reference_number'] : null,
                'notes' => ($data['notes'] ?? '') !== '' ? $data['notes'] : null,
                'created_by' => $data['created_by'] ?? null,
            ]);
            $remaining = max(0.0, $remaining - $share);
        }
    }

    public function recordItemPayment(int $payrollItemId, array $data): void
    {
        $this->db->beginTransaction();
        try {
            $this->recordItemPaymentInTransaction($payrollItemId, $data);
            $this->db->commit();
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    private function recordItemPaymentInTransaction(int $payrollItemId, array $data): void
    {
        $tenantFilter = Tenant::id() > 0 ? ' AND pr.company_id = :company_id' : '';
        $stmt = $this->db->prepare(
            "SELECT pi.*, pr.company_id, pr.total_net AS run_total_net, COALESCE(item_pay.paid_amount, 0) AS paid_amount
             FROM payroll_items pi
             INNER JOIN payroll_runs pr ON pr.id = pi.payroll_run_id
             LEFT JOIN (
                SELECT payroll_item_id, SUM(amount) AS paid_amount
                FROM payroll_item_payments
                GROUP BY payroll_item_id
             ) item_pay ON item_pay.payroll_item_id = pi.id
             WHERE pi.id = :id{$tenantFilter}
             LIMIT 1"
        );
        $params = ['id' => $payrollItemId];
        if (Tenant::id() > 0) {
            $params['company_id'] = Tenant::id();
        }
        $stmt->execute($params);
        $item = $stmt->fetch();
        if (!$item) {
            throw new RuntimeException('Payslip item not found.');
        }

        $amount = (float) ($data['amount'] ?? 0);
        $balance = max(0.0, (float) $item['net_pay'] - (float) $item['paid_amount']);
        if ($amount <= 0 || $amount > $balance + 0.005) {
            throw new RuntimeException('Payment amount must be greater than zero and cannot exceed the payslip balance.');
        }

        $runPaidStmt = $this->db->prepare('SELECT COALESCE(SUM(amount), 0) FROM payroll_run_payments WHERE payroll_run_id = :run_id');
        $runPaidStmt->execute(['run_id' => (int) $item['payroll_run_id']]);
        $runPaidSoFar = (float) $runPaidStmt->fetchColumn();
        $runBalance = max(0.0, (float) $item['run_total_net'] - $runPaidSoFar);
        if ($amount > $runBalance + 0.005) {
            throw new RuntimeException('Payment amount cannot exceed the payroll run balance.');
        }

        $paymentDate = $data['payment_date'] ?? date('Y-m-d');
        $paymentMethod = trim((string) ($data['payment_method'] ?? ''));
        $referenceNumber = trim((string) ($data['reference_number'] ?? ''));
        if ($referenceNumber === '') {
            $referenceNumber = (new PayrollRunPayment())->generateReferenceForRun((int) $item['payroll_run_id']);
        }
        $notes = trim((string) ($data['notes'] ?? ''));
        $createdBy = $data['created_by'] ?? null;

        $this->db->prepare(
            'INSERT INTO payroll_run_payments
             (payroll_run_id, payment_date, amount, payment_method, reference_number, notes, created_by)
             VALUES (:payroll_run_id, :payment_date, :amount, :payment_method, :reference_number, :notes, :created_by)'
        )->execute([
            'payroll_run_id' => (int) $item['payroll_run_id'],
            'payment_date' => $paymentDate,
            'amount' => $amount,
            'payment_method' => $paymentMethod !== '' ? $paymentMethod : null,
            'reference_number' => $referenceNumber,
            'notes' => $notes !== '' ? $notes : null,
            'created_by' => $createdBy,
        ]);
        $runPaymentId = (int) $this->db->lastInsertId();

        $this->db->prepare(
            "INSERT INTO payroll_item_payments
             (payroll_item_id, payroll_run_payment_id, payment_date, amount, payment_method, reference_number, notes, created_by)
             VALUES (:item_id, :run_payment_id, :payment_date, :amount, :method, :reference, :notes, :created_by)"
        )->execute([
            'item_id' => $payrollItemId,
            'run_payment_id' => $runPaymentId,
            'payment_date' => $paymentDate,
            'amount' => $amount,
            'method' => $paymentMethod !== '' ? $paymentMethod : null,
            'reference' => $referenceNumber,
            'notes' => $notes !== '' ? $notes : null,
            'created_by' => $createdBy,
        ]);

        $newRunPaidTotal = $runPaidSoFar + $amount;
        $newRunBalance = max(0.0, (float) $item['run_total_net'] - $newRunPaidTotal);
        $newStatus = $newRunBalance <= 0.00001 ? 'Paid' : 'Partially Paid';
        $this->db->prepare('UPDATE payroll_runs SET status = :status WHERE id = :id')->execute([
            'status' => $newStatus,
            'id' => (int) $item['payroll_run_id'],
        ]);
    }
}
