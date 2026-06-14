<?php

declare(strict_types=1);

class EmployeeLetterTemplate extends Model
{
    protected string $table = 'employee_letter_templates';
    protected bool $tenantScoped = true;

    public const TYPES = [
        'Employment Certificate',
        'Promotion Letter',
        'Transfer Letter',
        'Confirmation Letter',
        'Termination Letter',
        'Final Dues Statement',
    ];

    public const TOKENS = [
        'Company Logo' => '{{company_logo}}',
        'Company Name' => '{{company_name}}',
        'Company Address' => '{{company_address}}',
        'Today' => '{{today}}',
        'Employee Name' => '{{employee_name}}',
        'Employee Number' => '{{employee_number}}',
        'Employee Email' => '{{employee_email}}',
        'Employee Phone' => '{{employee_phone}}',
        'Department' => '{{department}}',
        'Designation' => '{{designation}}',
        'Employment Type' => '{{employment_type}}',
        'Hire Date' => '{{hire_date}}',
        'Probation End Date' => '{{probation_end_date}}',
        'Lifecycle Status' => '{{lifecycle_status}}',
        'Latest Event Type' => '{{latest_event_type}}',
        'Latest Event Date' => '{{latest_event_date}}',
        'Latest Event Notes' => '{{latest_event_notes}}',
        'New Department' => '{{new_department}}',
        'New Designation' => '{{new_designation}}',
        'Final Dues Net' => '{{final_dues_net}}',
        'Final Dues Notes' => '{{final_dues_notes}}',
    ];

    public static function fieldGroups(): array
    {
        return [
            'Company' => [
                '{{company_logo}}' => 'Company Logo',
                '{{company_name}}' => 'Company Name',
                '{{company_address}}' => 'Company Address',
                '{{today}}' => 'Today',
            ],
            'Employee' => [
                '{{employee_name}}' => 'Employee Name',
                '{{employee_number}}' => 'Employee Number',
                '{{employee_email}}' => 'Employee Email',
                '{{employee_phone}}' => 'Employee Phone',
                '{{department}}' => 'Department',
                '{{designation}}' => 'Designation',
                '{{employment_type}}' => 'Employment Type',
                '{{hire_date}}' => 'Hire Date',
                '{{probation_end_date}}' => 'Probation End Date',
                '{{lifecycle_status}}' => 'Lifecycle Status',
            ],
            'Lifecycle' => [
                '{{latest_event_type}}' => 'Latest Event',
                '{{latest_event_date}}' => 'Event Date',
                '{{latest_event_notes}}' => 'Event Notes',
                '{{new_department}}' => 'New Department',
                '{{new_designation}}' => 'New Designation',
            ],
            'Exit & Dues' => [
                '{{final_dues_net}}' => 'Final Dues Net',
                '{{final_dues_notes}}' => 'Final Dues Notes',
            ],
        ];
    }

    public function allTemplates(): array
    {
        $this->ensureDefaults();

        $stmt = $this->db->prepare(
            'SELECT * FROM employee_letter_templates
             WHERE company_id = :cid
             ORDER BY FIELD(letter_type, "Employment Certificate", "Promotion Letter", "Transfer Letter", "Confirmation Letter", "Termination Letter", "Final Dues Statement")'
        );
        $stmt->execute(['cid' => Tenant::id()]);

        return $stmt->fetchAll();
    }

