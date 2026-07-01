<?php

declare(strict_types=1);

/**
 * Contract template model — manages dynamic contract templates.
 */
class ContractTemplate extends Model
{
    protected string $table    = 'contract_templates';
    protected bool   $tenantScoped = true;

    public function __construct()
    {
        parent::__construct();
        $this->ensureExpandedTemplateSchema();
    }

    /** All supported auto-filled field keys (without braces). */
    public const TOKENS = [
        'employee_name',
        'employee_number',
        'employee_nrc',
        'employee_phone',
        'employee_email',
        'employee_address',
        'employee_date_of_birth',
        'employee_gender',
        'napsa_number',
        'tpin',
        'nhima_number',
        'designation',
        'department',
        'department_code',
        'employment_type',
        'hire_date',
        'probation_end_date',
        'branch_name',
        'branch_code',
        'branch_address',
        'branch_phone',
        'branch_email',
        'place_of_work',
        'client_entity_name',
        'client_entity_code',
        'contract_number',
        'contract_type',
        'start_date',
        'end_date',
        'contract_period',
        'salary_structure',
        'salary_grade',
        'standard_basic_salary',
        'agreed_basic_salary',
        'salary_variance',
        'basic_salary',
        'housing_allowance',
        'transport_allowance',
        'other_allowances',
        'gross_salary',
        'total_allowances',
        'bank_name',
        'bank_account_number',
        'company_name',
        'company_logo_url',
        'company_address',
        'company_phone',
        'company_email',
        'company_registration_number',
        'company_tpin',
        'company_napsa_number',
        'company_nhima_number',
        'authorized_representative_name',
        'authorized_representative_title',
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
            'employee_date_of_birth' => 'Date of Birth',
            'employee_gender' => 'Gender',
            'napsa_number' => 'NAPSA Number',
            'tpin' => 'TPIN',
            'nhima_number' => 'NHIMA Number',
            'designation' => 'Job Title',
            'department' => 'Department',
            'department_code' => 'Department Code',
            'employment_type' => 'Employment Type',
            'hire_date' => 'Original Hire Date',
            'probation_end_date' => 'Probation End Date',
        ],
        'Organisation & Work Location' => [
            'branch_name' => 'Branch Name',
            'branch_code' => 'Branch Code',
            'branch_address' => 'Branch Address',
            'branch_phone' => 'Branch Phone',
            'branch_email' => 'Branch Email',
            'place_of_work' => 'Place of Work',
            'client_entity_name' => 'Group / Client Entity',
            'client_entity_code' => 'Entity Code',
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
            'salary_grade' => 'Salary Grade',
            'standard_basic_salary' => 'Standard Basic Salary',
            'agreed_basic_salary' => 'Agreed Basic Salary',
            'salary_variance' => 'Salary Variance',
            'basic_salary' => 'Basic Salary',
            'housing_allowance' => 'Housing Allowance',
            'transport_allowance' => 'Transport Allowance',
            'other_allowances' => 'Other Allowances',
            'gross_salary' => 'Gross Salary',
            'total_allowances' => 'Total Allowances',
            'bank_name' => 'Bank Name',
            'bank_account_number' => 'Bank Account Number',
        ],
        'Company Details' => [
            'company_name' => 'Company Name',
            'company_logo_url' => 'Company Logo',
            'company_address' => 'Company Address',
            'company_phone' => 'Company Phone',
            'company_email' => 'Company Email',
            'company_registration_number' => 'Company Registration Number',
            'company_tpin' => 'Company TPIN',
            'company_napsa_number' => 'Company NAPSA Number',
            'company_nhima_number' => 'Company NHIMA Number',
            'authorized_representative_name' => 'Authorized Representative',
            'authorized_representative_title' => 'Representative Title',
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
                       ss.name AS structure_name, b.name AS branch_name
                FROM contract_templates ct
                LEFT JOIN salary_structures ss ON ss.id = ct.salary_structure_id
                LEFT JOIN branches b ON b.id = ct.branch_id
                WHERE ct.company_id = :cid
                ORDER BY ct.is_default DESC, ct.name ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['cid' => \Tenant::id()]);

