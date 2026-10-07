<?php

declare(strict_types=1);

/**
 * Immutable presentation-currency snapshots for generated payslips.
 */
class PayslipCurrencyVersion extends Model
{
    protected string $table = 'payslip_currency_versions';
    protected bool $tenantScoped = true;

    public function __construct()
    {
        parent::__construct();
        $this->ensureSchema();
    }

    public function ensureSchema(): void
    {
        $this->db->exec(
            "CREATE TABLE IF NOT EXISTS payslip_currency_versions (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                company_id BIGINT UNSIGNED NOT NULL,
                payroll_item_id BIGINT UNSIGNED NOT NULL,
                payroll_run_id BIGINT UNSIGNED NOT NULL,
                employee_id BIGINT UNSIGNED NOT NULL,
                version_number INT UNSIGNED NOT NULL DEFAULT 1,
                source_currency CHAR(3) NOT NULL,
                target_currency CHAR(3) NOT NULL,
                source_per_target_rate DECIMAL(18,8) NOT NULL,
                rate_date DATE NOT NULL,
                rate_source VARCHAR(150) NULL,
                gross_source DECIMAL(14,2) NOT NULL DEFAULT 0.00,
                deductions_source DECIMAL(14,2) NOT NULL DEFAULT 0.00,
                net_source DECIMAL(14,2) NOT NULL DEFAULT 0.00,
                gross_converted DECIMAL(14,2) NOT NULL DEFAULT 0.00,
                deductions_converted DECIMAL(14,2) NOT NULL DEFAULT 0.00,
                net_converted DECIMAL(14,2) NOT NULL DEFAULT 0.00,
                snapshot_json LONGTEXT NOT NULL,
                pdf_path VARCHAR(500) NULL,
                published_to_portal TINYINT(1) NOT NULL DEFAULT 0,
                notified_at DATETIME NULL,
                created_by BIGINT UNSIGNED NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_pcv_company_run (company_id, payroll_run_id),
                INDEX idx_pcv_item (payroll_item_id),
                INDEX idx_pcv_employee (employee_id),
                UNIQUE KEY uq_pcv_item_version (payroll_item_id, version_number)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );
    }

    public function createFromPayload(array $payload, array $input, ?int $userId): array
    {
        $item = $payload['item'] ?? [];
        $companyId = Tenant::id();
        $payrollItemId = (int) ($item['id'] ?? 0);
        $payrollRunId = (int) ($item['payroll_run_id'] ?? 0);
        $employeeId = (int) ($item['employee_id'] ?? 0);
        if ($companyId <= 0 || $payrollItemId <= 0 || $payrollRunId <= 0 || $employeeId <= 0) {
            throw new RuntimeException('The payslip could not be identified for currency conversion.');
        }

        $currencies = corevia_currency_options();
        $sourceCurrency = strtoupper(trim((string) ($item['currency_code'] ?? app_currency_code())));
        $targetCurrency = strtoupper(trim((string) ($input['target_currency'] ?? '')));
        if (!isset($currencies[$sourceCurrency]) || !isset($currencies[$targetCurrency])) {
            throw new RuntimeException('Please select a supported payslip currency.');
        }
        if ($sourceCurrency === $targetCurrency) {
            throw new RuntimeException('The presentation currency must be different from the payroll currency.');
        }

        $exchangeRate = (float) ($input['exchange_rate'] ?? 0);
        if (!is_finite($exchangeRate) || $exchangeRate <= 0 || $exchangeRate > 100000000) {
            throw new RuntimeException('Enter a valid exchange rate greater than zero.');
        }

        $rateDate = trim((string) ($input['rate_date'] ?? ''));
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $rateDate);
        if (!$date || $date->format('Y-m-d') !== $rateDate) {
            throw new RuntimeException('Enter a valid exchange-rate date.');
        }

        $convert = static fn(float $amount): float => round($amount / $exchangeRate, 2);
        $earnings = $this->convertLines((array) ($payload['earningsLines'] ?? []), $convert);
        $deductions = $this->convertLines((array) ($payload['deductionLines'] ?? []), $convert);
        $grossSource = round((float) ($payload['grossEarnings'] ?? $item['gross_pay'] ?? 0), 2);
        $deductionsSource = round((float) ($payload['totalDeductions'] ?? $item['total_deductions'] ?? 0), 2);
        $netSource = round((float) ($payload['netPay'] ?? $item['net_pay'] ?? 0), 2);
        $grossConverted = $convert($grossSource);
        $deductionsConverted = $convert($deductionsSource);
        $netConverted = $convert($netSource);

