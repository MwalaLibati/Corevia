<?php

declare(strict_types=1);

/**
 * Contract template model — manages dynamic contract templates.
 */
class ContractTemplate extends Model
{
    protected string $table    = 'contract_templates';
    protected bool   $tenantScoped = true;

    /** All supported auto-filled field keys (without braces). */
    public const TOKENS = [
        'employee_name',
        'employee_number',
        'employee_nrc',
        'employee_phone',
        'employee_email',
        'employee_address',
        'napsa_number',
        'tpin',
        'designation',
        'department',
        'contract_number',
        'contract_type',
        'start_date',
        'end_date',
        'contract_period',
        'salary_structure',
        'basic_salary',
        'housing_allowance',
        'transport_allowance',
        'other_allowances',
        'gross_salary',
        'bank_name',
        'bank_account_number',
        'company_name',
        'company_logo_url',
        'company_address',
        'company_phone',
        'company_email',
        'authorized_representative_name',
        'headteacher_name',
        'director_name',
        'probation_period',
        'working_hours',
        'leave_days',
        'gratuity_rate',
        'notice_period',
        'today_date',
        'notes',
    ];

    public const AUTO_FIELDS = [
        'Employee Details' => [
            'employee_name' => 'Employee Name',
            'employee_number' => 'Employee Number',
            'employee_nrc' => 'NRC Number',
            'employee_phone' => 'Phone Number',
            'employee_email' => 'Email Address',
            'employee_address' => 'Home Address',
            'napsa_number' => 'NAPSA Number',
            'tpin' => 'TPIN',
            'designation' => 'Job Title',
            'department' => 'Department',
        ],
        'Contract Details' => [
            'contract_number' => 'Contract Number',
            'contract_type' => 'Contract Type',
            'start_date' => 'Start Date',
            'end_date' => 'End Date',
            'contract_period' => 'Contract Period',
            'today_date' => 'Today\'s Date',
            'notes' => 'Contract Notes',
        ],
        'Pay Details' => [
            'salary_structure' => 'Salary Structure',
            'basic_salary' => 'Basic Salary',
            'housing_allowance' => 'Housing Allowance',
            'transport_allowance' => 'Transport Allowance',
            'other_allowances' => 'Other Allowances',
            'gross_salary' => 'Gross Salary',
            'bank_name' => 'Bank Name',
            'bank_account_number' => 'Bank Account Number',
        ],
        'Company Details' => [
            'company_name' => 'Company Name',
            'company_logo_url' => 'Company Logo',
            'company_address' => 'Company Address',
            'company_phone' => 'Company Phone',
            'company_email' => 'Company Email',
            'authorized_representative_name' => 'Authorized Representative',
            'headteacher_name' => 'Authorized Representative (Legacy)',
            'director_name' => 'Director Name',
            'probation_period' => 'Probation Period',
            'working_hours' => 'Working Hours',
            'leave_days' => 'Leave Days',
            'gratuity_rate' => 'Gratuity Rate',
            'notice_period' => 'Notice Period',
        ],
    ];

    // ─── Queries ─────────────────────────────────────────────────────────────

    public function listAll(): array
    {
        $sql = "SELECT ct.*,
                       ss.name AS structure_name
                FROM contract_templates ct
                LEFT JOIN salary_structures ss ON ss.id = ct.salary_structure_id
                WHERE ct.company_id = :cid
                ORDER BY ct.is_default DESC, ct.name ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['cid' => \Tenant::id()]);

