<?php

declare(strict_types=1);

/**
 * Payroll run payment model for partial and full settlement tracking.
 */

class PayrollRunPayment extends Model
{
    protected string $table = 'payroll_run_payments';

    public function listForRun(int $runId): array
    {
        $cid = Tenant::id();
        $and = $cid > 0 ? ' AND pr.company_id = :cid' : '';
        $sql = 'SELECT prp.*, u.full_name AS created_by_name
                FROM payroll_run_payments prp
                JOIN payroll_runs pr ON pr.id = prp.payroll_run_id
                LEFT JOIN users u ON u.id = prp.created_by
                WHERE prp.payroll_run_id = :run_id' . $and . '
                ORDER BY prp.payment_date DESC, prp.id DESC';

        $stmt = $this->db->prepare($sql);
        $params = ['run_id' => $runId];
        if ($cid > 0) { $params['cid'] = $cid; }
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function summaryForRun(int $runId): array
    {
        $cid = Tenant::id();
        $and = $cid > 0 ? ' AND pr.company_id = :cid' : '';
        $sql = 'SELECT COUNT(*) AS payment_count,
                       COALESCE(SUM(prp.amount), 0) AS paid_total,
                       MAX(prp.payment_date) AS latest_payment_date
                FROM payroll_run_payments
                prp JOIN payroll_runs pr ON pr.id = prp.payroll_run_id
                WHERE prp.payroll_run_id = :run_id' . $and;

        $stmt = $this->db->prepare($sql);
        $params = ['run_id' => $runId];
        if ($cid > 0) { $params['cid'] = $cid; }
        $stmt->execute($params);
        $row = $stmt->fetch() ?: [];

        return [
            'payment_count' => (int) ($row['payment_count'] ?? 0),
            'paid_total' => (float) ($row['paid_total'] ?? 0),
            'latest_payment_date' => $row['latest_payment_date'] ?? null,
        ];
    }

    public function latestForRun(int $runId): ?array
    {
        $cid = Tenant::id();
        $and = $cid > 0 ? ' AND pr.company_id = :cid' : '';
        $sql = 'SELECT prp.*, u.full_name AS created_by_name
                FROM payroll_run_payments prp
                JOIN payroll_runs pr ON pr.id = prp.payroll_run_id
                LEFT JOIN users u ON u.id = prp.created_by
                WHERE prp.payroll_run_id = :run_id' . $and . '
                ORDER BY prp.payment_date DESC, prp.id DESC
                LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $params = ['run_id' => $runId];
        if ($cid > 0) { $params['cid'] = $cid; }
        $stmt->execute($params);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function recordPayment(int $runId, array $data): array
    {
        $this->db->beginTransaction();

        try {
            $cid = Tenant::id();
            $and = $cid > 0 ? ' AND company_id = :cid' : '';
            $runStmt = $this->db->prepare('SELECT id, total_net, status FROM payroll_runs WHERE id = :id' . $and . ' LIMIT 1 FOR UPDATE');
            $params = ['id' => $runId];
            if ($cid > 0) { $params['cid'] = $cid; }
            $runStmt->execute($params);
            $run = $runStmt->fetch();

            if (!$run) {
                throw new RuntimeException('Payroll run not found.');
            }

            $paymentAmount = (float) ($data['amount'] ?? 0);
            if ($paymentAmount <= 0) {
                throw new RuntimeException('Payment amount must be greater than zero.');
            }

            $summary = $this->summaryForRun($runId);
            $runTotal = (float) ($run['total_net'] ?? 0);
            $paidSoFar = (float) ($summary['paid_total'] ?? 0);
            $balance = max(0.0, $runTotal - $paidSoFar);

            if ($balance <= 0.0) {
                throw new RuntimeException('This payroll run is already fully paid.');
            }

            if ($paymentAmount > $balance) {
                throw new RuntimeException('Payment amount cannot exceed the remaining balance of ' . format_currency($balance) . '.');
            }

            $paymentDate = $data['payment_date'] ?? date('Y-m-d');
            $paymentMethod = trim((string) ($data['payment_method'] ?? ''));
            $referenceNumber = trim((string) ($data['reference_number'] ?? ''));
            if ($referenceNumber === '') {
                $referenceNumber = $this->generateReferenceForRun($runId);
            }
            $notes = trim((string) ($data['notes'] ?? ''));
            $createdBy = isset($data['created_by']) ? (int) $data['created_by'] : null;

            $insert = $this->db->prepare('INSERT INTO payroll_run_payments (payroll_run_id, payment_date, amount, payment_method, reference_number, notes, created_by) VALUES (:payroll_run_id, :payment_date, :amount, :payment_method, :reference_number, :notes, :created_by)');
            $insert->execute([
                'payroll_run_id' => $runId,
                'payment_date' => $paymentDate,
                'amount' => $paymentAmount,
                'payment_method' => $paymentMethod !== '' ? $paymentMethod : null,
                'reference_number' => $referenceNumber,
                'notes' => $notes !== '' ? $notes : null,
                'created_by' => $createdBy,
            ]);
            $runPaymentId = (int) $this->db->lastInsertId();

            (new PayrollItemPayment())->allocateRunPayment($runId, $runPaymentId, $paymentAmount, [
                'payment_date' => $paymentDate,
                'payment_method' => $paymentMethod,
                'reference_number' => $referenceNumber,
                'notes' => $notes,
                'created_by' => $createdBy,
            ]);

            $newPaidTotal = $paidSoFar + $paymentAmount;
            $newBalance = max(0.0, $runTotal - $newPaidTotal);
            $newStatus = $newBalance <= 0.00001 ? 'Paid' : 'Partially Paid';

            $updateRun = $this->db->prepare('UPDATE payroll_runs SET status = :status WHERE id = :id');
            $updateRun->execute([
                'status' => $newStatus,
                'id' => $runId,
            ]);

            $this->db->commit();

            return [
                'paid_total' => $newPaidTotal,
                'balance' => $newBalance,
                'status' => $newStatus,
                'payment_count' => $summary['payment_count'] + 1,
                'latest_payment_date' => $paymentDate,
            ];
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $exception;
        }
    }

    public function generateReferenceForRun(int $runId): string
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM payroll_run_payments WHERE payroll_run_id = :run_id');
        $stmt->execute(['run_id' => $runId]);
        $next = (int) $stmt->fetchColumn() + 1;

        return 'PAY-' . str_pad((string) $runId, 6, '0', STR_PAD_LEFT) . '-' . str_pad((string) $next, 3, '0', STR_PAD_LEFT);
    }
}