    public function findByType(string $type): ?array
    {
        $this->ensureDefaults();

        $stmt = $this->db->prepare(
            'SELECT * FROM employee_letter_templates
             WHERE company_id = :cid AND letter_type = :letter_type
             LIMIT 1'
        );
        $stmt->execute(['cid' => Tenant::id(), 'letter_type' => $this->normalizeType($type)]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function upsertTemplate(string $type, string $title, string $body): void
    {
        $type = $this->normalizeType($type);
        $title = trim($title) !== '' ? trim($title) : $type;
        $body = trim($body) !== '' ? trim($body) : self::defaultBody($type);

        $this->db->prepare(
            'INSERT INTO employee_letter_templates (company_id, letter_type, title, body_html, version, updated_by)
             VALUES (:cid, :letter_type, :title, :body_html, 1, :updated_by)
             ON DUPLICATE KEY UPDATE
                title = VALUES(title),
                body_html = VALUES(body_html),
                version = version + 1,
                updated_by = VALUES(updated_by),
                updated_at = CURRENT_TIMESTAMP'
        )->execute([
            'cid' => Tenant::id(),
            'letter_type' => $type,
            'title' => $title,
            'body_html' => $body,
            'updated_by' => (int) (current_user()['id'] ?? 0) ?: null,
        ]);
    }

    public function render(string $type, array $employee, ?array $templateOverride = null): array
    {
        $template = $templateOverride ?? $this->findByType($type);
        $type = $this->normalizeType($type);
        $title = (string) ($template['title'] ?? $type);
        $body = (string) ($template['body_html'] ?? self::defaultBody($type));
        $tokens = $this->tokenValues($employee);

        return [
            'title' => $this->replaceTokens($title, $tokens),
            'body_html' => $this->replaceTokens($body, $tokens),
            'missing_tokens' => $this->missingTokens($body . ' ' . $title),
        ];
    }

    public function ensureDefaults(): void
    {
        if (Tenant::id() <= 0 || !$this->tableExists()) {
            return;
        }

        foreach (self::TYPES as $type) {
            $stmt = $this->db->prepare(
                'SELECT id FROM employee_letter_templates WHERE company_id = :cid AND letter_type = :letter_type LIMIT 1'
            );
            $stmt->execute(['cid' => Tenant::id(), 'letter_type' => $type]);
            if (!$stmt->fetchColumn()) {
                $this->insert([
                    'letter_type' => $type,
                    'title' => $type . ' - {{employee_name}}',
                    'body_html' => self::defaultBody($type),
                    'version' => 1,
                    'updated_by' => (int) (current_user()['id'] ?? 0) ?: null,
                ]);
            }
        }
    }

    private function tokenValues(array $employee): array
    {
        $company = current_company() ?? [];
        $latest = $employee['_sample_latest_event'] ?? null;
        $finalDue = $employee['_sample_final_due'] ?? null;
        if ((int) ($employee['id'] ?? 0) > 0) {
            $latest = (new EmployeeLifecycle())->latestForEmployee((int) ($employee['id'] ?? 0));
            $finalDue = (new EmployeeFinalDue())->latestForEmployee((int) ($employee['id'] ?? 0));
        }

        return [
            '{{company_logo}}' => '<img src="' . e(company_logo_url($company)) . '" alt="' . e((string) ($company['name'] ?? 'Company')) . ' logo" style="max-height:72px;max-width:160px;object-fit:contain;">',
            '{{company_name}}' => e((string) ($company['name'] ?? app_product_name())),
            '{{company_address}}' => nl2br(e((string) ($company['address'] ?? ''))),
            '{{today}}' => e(date('d M Y')),
            '{{employee_name}}' => e((string) ($employee['full_name'] ?? 'Employee')),
            '{{employee_number}}' => e((string) ($employee['employee_number'] ?? '')),
            '{{employee_email}}' => e((string) ($employee['email'] ?? '')),
            '{{employee_phone}}' => e((string) ($employee['phone'] ?? '')),
            '{{department}}' => e((string) ($employee['department_name'] ?? '')),
            '{{designation}}' => e((string) ($employee['designation'] ?? '')),
            '{{employment_type}}' => e((string) ($employee['employment_type'] ?? '')),
            '{{hire_date}}' => !empty($employee['hired_at']) ? e(format_date((string) $employee['hired_at'])) : '',
            '{{probation_end_date}}' => !empty($employee['probation_end_date']) ? e(format_date((string) $employee['probation_end_date'])) : '',
            '{{lifecycle_status}}' => e((string) ($employee['lifecycle_status'] ?? $employee['contract_status'] ?? '')),
            '{{latest_event_type}}' => e((string) ($latest['event_type'] ?? '')),
            '{{latest_event_date}}' => !empty($latest['effective_date']) ? e(format_date((string) $latest['effective_date'])) : '',
            '{{latest_event_notes}}' => nl2br(e((string) ($latest['notes'] ?? ''))),
            '{{new_department}}' => e((string) ($latest['to_department'] ?? '')),
            '{{new_designation}}' => e((string) ($latest['to_designation'] ?? '')),
            '{{final_dues_net}}' => $finalDue ? e(format_currency((float) ($finalDue['net_final_due'] ?? 0))) : '',
            '{{final_dues_notes}}' => $finalDue ? nl2br(e((string) ($finalDue['notes'] ?? ''))) : '',
        ];
    }

    private function replaceTokens(string $content, array $tokens): string
    {
        return str_replace(array_keys($tokens), array_values($tokens), $content);
    }

    private function missingTokens(string $content): array
    {
        preg_match_all('/{{\s*[^}]+\s*}}/', $content, $matches);
        $tokens = array_unique($matches[0] ?? []);
        $supported = array_values(self::TOKENS);

        return array_values(array_diff($tokens, $supported));
    }

    private function normalizeType(string $type): string
    {
        return in_array($type, self::TYPES, true) ? $type : 'Employment Certificate';
    }

    private function tableExists(): bool
    {
        try {
            $stmt = $this->db->query("SHOW TABLES LIKE 'employee_letter_templates'");
            return (bool) $stmt->fetchColumn();
        } catch (Throwable) {
            return false;
        }
    }

    public static function defaultBody(string $type): string
    {
        return match ($type) {
            'Promotion Letter' => self::letterShell('Promotion Letter', '<p>Dear {{employee_name}},</p><p>We are pleased to confirm your promotion to <strong>{{new_designation}}</strong> effective {{latest_event_date}}. This promotion follows the approved HR lifecycle record and reflects the company&apos;s confidence in your continued contribution.</p><p>Your department after this change is <strong>{{new_department}}</strong>. Any revised salary or benefit details will be communicated separately through the approved salary-change process.</p><p>Please accept our congratulations.</p>'),
            'Transfer Letter' => self::letterShell('Transfer Letter', '<p>Dear {{employee_name}},</p><p>This letter confirms your transfer to <strong>{{new_department}}</strong> effective {{latest_event_date}}. Your designation after the transfer is recorded as <strong>{{new_designation}}</strong>.</p><p>You are expected to complete handover requirements and report to the receiving department as directed by management.</p>'),
            'Confirmation Letter' => self::letterShell('Confirmation of Employment', '<p>Dear {{employee_name}},</p><p>We are pleased to confirm that you have successfully completed your probation period and your employment with {{company_name}} is now confirmed.</p><p>Your current designation is <strong>{{designation}}</strong> in the <strong>{{department}}</strong> department. All other terms of employment remain subject to company policy and your employment contract.</p>'),
            'Termination Letter' => self::letterShell('Termination Letter', '<p>Dear {{employee_name}},</p><p>This letter confirms the termination of your employment with {{company_name}} effective {{latest_event_date}}.</p><p>The reason and supporting notes recorded by HR are as follows:</p><p>{{latest_event_notes}}</p><p>Your final clearance, return of company property, and final dues calculation must be completed before closure of the employee file.</p>'),
            'Final Dues Statement' => self::letterShell('Final Dues Statement', '<p>This statement confirms the latest final dues calculation for {{employee_name}}, employee number {{employee_number}}.</p><p><strong>Net final dues payable:</strong> {{final_dues_net}}</p><p><strong>Notes:</strong><br>{{final_dues_notes}}</p><p>This statement should be read together with payroll, leave, gratuity, and clearance records.</p>'),
            default => self::letterShell('Employment Certificate', '<p>To whom it may concern,</p><p>This is to certify that <strong>{{employee_name}}</strong>, employee number <strong>{{employee_number}}</strong>, has been employed by {{company_name}} as <strong>{{designation}}</strong> in the <strong>{{department}}</strong> department from {{hire_date}}.</p><p>This certificate is issued upon request and is based on official HR records.</p>'),
        };
    }

    private static function letterShell(string $heading, string $body): string
    {
        return <<<HTML
<div class="letterhead" style="text-align:center;border-bottom:2px solid #111827;padding-bottom:14px;margin-bottom:24px;">
    <div>{{company_logo}}</div>
    <h1 style="margin:8px 0 2px;font-size:20px;text-transform:uppercase;">{{company_name}}</h1>
    <div style="font-size:12px;color:#64748b;">{{company_address}}</div>
</div>
<p style="text-align:right;">Date: {{today}}</p>
<h2 style="text-align:center;text-transform:uppercase;">{$heading}</h2>
{$body}
<br><br>
<div style="display:grid;grid-template-columns:1fr 1fr;gap:36px;margin-top:36px;">
    <div>
        <p>______________________________</p>
        <p><strong>Authorised Signatory</strong></p>
        <p>{{company_name}}</p>
    </div>
    <div>
        <p>______________________________</p>
        <p><strong>Employee Acknowledgement</strong></p>
        <p>{{employee_name}}</p>
    </div>
</div>
HTML;
    }
}
