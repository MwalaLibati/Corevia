<?php

declare(strict_types=1);

class StatutoryPayment extends Model
{
    protected string $table = 'statutory_payments';
    protected bool $tenantScoped = true;

    public function forRunAndCode(int $payrollRunId, string $code): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM statutory_payments
             WHERE company_id = :cid AND payroll_run_id = :run_id AND statutory_code = :code
             LIMIT 1'
        );
        $stmt->execute([
            'cid' => Tenant::id(),
            'run_id' => $payrollRunId,
            'code' => strtoupper($code),
        ]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function upsertForRun(array $data): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO statutory_payments
                (company_id, payroll_run_id, pay_period, statutory_code, employee_amount, employer_amount, total_amount,
                 payment_reference, payment_date, status, notes, created_by)
             VALUES
                (:company_id, :payroll_run_id, :pay_period, :statutory_code, :employee_amount, :employer_amount, :total_amount,
                 :payment_reference, :payment_date, :status, :notes, :created_by)
             ON DUPLICATE KEY UPDATE
                 employee_amount = VALUES(employee_amount),
                 employer_amount = VALUES(employer_amount),
                 total_amount = VALUES(total_amount),
                 payment_reference = VALUES(payment_reference),
                 payment_date = VALUES(payment_date),
                 status = VALUES(status),
                 notes = VALUES(notes),
                 updated_at = CURRENT_TIMESTAMP'
        );
        $stmt->execute([
            'company_id' => Tenant::id(),
            'payroll_run_id' => (int) $data['payroll_run_id'],
            'pay_period' => (string) $data['pay_period'],
            'statutory_code' => strtoupper((string) $data['statutory_code']),
            'employee_amount' => (float) $data['employee_amount'],
            'employer_amount' => (float) $data['employer_amount'],
            'total_amount' => (float) $data['total_amount'],
            'payment_reference' => trim((string) ($data['payment_reference'] ?? '')),
            'payment_date' => $data['payment_date'] ?: null,
            'status' => (string) ($data['status'] ?? 'Pending'),
            'notes' => trim((string) ($data['notes'] ?? '')),
            'created_by' => (int) ($data['created_by'] ?? 0) ?: null,
        ]);
    }

    public function recent(int $limit = 25): array
    {
        $stmt = $this->db->prepare(
            'SELECT sp.*, pr.run_date
             FROM statutory_payments sp
             JOIN payroll_runs pr ON pr.id = sp.payroll_run_id
             WHERE sp.company_id = :cid
             ORDER BY COALESCE(sp.payment_date, pr.run_date) DESC, sp.id DESC
             LIMIT :limit'
        );
        $stmt->bindValue(':cid', Tenant::id(), PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