        $snapshot = [
            'source_currency' => $sourceCurrency,
            'target_currency' => $targetCurrency,
            'source_per_target_rate' => $exchangeRate,
            'rate_date' => $rateDate,
            'rate_source' => trim((string) ($input['rate_source'] ?? '')),
            'gross_source' => $grossSource,
            'deductions_source' => $deductionsSource,
            'net_source' => $netSource,
            'gross_converted' => $grossConverted,
            'deductions_converted' => $deductionsConverted,
            'net_converted' => $netConverted,
            'earnings' => $earnings,
            'deductions' => $deductions,
        ];

        $this->db->beginTransaction();
        try {
            $versionStmt = $this->db->prepare(
                'SELECT COALESCE(MAX(version_number), 0) + 1
                 FROM payslip_currency_versions
                 WHERE company_id = :company_id AND payroll_item_id = :payroll_item_id
                 FOR UPDATE'
            );
            $versionStmt->execute(['company_id' => $companyId, 'payroll_item_id' => $payrollItemId]);
            $versionNumber = max(1, (int) $versionStmt->fetchColumn());

            $stmt = $this->db->prepare(
                'INSERT INTO payslip_currency_versions
                    (company_id, payroll_item_id, payroll_run_id, employee_id, version_number,
                     source_currency, target_currency, source_per_target_rate, rate_date, rate_source,
                     gross_source, deductions_source, net_source, gross_converted, deductions_converted,
                     net_converted, snapshot_json, published_to_portal, created_by)
                 VALUES
                    (:company_id, :payroll_item_id, :payroll_run_id, :employee_id, :version_number,
                     :source_currency, :target_currency, :exchange_rate, :rate_date, :rate_source,
                     :gross_source, :deductions_source, :net_source, :gross_converted, :deductions_converted,
                     :net_converted, :snapshot_json, :published_to_portal, :created_by)'
            );
            $stmt->execute([
                'company_id' => $companyId,
                'payroll_item_id' => $payrollItemId,
                'payroll_run_id' => $payrollRunId,
                'employee_id' => $employeeId,
                'version_number' => $versionNumber,
                'source_currency' => $sourceCurrency,
                'target_currency' => $targetCurrency,
                'exchange_rate' => $exchangeRate,
                'rate_date' => $rateDate,
                'rate_source' => $snapshot['rate_source'] !== '' ? $snapshot['rate_source'] : null,
                'gross_source' => $grossSource,
                'deductions_source' => $deductionsSource,
                'net_source' => $netSource,
                'gross_converted' => $grossConverted,
                'deductions_converted' => $deductionsConverted,
                'net_converted' => $netConverted,
                'snapshot_json' => json_encode($snapshot, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
                'published_to_portal' => !empty($input['published_to_portal']) ? 1 : 0,
                'created_by' => $userId ?: null,
            ]);
            $id = (int) $this->db->lastInsertId();
            $this->db->commit();
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }

        $version = $this->findDetailed($id);
        if (!$version) {
            throw new RuntimeException('Converted payslip was saved but could not be reloaded.');
        }

        return $version;
    }