        return $stmt->fetchAll();
    }

    public function findDetailed(int $id): ?array
    {
        $sql = "SELECT ct.*,
                       ss.name AS structure_name, b.name AS branch_name
                FROM contract_templates ct
                LEFT JOIN salary_structures ss ON ss.id = ct.salary_structure_id
                LEFT JOIN branches b ON b.id = ct.branch_id
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
    public function resolve(?int $salaryStructureId, ?string $contractType, ?int $branchId = null): ?array
    {
        $cid = \Tenant::id();

        if ($branchId) {
            foreach ([
                [$salaryStructureId, $contractType],
                [null, $contractType],
                [$salaryStructureId, null],
                [null, null],
            ] as [$structure, $type]) {
                $matched = $this->findBranchMatch($cid, $branchId, $structure, $type);
                if ($matched) return $matched;
            }
        }

        // 1. Exact match
        if ($salaryStructureId && $contractType) {
            $sql  = "SELECT * FROM contract_templates
                     WHERE company_id = :cid
                       AND salary_structure_id = :sid
                       AND contract_type = :type
                       AND branch_id IS NULL
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
                       AND branch_id IS NULL
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
                       AND branch_id IS NULL
                     LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['cid' => $cid, 'type' => $contractType]);
            $row  = $stmt->fetch();
            if ($row) return $row;
        }

        // 4. Default
        $sql  = "SELECT * FROM contract_templates
                 WHERE company_id = :cid AND is_default = 1 AND branch_id IS NULL
                 LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['cid' => $cid]);
        $row  = $stmt->fetch();

        return $row ?: null;
    }

    private function findBranchMatch(int $companyId, int $branchId, ?int $salaryStructureId, ?string $contractType): ?array
    {
        $sql = 'SELECT * FROM contract_templates WHERE company_id = :cid AND branch_id = :bid';
        $params = ['cid' => $companyId, 'bid' => $branchId];

        if ($salaryStructureId) {
            $sql .= ' AND salary_structure_id = :sid';
            $params['sid'] = $salaryStructureId;
        } else {
            $sql .= ' AND salary_structure_id IS NULL';
        }

        if ($contractType !== null && $contractType !== '') {
            $sql .= ' AND contract_type = :type';
            $params['type'] = $contractType;
        } else {
            $sql .= ' AND contract_type IS NULL';
        }

        $stmt = $this->db->prepare($sql . ' ORDER BY id DESC LIMIT 1');
        $stmt->execute($params);
        $row = $stmt->fetch();
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
            } elseif ($token === 'company_logo_url') {
                $replacement = '<img src="' . e($value) . '" alt="Company logo" style="max-height:80px;max-width:220px;object-fit:contain;">';
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
        $totalAllowances   = $housingAllowance + $transportAllowance + $otherAllowances;
        $standardBasic     = (float) ($employee['structure_basic_pay'] ?? $basicSalary);
        $salaryVariance    = $basicSalary - $standardBasic;
        $structureName     = (string) ($employee['salary_structure_name'] ?? $employee['structure_name'] ?? '');
        $company           = $this->companyDetails();
        $settings          = new Setting();

        return [
            'employee_name'    => (string) ($employee['full_name']       ?? ''),
            'employee_number'  => (string) ($employee['employee_number'] ?? ''),
            'employee_nrc'     => (string) ($employee['nrc_number'] ?? ''),
            'employee_phone'   => (string) ($employee['phone'] ?? ''),
            'employee_email'   => (string) ($employee['email'] ?? ''),
            'employee_address' => (string) ($employee['address'] ?? ''),
            'employee_date_of_birth' => $this->dateOrBlank((string) ($employee['date_of_birth'] ?? '')),
            'employee_gender'  => (string) ($employee['gender_name'] ?? $employee['gender'] ?? ''),
            'napsa_number'     => (string) ($employee['napsa_number'] ?? ''),
            'tpin'             => (string) ($employee['tpin'] ?? ''),
            'nhima_number'     => (string) ($employee['nhima_number'] ?? ''),
            'designation'      => (string) ($employee['designation']     ?? ''),
            'department'       => (string) ($employee['department_name'] ?? ''),
            'department_code'  => (string) ($employee['department_code'] ?? ''),
            'employment_type'  => (string) ($employee['employment_type'] ?? $contract['contract_type'] ?? ''),
            'hire_date'        => $this->dateOrBlank((string) ($employee['hired_at'] ?? '')),
            'probation_end_date' => $this->dateOrBlank((string) ($employee['probation_end_date'] ?? '')),
            'branch_name'      => (string) ($employee['branch_name'] ?? ''),
            'branch_code'      => (string) ($employee['branch_code'] ?? ''),
            'branch_address'   => (string) ($employee['branch_address'] ?? ''),
            'branch_phone'     => (string) ($employee['branch_phone'] ?? ''),
            'branch_email'     => (string) ($employee['branch_email'] ?? ''),
            'place_of_work'    => (string) ($employee['branch_address'] ?? $employee['branch_name'] ?? $company['address'] ?? ''),
            'client_entity_name' => (string) ($employee['client_entity_name'] ?? $company['client_entity_name'] ?? ''),
            'client_entity_code' => (string) ($employee['client_entity_code'] ?? $company['client_entity_code'] ?? ''),
            'contract_number'  => (string) ($contract['contract_number'] ?? ''),
            'contract_type'    => (string) ($contract['contract_type']   ?? ''),
            'start_date'       => $startDate !== '' ? date('j F Y', strtotime($startDate)) : '________________',
            'end_date'         => $endDate   !== '' ? date('j F Y', strtotime($endDate))   : 'No fixed expiry',
            'contract_period'  => $this->contractPeriodText($startDate, $endDate),
            'salary_structure' => $structureName,
            'salary_grade'     => (string) ($employee['grade_level'] ?? ''),
            'standard_basic_salary' => $this->moneyOrBlank($standardBasic),
            'agreed_basic_salary' => $this->moneyOrBlank($basicSalary),
            'salary_variance'  => $salaryVariance === 0.0 ? 'ZMW 0.00' : (($salaryVariance > 0 ? '+' : '-') . $this->moneyOrBlank(abs($salaryVariance))),
            'basic_salary'     => $this->moneyOrBlank($basicSalary),
            'housing_allowance'=> $this->moneyOrBlank($housingAllowance),
            'transport_allowance' => $this->moneyOrBlank($transportAllowance),
            'other_allowances' => $this->moneyOrBlank($otherAllowances),
            'gross_salary'     => $this->moneyOrBlank($grossSalary),
            'total_allowances' => $this->moneyOrBlank($totalAllowances),
            'bank_name'        => (string) ($employee['bank_name'] ?? ''),
            'bank_account_number' => (string) ($employee['bank_account_number'] ?? ''),
            'company_name'     => (string) ($company['name'] ?? ''),
            'company_logo_url' => company_logo_url($company),
            'company_address'  => (string) ($company['address'] ?? ''),
            'company_phone'    => (string) ($company['phone'] ?? ''),
            'company_email'    => (string) ($company['email'] ?? ''),
            'company_registration_number' => $settings->value('company_registration_number', ''),
            'company_tpin'     => $settings->value('statutory_tpin', ''),
            'company_napsa_number' => $settings->value('statutory_napsa_account_number', ''),
            'company_nhima_number' => $settings->value('statutory_nhima_employer_number', ''),
            'authorized_representative_name' => $settings->value('document_default_signatory_name', 'Authorized Representative'),
            'authorized_representative_title' => $settings->value('document_default_signatory_title', 'Authorized Representative'),
            'headteacher_name' => $settings->value('document_default_signatory_name', 'Authorized Representative'),
            'director_name'    => $settings->value('document_default_signatory_name', 'Managing Director'),
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
             (template_id, company_id, branch_id, version, name, salary_structure_id, contract_type, cover_body, body, signature_body, footer_body, is_default, changed_by)
             VALUES (:template_id, :company_id, :branch_id, :version, :name, :salary_structure_id, :contract_type, :cover_body, :body, :signature_body, :footer_body, :is_default, :changed_by)"
        );
        $stmt->execute([
            'template_id' => (int) $template['id'],
            'company_id' => (int) $template['company_id'],
            'branch_id' => !empty($template['branch_id']) ? (int) $template['branch_id'] : null,
            'version' => $version,
            'name' => (string) $template['name'],
            'salary_structure_id' => $template['salary_structure_id'] !== null ? (int) $template['salary_structure_id'] : null,
            'contract_type' => $template['contract_type'] ?? null,
            'cover_body' => $template['cover_body'] ?? null,
            'body' => (string) $template['body'],
            'signature_body' => $template['signature_body'] ?? null,
            'footer_body' => $template['footer_body'] ?? null,
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
        $stmt = $this->db->prepare(
            "SELECT c.*, ce.name AS client_entity_name, ce.code AS client_entity_code
             FROM companies c
             LEFT JOIN client_entities ce ON ce.id = c.client_entity_id
             WHERE c.id = :id LIMIT 1"
        );
        $stmt->execute(['id' => \Tenant::id()]);

        return $stmt->fetch() ?: [];
    }

    private function moneyOrBlank(float $amount): string
    {
        return $amount > 0 ? 'ZMW ' . number_format($amount, 2) : '';
    }

    private function dateOrBlank(string $date): string
    {
        return $date !== '' && strtotime($date) !== false ? date('j F Y', strtotime($date)) : '';
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

    private function ensureExpandedTemplateSchema(): void
    {
        foreach ([
            'branch_id' => 'BIGINT UNSIGNED NULL',
            'cover_body' => 'LONGTEXT NULL',
            'signature_body' => 'LONGTEXT NULL',
            'footer_body' => 'LONGTEXT NULL',
        ] as $column => $definition) {
            if (!$this->columnExists('contract_templates', $column)) {
                $this->db->exec("ALTER TABLE contract_templates ADD COLUMN {$column} {$definition}");
            }
        }
        if ($this->tableExists('contract_template_versions')) {
            foreach ([
                'branch_id' => 'BIGINT UNSIGNED NULL',
                'cover_body' => 'LONGTEXT NULL',
                'signature_body' => 'LONGTEXT NULL',
                'footer_body' => 'LONGTEXT NULL',
            ] as $column => $definition) {
                if (!$this->columnExists('contract_template_versions', $column)) {
                    $this->db->exec("ALTER TABLE contract_template_versions ADD COLUMN {$column} {$definition}");
                }
            }
        }
    }

    private function columnExists(string $table, string $column): bool
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM information_schema.columns
             WHERE table_schema = DATABASE() AND table_name = :table AND column_name = :column'
        );
        $stmt->execute(['table' => $table, 'column' => $column]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function salaryStructures(): array
    {
        $stmt = $this->db->prepare(
            "SELECT id, name FROM salary_structures WHERE company_id = :cid ORDER BY name ASC"
        );
        $stmt->execute(['cid' => \Tenant::id()]);

        return $stmt->fetchAll();
    }

    public function branches(): array
    {
        $stmt = $this->db->prepare(
            'SELECT id, name, code FROM branches WHERE company_id = :cid AND is_active = 1 ORDER BY name ASC'
        );
        $stmt->execute(['cid' => \Tenant::id()]);
        return $stmt->fetchAll();
    }

    public function professionalDefaultBody(): string
    {
        return '<div style="text-align:center;border-bottom:2px solid #111827;padding-bottom:14px;margin-bottom:24px;">'
            . '<div>{{company_logo_url}}</div><h1>{{company_name}}</h1><p>{{company_address}} | {{company_phone}} | {{company_email}}</p></div>'
            . '<p style="text-align:right;">Date: {{today_date}}</p><h2 style="text-align:center;">EMPLOYMENT CONTRACT</h2>'
            . '<p>This Employment Contract is made between <strong>{{company_name}}</strong> and <strong>{{employee_name}}</strong> (Employee No. {{employee_number}}, NRC {{employee_nrc}}).</p>'
            . '<h3>1. Appointment</h3><p>The Employee is appointed as <strong>{{designation}}</strong> in the {{department}} department under {{employment_type}} employment, effective {{start_date}}.</p>'
            . '<p>The primary place of work is {{place_of_work}} ({{branch_name}}). The Employee may be reasonably assigned to another company location in accordance with operational requirements and applicable law.</p>'
            . '<h3>2. Contract Term</h3><p>This contract runs from {{start_date}} to {{end_date}} for {{contract_period}}, subject to the probation period of {{probation_period}}.</p>'
            . '<h3>3. Remuneration</h3><p>The Employee will receive the following monthly remuneration before statutory and authorised deductions:</p>'
            . '<table><thead><tr><th>Remuneration Component</th><th>Monthly Amount</th></tr></thead><tbody>'
            . '<tr><td>Agreed Basic Salary</td><td>{{agreed_basic_salary}}</td></tr><tr><td>Housing Allowance</td><td>{{housing_allowance}}</td></tr>'
            . '<tr><td>Transport Allowance</td><td>{{transport_allowance}}</td></tr><tr><td>Other Allowances</td><td>{{other_allowances}}</td></tr>'
            . '<tr><th>Gross Monthly Remuneration</th><th>{{gross_salary}}</th></tr></tbody></table>'
            . '<h3>4. Working Hours and Leave</h3><p>Normal working hours are {{working_hours}}. Leave entitlement is {{leave_days}}, administered under company policy and applicable labour law.</p>'
            . '<h3>5. Statutory Registration</h3><p>Employee NAPSA: {{napsa_number}} | NHIMA: {{nhima_number}} | TPIN: {{tpin}}.</p>'
            . '<h3>6. Gratuity and Termination</h3><p>Gratuity: {{gratuity_rate}}. Notice period: {{notice_period}}. Termination remains subject to applicable law, disciplinary procedure, and approved company policy.</p>'
            . '<h3>7. Acceptance</h3><p>By signing below, both parties confirm that they understand and accept the terms of this contract.</p>';
    }

    public function professionalDefaultCover(): string
    {
        return '<div class="cover-content"><div>{{company_logo_url}}</div><div class="cover-company">{{company_name}}</div><div class="cover-rule"></div>'
            . '<div class="cover-title">Employment Contract</div><div class="cover-employee">{{employee_name}}</div>'
            . '<div class="cover-meta">Employee No: {{employee_number}}<br>Position: {{designation}}<br>Contract Reference: {{contract_number}}<br>Effective Date: {{start_date}}</div></div>';
    }

    public function professionalDefaultSignature(): string
    {
        return '<table class="signature-table"><tr><th>Employee Acceptance</th><th>For the Employer</th></tr><tr>'
            . '<td>Signature: ____________________<br><br>Name: {{employee_name}}<br><br>Date: ____________________</td>'
            . '<td>Signature: ____________________<br><br>Name: {{authorized_representative_name}}<br>Title: {{authorized_representative_title}}<br><br>Date: ____________________</td></tr></table>';
    }

    public function professionalDefaultFooter(): string
    {
        return '{{company_name}} | {{company_address}} | {{company_phone}} | {{company_email}}';
    }
}

