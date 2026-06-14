<?php

declare(strict_types=1);

class CompanyEmailTemplate
{
    private Setting $settings;

    public function __construct(?Setting $settings = null)
    {
        $this->settings = $settings ?? new Setting();
    }

    public function all(): array
    {
        return [
            'signature' => $this->settings->value('email_signature_body', $this->defaultSignature()),
            'contract' => $this->template('contract'),
            'payslip' => $this->template('payslip'),
        ];
    }

    public function template(string $type): array
    {
        return [
            'subject' => $this->settings->value("email_template_{$type}_subject", $this->defaultSubject($type)),
            'body' => $this->settings->value("email_template_{$type}_body", $this->defaultBody($type)),
            'attach_document' => $this->settings->value("email_template_{$type}_attach_document", '1') === '1',
        ];
    }

    public function save(array $data): void
    {
        foreach (['contract', 'payslip'] as $type) {
            $subject = trim((string) ($data[$type . '_subject'] ?? ''));
            $body = trim((string) ($data[$type . '_body'] ?? ''));

            $this->settings->upsert("email_template_{$type}_subject", $subject !== '' ? $subject : $this->defaultSubject($type));
            $this->settings->upsert("email_template_{$type}_body", $body !== '' ? $body : $this->defaultBody($type));
            $this->settings->upsert("email_template_{$type}_attach_document", !empty($data[$type . '_attach_document']) ? '1' : '0');
        }

        $signature = trim((string) ($data['signature'] ?? ''));
        $this->settings->upsert('email_signature_body', $signature !== '' ? $signature : $this->defaultSignature());
    }

    public function renderSubject(string $type, array $tokens): string
    {
        return $this->renderText((string) $this->template($type)['subject'], $tokens);
    }

    public function renderBody(string $type, array $tokens): string
    {
        $template = $this->template($type);
        $message = $this->paragraphHtml($this->renderText((string) $template['body'], $tokens));
        $signature = $this->paragraphHtml($this->renderText($this->settings->value('email_signature_body', $this->defaultSignature()), $tokens));
        $company = htmlspecialchars((string) ($tokens['company_name'] ?? app_product_name()), ENT_QUOTES, 'UTF-8');

        return <<<HTML
        <html><body style="font-family:Arial,sans-serif;color:#1f2937;background:#f8fafc;padding:24px">
            <div style="max-width:680px;margin:0 auto;background:#fff;border:1px solid #e5e7eb;border-radius:8px;overflow:hidden">
                <div style="background:#1a3a2a;color:#fff;padding:18px 22px">
                    <h2 style="margin:0;font-size:20px">{$company}</h2>
                    <div style="opacity:.85;font-size:13px">Official HR & Payroll Communication</div>
                </div>
                <div style="padding:22px;line-height:1.6">
                    {$message}
                    <div style="border-top:1px solid #e5e7eb;margin-top:22px;padding-top:16px;color:#334155">
                        {$signature}
                    </div>
                    <p style="font-size:12px;color:#64748b;margin-top:18px">This email was sent by {$company} through Corevia HR & Payroll.</p>
                </div>
            </div>
        </body></html>
        HTML;
    }

    public function renderText(string $text, array $tokens): string
    {
        $replace = [];
        foreach ($tokens as $key => $value) {
            $replace['{{' . $key . '}}'] = (string) $value;
        }

        return strtr($text, $replace);
    }

    public function tokens(): array
    {
        return [
            'General' => [
                '{{company_name}}' => 'Company name',
                '{{company_email}}' => 'Company email',
                '{{company_phone}}' => 'Company phone',
                '{{company_address}}' => 'Company address',
                '{{employee_name}}' => 'Employee name',
                '{{employee_number}}' => 'Employee number',
                '{{employee_email}}' => 'Employee email',
                '{{today}}' => 'Today',
            ],
            'Contract' => [
                '{{contract_number}}' => 'Contract number',
                '{{contract_type}}' => 'Contract type',
                '{{contract_start_date}}' => 'Start date',
                '{{contract_end_date}}' => 'End date',
            ],
            'Payslip' => [
                '{{pay_period}}' => 'Pay period',
                '{{gross_pay}}' => 'Gross pay',
                '{{total_deductions}}' => 'Total deductions',
                '{{net_pay}}' => 'Net pay',
            ],
        ];
    }

    public function defaultSignature(): string
    {
        return "Regards,\n{{company_name}}\n{{company_phone}}\n{{company_email}}";
    }

    private function defaultSubject(string $type): string
    {
        return $type === 'payslip'
            ? 'Payslip for {{pay_period}} - {{employee_name}}'
            : 'Employment Contract - {{employee_name}}';
    }

    private function defaultBody(string $type): string
    {
        if ($type === 'payslip') {
            return "Dear {{employee_name}},\n\nYour payslip for {{pay_period}} is ready. Please find the attached payslip document for your records.\n\nNet pay: {{net_pay}}.";
        }

        return "Dear {{employee_name}},\n\nPlease find your employment contract attached for your review and records.\n\nContract number: {{contract_number}}\nContract type: {{contract_type}}";
    }

    private function paragraphHtml(string $text): string
    {
        $escaped = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
        $blocks = preg_split("/\R{2,}/", trim($escaped)) ?: [];
        $html = '';
        foreach ($blocks as $block) {
            $html .= '<p style="margin:0 0 12px">' . nl2br($block) . '</p>';
        }
        return $html !== '' ? $html : '<p style="margin:0 0 12px"></p>';
    }
}