    public function findDetailed(int $id): ?array
    {
        $cid = Tenant::id();
        $stmt = $this->db->prepare(
            'SELECT pcv.*, e.full_name AS employee_name, e.employee_number, e.email AS employee_email,
                    pr.pay_period, pr.run_date, pr.payslips_released
             FROM payslip_currency_versions pcv
             JOIN employees e ON e.id = pcv.employee_id
             JOIN payroll_runs pr ON pr.id = pcv.payroll_run_id
             WHERE pcv.id = :id AND pcv.company_id = :company_id
             LIMIT 1'
        );
        $stmt->execute(['id' => $id, 'company_id' => $cid]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findForEmployee(int $id, int $employeeId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT pcv.*, e.full_name AS employee_name, e.employee_number,
                    pr.pay_period, pr.run_date, pr.payslips_released
             FROM payslip_currency_versions pcv
             JOIN employees e ON e.id = pcv.employee_id
             JOIN payroll_runs pr ON pr.id = pcv.payroll_run_id
             WHERE pcv.id = :id
               AND pcv.company_id = :company_id
               AND pcv.employee_id = :employee_id
               AND pcv.published_to_portal = 1
               AND pr.payslips_released = 1
             LIMIT 1'
        );
        $stmt->execute([
            'id' => $id,
            'company_id' => Tenant::id(),
            'employee_id' => $employeeId,
        ]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function listForRun(int $runId): array
    {
        $stmt = $this->db->prepare(
            'SELECT pcv.*, u.full_name AS created_by_name
             FROM payslip_currency_versions pcv
             LEFT JOIN users u ON u.id = pcv.created_by
             WHERE pcv.company_id = :company_id AND pcv.payroll_run_id = :payroll_run_id
             ORDER BY pcv.payroll_item_id ASC, pcv.version_number DESC'
        );
        $stmt->execute(['company_id' => Tenant::id(), 'payroll_run_id' => $runId]);
        return $stmt->fetchAll();
    }

    public function listPublishedForEmployee(int $employeeId): array
    {
        $stmt = $this->db->prepare(
            'SELECT pcv.*
             FROM payslip_currency_versions pcv
             JOIN payroll_runs pr ON pr.id = pcv.payroll_run_id
             WHERE pcv.company_id = :company_id
               AND pcv.employee_id = :employee_id
               AND pcv.published_to_portal = 1
               AND pr.payslips_released = 1
             ORDER BY pcv.created_at DESC'
        );
        $stmt->execute([
            'company_id' => Tenant::id(),
            'employee_id' => $employeeId,
        ]);
        return $stmt->fetchAll();
    }

    public function applyToPayload(array $version, array $payload): array
    {
        $snapshot = json_decode((string) ($version['snapshot_json'] ?? ''), true);
        if (!is_array($snapshot)) {
            throw new RuntimeException('The converted payslip snapshot is invalid.');
        }

        $targetCurrency = (string) ($version['target_currency'] ?? $snapshot['target_currency'] ?? '');
        $payload['item']['currency_code'] = $targetCurrency;
        $payload['item']['gross_pay'] = (float) ($version['gross_converted'] ?? 0);
        $payload['item']['total_deductions'] = (float) ($version['deductions_converted'] ?? 0);
        $payload['item']['net_pay'] = (float) ($version['net_converted'] ?? 0);
        $payload['earningsLines'] = (array) ($snapshot['earnings'] ?? []);
        $payload['deductionLines'] = (array) ($snapshot['deductions'] ?? []);
        $payload['grossEarnings'] = (float) ($version['gross_converted'] ?? 0);
        $payload['totalDeductions'] = (float) ($version['deductions_converted'] ?? 0);
        $payload['netPay'] = (float) ($version['net_converted'] ?? 0);
        $payload['conversion'] = $version;
        $payload['downloadName'] = (string) ($payload['downloadName'] ?? 'payslip')
            . '-' . strtolower($targetCurrency)
            . '-v' . (int) ($version['version_number'] ?? 1);

        return $payload;
    }

    public function updatePdfPath(int $id, string $relativePath): void
    {
        $stmt = $this->db->prepare(
            'UPDATE payslip_currency_versions SET pdf_path = :pdf_path
             WHERE id = :id AND company_id = :company_id'
        );
        $stmt->execute(['pdf_path' => $relativePath, 'id' => $id, 'company_id' => Tenant::id()]);
    }

    public function publish(int $id, bool $notified = false): void
    {
        $sql = 'UPDATE payslip_currency_versions SET published_to_portal = 1';
        if ($notified) {
            $sql .= ', notified_at = NOW()';
        }
        $sql .= ' WHERE id = :id AND company_id = :company_id';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id, 'company_id' => Tenant::id()]);
    }

    private function convertLines(array $lines, callable $convert): array
    {
        $converted = [];
        foreach ($lines as $line) {
            $sourceAmount = round((float) ($line['amount'] ?? 0), 2);
            $converted[] = [
                'label' => (string) ($line['label'] ?? $line['name'] ?? 'Item'),
                'category' => (string) ($line['category'] ?? ''),
                'source_amount' => $sourceAmount,
                'amount' => $convert($sourceAmount),
            ];
        }
        return $converted;
    }
}