        return $stmt->fetchAll();
    }

    public function findDetailed(int $id): ?array
    {
        $sql = "SELECT ct.*,
                       ss.name AS structure_name
                FROM contract_templates ct
                LEFT JOIN salary_structures ss ON ss.id = ct.salary_structure_id
                WHERE ct.id = :id AND ct.company_id = :cid
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id, 'cid' => \Tenant::id()]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function fieldGroups(): array
    {
        return self::AUTO_FIELDS;
    }

    public function fieldLabel(string $token): string
    {
        foreach (self::AUTO_FIELDS as $fields) {
            if (isset($fields[$token])) {
                return $fields[$token];
            }
        }

        return ucwords(str_replace('_', ' ', $token));
    }

    /**
     * Resolve the best-matching template for a given salary_structure_id + contract_type.
     * Priority:
     *   1. structure + type exact match
     *   2. structure match, any type  (contract_type IS NULL)
     *   3. type match, any structure  (salary_structure_id IS NULL)
     *   4. default template           (is_default = 1)
     */
    public function resolve(?int $salaryStructureId, ?string $contractType): ?array
    {
        $cid = \Tenant::id();

        // 1. Exact match
        if ($salaryStructureId && $contractType) {
            $sql  = "SELECT * FROM contract_templates
                     WHERE company_id = :cid
                       AND salary_structure_id = :sid
                       AND contract_type = :type
                     LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['cid' => $cid, 'sid' => $salaryStructureId, 'type' => $contractType]);
            $row  = $stmt->fetch();
            if ($row) return $row;
        }

        // 2. Structure only
        if ($salaryStructureId) {
            $sql  = "SELECT * FROM contract_templates
                     WHERE company_id = :cid
                       AND salary_structure_id = :sid
                       AND contract_type IS NULL
                     LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['cid' => $cid, 'sid' => $salaryStructureId]);
            $row  = $stmt->fetch();
            if ($row) return $row;
        }

        // 3. Type only
        if ($contractType) {
            $sql  = "SELECT * FROM contract_templates
                     WHERE company_id = :cid
                       AND salary_structure_id IS NULL
                       AND contract_type = :type
                     LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['cid' => $cid, 'type' => $contractType]);
            $row  = $stmt->fetch();
            if ($row) return $row;
        }

        // 4. Default
        $sql  = "SELECT * FROM contract_templates
                 WHERE company_id = :cid AND is_default = 1
                 LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['cid' => $cid]);
        $row  = $stmt->fetch();

        return $row ?: null;
    }

    /**
     * Replace all {{token}} placeholders in a template body with resolved values.
     */
    public function renderBody(string $body, array $values, bool $markMissing = false): string
    {
        foreach (self::TOKENS as $token) {
            $placeholder = '{{' . $token . '}}';
            $value = trim((string) ($values[$token] ?? ''));

            if ($value === '') {
                $replacement = $markMissing
                    ? '<span class="missing-field" title="Missing auto-filled detail">' . e($this->fieldLabel($token)) . ' missing</span>'
                    : '________________';
            } else {
                $replacement = e($value);
            }

            $body = str_replace($placeholder, $replacement, $body);
        }

        $body = preg_replace_callback('/\{\{\s*([A-Za-z0-9_]+)\s*\}\}/', function (array $match) use ($markMissing): string {
            $label = $this->fieldLabel((string) $match[1]);
            return $markMissing
                ? '<span class="missing-field" title="Unknown auto-filled detail">' . e($label) . ' unknown</span>'
                : '________________';
        }, $body) ?? $body;

        return $body;
    }

    public function missingFields(string $body, array $values): array
    {
        $missing = [];

        if (preg_match_all('/\{\{\s*([A-Za-z0-9_]+)\s*\}\}/', $body, $matches)) {
            foreach (array_unique($matches[1]) as $token) {
                if (!in_array($token, self::TOKENS, true) || trim((string) ($values[$token] ?? '')) === '') {
                    $missing[$token] = $this->fieldLabel($token);
                }
            }
        }

        return $missing;
    }

    /**
     * Build token value map from contract + employee data.
     */
    public function buildTokenValues(array $contract, array $employee): array
    {
        $startDate = (string) ($contract['start_date'] ?? '');
        $endDate   = (string) ($contract['end_date']   ?? '');

        $basicSalary       = (float) ($employee['basic_pay'] ?? 0);
        $housingAllowance  = (float) ($employee['housing_allowance'] ?? 0);
        $transportAllowance= (float) ($employee['transport_allowance'] ?? 0);
        $otherAllowances   = (float) ($employee['other_allowances'] ?? 0);
        $grossSalary       = $basicSalary + $housingAllowance + $transportAllowance + $otherAllowances;
        $structureName     = (string) ($employee['salary_structure_name'] ?? $employee['structure_name'] ?? '');
        $company           = $this->companyDetails();

        return [
            'employee_name'    => (string) ($employee['full_name']       ?? ''),
            'employee_number'  => (string) ($employee['employee_number'] ?? ''),
            'employee_nrc'     => (string) ($employee['nrc_number'] ?? ''),
            'employee_phone'   => (string) ($employee['phone'] ?? ''),
            'employee_email'   => (string) ($employee['email'] ?? ''),
            'employee_address' => (string) ($employee['address'] ?? ''),
            'napsa_number'     => (string) ($employee['napsa_number'] ?? ''),
            'tpin'             => (string) ($employee['tpin'] ?? ''),
            'designation'      => (string) ($employee['designation']     ?? ''),
            'department'       => (string) ($employee['department_name'] ?? ''),
            'contract_number'  => (string) ($contract['contract_number'] ?? ''),
            'contract_type'    => (string) ($contract['contract_type']   ?? ''),
            'start_date'       => $startDate !== '' ? date('j F Y', strtotime($startDate)) : '________________',
            'end_date'         => $endDate   !== '' ? date('j F Y', strtotime($endDate))   : 'No fixed expiry',
            'contract_period'  => $this->contractPeriodText($startDate, $endDate),
            'salary_structure' => $structureName,
            'basic_salary'     => $this->moneyOrBlank($basicSalary),
            'housing_allowance'=> $this->moneyOrBlank($housingAllowance),
            'transport_allowance' => $this->moneyOrBlank($transportAllowance),
            'other_allowances' => $this->moneyOrBlank($otherAllowances),
            'gross_salary'     => $this->moneyOrBlank($grossSalary),
            'bank_name'        => (string) ($employee['bank_name'] ?? ''),
            'bank_account_number' => (string) ($employee['bank_account_number'] ?? ''),
            'company_name'     => (string) ($company['name'] ?? ''),
            'company_logo_url' => company_logo_url($company),
            'company_address'  => (string) ($company['address'] ?? ''),
            'company_phone'    => (string) ($company['phone'] ?? ''),
            'company_email'    => (string) ($company['email'] ?? ''),
            'authorized_representative_name' => (string) ($company['authorized_representative_name'] ?? $company['headteacher_name'] ?? 'Authorized Representative'),
            'headteacher_name' => (string) ($company['headteacher_name'] ?? $company['authorized_representative_name'] ?? 'Authorized Representative'),
            'director_name'    => (string) ($company['director_name'] ?? 'Managing Director'),
            'probation_period' => (string) ($contract['probation_period'] ?? 'three (3) months'),
            'working_hours'    => (string) ($contract['working_hours'] ?? 'Monday to Friday, 07:00 hours to 16:30 hours'),
            'leave_days'       => (string) ($contract['leave_days'] ?? 'as provided under applicable labour laws'),
            'gratuity_rate'    => (string) ($contract['gratuity_rate'] ?? '5% of annual basic salary for each completed year served'),
            'notice_period'    => (string) ($contract['notice_period'] ?? 'ninety (90) days'),
            'today_date'       => date('j F Y'),
            'notes'            => (string) ($contract['notes'] ?? ''),
        ];
    }

    public function archiveVersion(array $template, ?int $userId = null): void
    {
        if (!$this->tableExists('contract_template_versions')) {
            return;
        }

        $version = (int) ($template['version'] ?? 1);
        $stmt = $this->db->prepare(
            "INSERT INTO contract_template_versions
             (template_id, company_id, version, name, salary_structure_id, contract_type, body, is_default, changed_by)
             VALUES (:template_id, :company_id, :version, :name, :salary_structure_id, :contract_type, :body, :is_default, :changed_by)"
        );
        $stmt->execute([
            'template_id' => (int) $template['id'],
            'company_id' => (int) $template['company_id'],
            'version' => $version,
            'name' => (string) $template['name'],
            'salary_structure_id' => $template['salary_structure_id'] !== null ? (int) $template['salary_structure_id'] : null,
            'contract_type' => $template['contract_type'] ?? null,
            'body' => (string) $template['body'],
            'is_default' => (int) ($template['is_default'] ?? 0),
            'changed_by' => $userId ?: null,
        ]);
    }

    public function versions(int $templateId): array
    {
        if (!$this->tableExists('contract_template_versions')) {
            return [];
        }

        $stmt = $this->db->prepare(
            "SELECT ctv.*, u.full_name AS changed_by_name
             FROM contract_template_versions ctv
             LEFT JOIN users u ON u.id = ctv.changed_by
             WHERE ctv.template_id = :template_id AND ctv.company_id = :cid
             ORDER BY ctv.version DESC, ctv.id DESC"
        );
        $stmt->execute(['template_id' => $templateId, 'cid' => Tenant::id()]);

        return $stmt->fetchAll();
    }

    private function companyDetails(): array
    {
        $stmt = $this->db->prepare("SELECT * FROM companies WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => \Tenant::id()]);

        return $stmt->fetch() ?: [];
    }

    private function moneyOrBlank(float $amount): string
    {
        return $amount > 0 ? 'ZMW ' . number_format($amount, 2) : '';
    }

    private function contractPeriodText(string $startDate, string $endDate): string
    {
        if ($startDate === '' || $endDate === '') {
            return '';
        }

        $start = date_create($startDate);
        $end = date_create($endDate);
        if (!$start || !$end) {
            return '';
        }

        $diff = $start->diff($end);
        $months = $diff->y * 12 + $diff->m;
        if ($months <= 0) {
            return '';
        }

        return $months . ' month' . ($months === 1 ? '' : 's');
    }

    private function tableExists(string $table): bool
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :table"
            );
            $stmt->execute(['table' => $table]);
            return (int) $stmt->fetchColumn() > 0;
        } catch (Throwable) {
            return false;
        }
    }

    public function salaryStructures(): array
    {
        $stmt = $this->db->prepare(
            "SELECT id, name FROM salary_structures WHERE company_id = :cid ORDER BY name ASC"
        );
        $stmt->execute(['cid' => \Tenant::id()]);

        return $stmt->fetchAll();
    }
}

