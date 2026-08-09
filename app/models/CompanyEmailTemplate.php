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
        $subject = $this->settings->value("email_template_{$type}_subject", $this->defaultSubject($type));
        $body = $this->settings->value("email_template_{$type}_body", $this->defaultBody($type));

        return [
            'subject' => $this->portalOnlySubject($type, $subject),
            'body' => $this->portalOnlyBody($type, $body),
            'attach_document' => false,
        ];
    }

    public function save(array $data): void
    {
        foreach (['contract', 'payslip'] as $type) {
            $subject = trim((string) ($data[$type . '_subject'] ?? ''));
            $body = trim((string) ($data[$type . '_body'] ?? ''));

            $this->settings->upsert("email_template_{$type}_subject", $subject !== '' ? $subject : $this->defaultSubject($type));
            $this->settings->upsert("email_template_{$type}_body", $body !== '' ? $body : $this->defaultBody($type));
            $this->settings->upsert("email_template_{$type}_attach_document", '0');
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
                '{{portal_url}}' => 'Employee portal login link',
            ],
            'Contract' => [
                '{{contract_number}}' => 'Contract number',
                '{{contract_type}}' => 'Contract type',
                '{{contract_start_date}}' => 'Start date',
                '{{contract_end_date}}' => 'End date',
                '{{contract_url}}' => 'Employee portal contract page',
            ],
            'Payslip' => [
                '{{pay_period}}' => 'Pay period',
                '{{gross_pay}}' => 'Gross pay',
                '{{total_deductions}}' => 'Total deductions',
                '{{net_pay}}' => 'Net pay',
                '{{payslip_url}}' => 'Employee portal payslip page',
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
            ? 'Payslip available in your employee portal - {{pay_period}}'
            : 'Employment contract available in your employee portal';
    }

    private function defaultBody(string $type): string
    {
        if ($type === 'payslip') {
            return "Dear {{employee_name}},\n\nYour payslip for {{pay_period}} is now available in your employee self-service portal.\n\nFor confidentiality and security, payslip documents are not sent by email. Please log in to your portal to view or download your payslip.\n\nPortal link: {{payslip_url}}\n\nNet pay: {{net_pay}}.";
        }

        return "Dear {{employee_name}},\n\nYour employment contract is now available in your employee self-service portal for review.\n\nFor confidentiality and security, contract documents are not sent by email. Please log in to your portal to view the contract details and any related actions.\n\nPortal link: {{contract_url}}\n\nContract number: {{contract_number}}\nContract type: {{contract_type}}";
    }

    private function portalOnlySubject(string $type, string $subject): string
    {
        if (!in_array($type, ['contract', 'payslip'], true)) {
            return $subject;
        }

        return $this->mentionsEmailAttachment($subject) ? $this->defaultSubject($type) : $subject;
    }

    private function portalOnlyBody(string $type, string $body): string
    {
        if (!in_array($type, ['contract', 'payslip'], true)) {
            return $body;
        }

        return $this->mentionsEmailAttachment($body) ? $this->defaultBody($type) : $body;
    }

    private function mentionsEmailAttachment(string $text): bool
    {
        $normalized = strtolower($text);
        return str_contains($normalized, 'attached')
            || str_contains($normalized, 'attachment')
            || str_contains($normalized, 'find your')
            || str_contains($normalized, 'please find');
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
