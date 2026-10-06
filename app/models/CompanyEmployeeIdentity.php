<?php

declare(strict_types=1);

/**
 * Owns globally unique company codes and company-prefixed employee numbers.
 */
class CompanyEmployeeIdentity
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? db();
    }

    public function ensureSchema(): void
    {
        if (!$this->columnExists('companies', 'employee_code')) {
            $this->db->exec('ALTER TABLE companies ADD COLUMN employee_code VARCHAR(6) NULL AFTER slug');
        }
        if (!$this->columnExists('employees', 'legacy_employee_number')) {
            $this->db->exec('ALTER TABLE employees ADD COLUMN legacy_employee_number VARCHAR(50) NULL AFTER employee_number');
        }
        if (!$this->indexExists('companies', 'uq_companies_employee_code')) {
            $this->db->exec('CREATE UNIQUE INDEX uq_companies_employee_code ON companies (employee_code)');
        }
    }

    public function normalizeCode(string $code): string
    {
        return strtoupper(preg_replace('/[^A-Za-z]/', '', trim($code)) ?? '');
    }

    public function validCode(string $code): bool
    {
        return preg_match('/^[A-Z]{2,6}$/', $this->normalizeCode($code)) === 1;
    }

    public function codeExists(string $code, ?int $excludeCompanyId = null): bool
    {
        $sql = 'SELECT id FROM companies WHERE employee_code = :code';
        $params = ['code' => $this->normalizeCode($code)];
        if ($excludeCompanyId !== null) {
            $sql .= ' AND id != :id';
            $params['id'] = $excludeCompanyId;
        }
        $sql .= ' LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (bool) $stmt->fetchColumn();
    }

    public function suggestCode(string $companyName): string
    {
        $words = preg_split('/[^A-Za-z]+/', strtoupper($companyName), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $ignored = ['THE', 'LIMITED', 'LTD', 'PLC', 'INC', 'LLC', 'CORPORATION', 'CORP'];
        $meaningful = array_values(array_filter($words, static fn(string $word): bool => !in_array($word, $ignored, true)));
        $meaningful = $meaningful ?: $words;

        if (count($meaningful) >= 2) {
            $base = implode('', array_map(static fn(string $word): string => $word[0], $meaningful));
        } else {
            $base = substr($meaningful[0] ?? 'CO', 0, 6);
        }

        $base = substr($this->normalizeCode($base), 0, 6);
        if (strlen($base) < 2) {
            $base = str_pad($base, 2, 'X');
        }
        if (!$this->codeExists($base)) {
            return $base;
        }

        $stem = substr($base, 0, 5);
        foreach (range('A', 'Z') as $suffix) {
            $candidate = $stem . $suffix;
            if (!$this->codeExists($candidate)) {
                return $candidate;
            }
        }

        throw new RuntimeException('A unique employee code could not be generated. Enter one manually.');
    }

    public function previewNextEmployeeNumber(int $companyId): string
    {
        $company = $this->companyForNumbering($companyId, false);
        return $this->formatEmployeeNumber(
            (string) $company['employee_code'],
            $this->nextSequence($companyId, (string) $company['employee_code'])
        );
    }

    /** @return array<int,array{company_id:int,company_name:string,employee_code:string,employees:int}> */
    public function migrateExistingEmployeeNumbers(): array
    {
        $this->ensureSchema();
        $this->db->exec(
            'CREATE TABLE IF NOT EXISTS employee_number_migration_backup (
                employee_id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
                company_id BIGINT UNSIGNED NOT NULL,
                employee_number VARCHAR(50) NOT NULL,
                backed_up_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );
        $this->db->exec(
            'INSERT IGNORE INTO employee_number_migration_backup (employee_id, company_id, employee_number)
             SELECT id, company_id, employee_number FROM employees'
        );
        $companies = $this->db->query('SELECT id, name, employee_code FROM companies ORDER BY id ASC')->fetchAll();
        $summary = [];
        $ownsTransaction = !$this->db->inTransaction();
        if ($ownsTransaction) {
            $this->db->beginTransaction();
        }

        try {
            foreach ($companies as $company) {
                $companyId = (int) $company['id'];
                $code = $this->normalizeCode((string) ($company['employee_code'] ?? ''));
                if (!$this->validCode($code)) {
                    $code = $this->suggestCode((string) $company['name']);
                    $this->db->prepare('UPDATE companies SET employee_code = :code WHERE id = :id')
                        ->execute(['code' => $code, 'id' => $companyId]);
                }

                $employeeStmt = $this->db->prepare('SELECT id, employee_number FROM employees WHERE company_id = :company_id ORDER BY id ASC');
                $employeeStmt->execute(['company_id' => $companyId]);
                $employees = $employeeStmt->fetchAll();
                $assignedSequences = [];
                $nextAvailable = 1;
                foreach ($employees as $employee) {
                    $sequence = 0;
                    if (preg_match('/([0-9]+)$/', (string) $employee['employee_number'], $match) === 1) {
                        $candidate = (int) $match[1];
                        if ($candidate >= 1 && $candidate <= 9999 && !isset($assignedSequences[$candidate])) {
                            $sequence = $candidate;
                        }
                    }
                    if ($sequence === 0) {
                        while (isset($assignedSequences[$nextAvailable])) {
                            $nextAvailable++;
                        }
                        if ($nextAvailable > 9999) {
                            throw new RuntimeException("Employee number capacity has been reached for company code {$code}.");
                        }
                        $sequence = $nextAvailable;
                    }
                    $assignedSequences[$sequence] = (int) $employee['id'];
                }

                $stage = $this->db->prepare(
                    'UPDATE employees
                     SET legacy_employee_number = COALESCE(legacy_employee_number, employee_number),
                         employee_number = :temporary_number
                     WHERE id = :id'
                );
                foreach ($employees as $employee) {
                    $stage->execute([
                        'temporary_number' => 'MIG' . $companyId . 'X' . (int) $employee['id'],
                        'id' => (int) $employee['id'],
                    ]);
                }

                $finalize = $this->db->prepare('UPDATE employees SET employee_number = :employee_number WHERE id = :id');
                foreach ($assignedSequences as $sequence => $employeeId) {
                    $finalize->execute([
                        'employee_number' => $this->formatEmployeeNumber($code, (int) $sequence),
                        'id' => $employeeId,
                    ]);
                }

                $summary[] = [
                    'company_id' => $companyId,
                    'company_name' => (string) $company['name'],
                    'employee_code' => $code,
                    'employees' => count($employees),
                ];
            }

            if ($ownsTransaction) {
                $this->db->commit();
            }
        } catch (Throwable $e) {
            if ($ownsTransaction && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }

        if ($this->indexExists('employees', 'uq_employees_company_employee_number')) {
            $this->db->exec('DROP INDEX uq_employees_company_employee_number ON employees');
        }
        if (!$this->indexExists('employees', 'uq_employees_employee_number')) {
            $this->db->exec('CREATE UNIQUE INDEX uq_employees_employee_number ON employees (employee_number)');
        }

        return $summary;
    }

    /** @return array{id:int,employee_number:string} */
    public function createEmployee(array $data, int $companyId): array
    {
        if ($companyId <= 0) {
            throw new RuntimeException('A company is required before an employee number can be generated.');
        }

        $ownsTransaction = !$this->db->inTransaction();
        if ($ownsTransaction) {
            $this->db->beginTransaction();
        }

        try {
            $company = $this->companyForNumbering($companyId, true);
            $code = (string) $company['employee_code'];
            $employeeNumber = $this->formatEmployeeNumber($code, $this->nextSequence($companyId, $code));
            $data['company_id'] = $companyId;
            $data['employee_number'] = $employeeNumber;

            $columns = array_keys($data);
            $placeholders = array_map(static fn(string $column): string => ':' . $column, $columns);
            $stmt = $this->db->prepare(sprintf(
                'INSERT INTO employees (%s) VALUES (%s)',
                implode(', ', $columns),
                implode(', ', $placeholders)
            ));
            $stmt->execute($data);
            $id = (int) $this->db->lastInsertId();

            if ($ownsTransaction) {
                $this->db->commit();
            }
            return ['id' => $id, 'employee_number' => $employeeNumber];
        } catch (Throwable $e) {
            if ($ownsTransaction && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    private function companyForNumbering(int $companyId, bool $forUpdate): array
    {
        $sql = 'SELECT id, employee_code FROM companies WHERE id = :id LIMIT 1' . ($forUpdate ? ' FOR UPDATE' : '');
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $companyId]);
        $company = $stmt->fetch();
        if (!$company) {
            throw new RuntimeException('Company could not be found.');
        }

        $code = $this->normalizeCode((string) ($company['employee_code'] ?? ''));
        if (!$this->validCode($code)) {
            throw new RuntimeException('Configure a 2 to 6 letter employee code for this company before creating employees.');
        }
        $company['employee_code'] = $code;
        return $company;
    }

    private function nextSequence(int $companyId, string $code): int
    {
        $stmt = $this->db->prepare(
            'SELECT MAX(CAST(RIGHT(employee_number, 4) AS UNSIGNED))
             FROM employees
             WHERE company_id = :company_id
               AND employee_number REGEXP :pattern'
        );
        $stmt->execute([
            'company_id' => $companyId,
            'pattern' => '^' . $code . '[0-9]{4}$',
        ]);
        $next = ((int) $stmt->fetchColumn()) + 1;
        if ($next > 9999) {
            throw new RuntimeException("Employee number capacity has been reached for company code {$code}.");
        }
        return max(1, $next);
    }

    private function formatEmployeeNumber(string $code, int $sequence): string
    {
        return $code . str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }

    private function columnExists(string $table, string $column): bool
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table AND COLUMN_NAME = :column'
        );
        $stmt->execute(['table' => $table, 'column' => $column]);
        return (int) $stmt->fetchColumn() > 0;
    }

    private function indexExists(string $table, string $index): bool
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table AND INDEX_NAME = :index'
        );
        $stmt->execute(['table' => $table, 'index' => $index]);
        return (int) $stmt->fetchColumn() > 0;
    }
}
