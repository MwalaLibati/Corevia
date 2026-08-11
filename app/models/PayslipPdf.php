<?php

declare(strict_types=1);

class PayslipPdf
{
    private array $objects = [];
    private string $content = '';
    private int $fontSize = 10;

    public static function download(array $payload, string $filename): void
    {
        $pdf = new self();
        $bytes = $pdf->render($payload);
        $safeName = trim((string) preg_replace('/[^A-Za-z0-9._-]/', '_', $filename), '._');
        if ($safeName === '') {
            $safeName = 'payslip.pdf';
        }
        if (!str_ends_with(strtolower($safeName), '.pdf')) {
            $safeName .= '.pdf';
        }

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $safeName . '"');
        header('Content-Length: ' . strlen($bytes));
        echo $bytes;
        exit;
    }

    public function render(array $payload): string
    {
        $this->objects = [];
        $this->content = '';

        $this->drawPayslip($payload);

        $contentObject = $this->addObject("<< /Length " . strlen($this->content) . " >>\nstream\n{$this->content}endstream");
        $fontObject = $this->addObject('<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>');
        $boldFontObject = $this->addObject('<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>');
        $pageObject = $this->addObject("<< /Type /Page /Parent 5 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 {$fontObject} 0 R /F2 {$boldFontObject} 0 R >> >> /Contents {$contentObject} 0 R >>");
        $pagesObject = $this->addObject("<< /Type /Pages /Kids [{$pageObject} 0 R] /Count 1 >>");
        $catalogObject = $this->addObject("<< /Type /Catalog /Pages {$pagesObject} 0 R >>");

        return $this->compile($catalogObject);
    }

    private function drawPayslip(array $payload): void
    {
        $companyName = (string) ($payload['companyName'] ?? app_product_name());
        $companyAddress = (string) ($payload['companyAddress'] ?? '');
        $item = $payload['item'] ?? [];
        $earnings = $payload['earningsLines'] ?? [];
        $deductions = $payload['deductionLines'] ?? [];
        $gross = (float) ($payload['grossEarnings'] ?? ($item['gross_pay'] ?? 0));
        $totalDeductions = (float) ($payload['totalDeductions'] ?? ($item['total_deductions'] ?? 0));
        $net = (float) ($payload['netPay'] ?? ($item['net_pay'] ?? 0));

        $this->rect(40, 775, 515, 42, '0.06 0.11 0.20');
        $this->text(56, 797, $companyName, 15, true, '1 1 1');
        $this->text(56, 780, $companyAddress, 9, false, '0.86 0.91 0.98');
        $this->text(465, 792, 'PAYSLIP', 17, true, '1 1 1');
        $this->text(469, 778, (string) ($item['pay_period'] ?? ''), 9, false, '0.86 0.91 0.98');

        $this->text(40, 742, 'Employee Details', 12, true);
        $this->line(40, 735, 555, 735, '0.82 0.86 0.91');

        $left = [
            ['Employee Name', (string) ($item['employee_name'] ?? $item['full_name'] ?? '')],
            ['Employee No.', (string) ($item['employee_number'] ?? '')],
            ['Department', (string) ($item['department_name'] ?? '-')],
        ];
        $right = [
            ['Designation', (string) ($item['designation'] ?? '-')],
            ['Run Date', (string) ($item['run_date'] ?? '-')],
            ['Payment Method', (string) ($payload['paymentMethod'] ?? 'Bank Transfer')],
        ];
        $y = 715;
        foreach ($left as $row) {
            $this->text(40, $y, $row[0], 8, true, '0.38 0.45 0.55');
            $this->text(145, $y, $row[1], 10);
            $y -= 20;
        }
        $y = 715;
        foreach ($right as $row) {
            $this->text(315, $y, $row[0], 8, true, '0.38 0.45 0.55');
            $this->text(420, $y, $row[1], 10);
            $y -= 20;
        }

        $this->table(40, 630, 245, 'Earnings', $earnings, $gross, 'Gross Earnings');
        $this->table(310, 630, 245, 'Deductions', $deductions, $totalDeductions, 'Total Deductions');

        $this->rect(40, 245, 515, 50, '0.00 0.47 0.30');
        $this->text(58, 275, 'NET PAY', 11, true, '1 1 1');
        $this->text(385, 267, format_currency($net), 20, true, '1 1 1');

        $this->line(40, 105, 555, 105, '0.82 0.86 0.91');
        $this->text(40, 88, 'This is a computer-generated payslip and does not require a physical signature.', 8, false, '0.38 0.45 0.55');
        $this->text(445, 88, 'Authorised Signatory', 8, false, '0.38 0.45 0.55');
        $this->line(430, 103, 555, 103, '0.20 0.24 0.31');
    }

    private function table(float $x, float $y, float $width, string $title, array $rows, float $total, string $totalLabel): void
    {
        $this->rect($x, $y, $width, 24, '0.94 0.97 1.00');
        $this->text($x + 10, $y + 8, $title, 10, true, '0.08 0.16 0.29');
        $lineY = $y - 8;
        if ($rows === []) {
            $rows[] = ['label' => 'No items', 'amount' => 0];
        }
        foreach ($rows as $row) {
            if ($lineY < 320) {
                break;
            }
            $label = (string) ($row['label'] ?? $row['name'] ?? 'Item');
            $amount = (float) ($row['amount'] ?? 0);
            $this->line($x, $lineY - 5, $x + $width, $lineY - 5, '0.90 0.92 0.95');
            $this->text($x + 10, $lineY, $this->fit($label, 31), 9, false, '0.30 0.35 0.42');
            $this->text($x + $width - 92, $lineY, format_currency($amount), 9);
            $lineY -= 20;
        }
        $this->line($x, $lineY - 3, $x + $width, $lineY - 3, '0.20 0.24 0.31');
        $this->text($x + 10, $lineY - 18, $totalLabel, 10, true);
        $this->text($x + $width - 100, $lineY - 18, format_currency($total), 10, true);
    }

    private function text(float $x, float $y, string $text, int $size = 10, bool $bold = false, string $color = '0 0 0'): void
    {
        $font = $bold ? 'F2' : 'F1';
        $safe = $this->escapeText($text);
        $this->content .= "BT /{$font} {$size} Tf {$color} rg 1 0 0 1 {$x} {$y} Tm ({$safe}) Tj ET\n";
    }

    private function rect(float $x, float $y, float $w, float $h, string $color): void
    {
        $this->content .= "{$color} rg {$x} {$y} {$w} {$h} re f\n";
    }

    private function line(float $x1, float $y1, float $x2, float $y2, string $color): void
    {
        $this->content .= "{$color} RG {$x1} {$y1} m {$x2} {$y2} l S\n";
    }

    private function fit(string $text, int $length): string
    {
        return strlen($text) > $length ? substr($text, 0, max(0, $length - 3)) . '...' : $text;
    }

    private function escapeText(string $text): string
    {
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES, 'UTF-8');
        $converted = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT//IGNORE', $text);
        $text = $converted === false ? $text : $converted;
        return str_replace(['\\', '(', ')', "\r", "\n"], ['\\\\', '\\(', '\\)', ' ', ' '], $text);
    }

    private function addObject(string $body): int
    {
        $this->objects[] = $body;
        return count($this->objects);
    }

    private function compile(int $catalogObject): string
    {
        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($this->objects as $index => $object) {
            $offsets[] = strlen($pdf);
            $number = $index + 1;
            $pdf .= "{$number} 0 obj\n{$object}\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 " . (count($this->objects) + 1) . "\n0000000000 65535 f \n";
        for ($i = 1, $count = count($offsets); $i < $count; $i++) {
            $pdf .= str_pad((string) $offsets[$i], 10, '0', STR_PAD_LEFT) . " 00000 n \n";
        }
        $pdf .= "trailer\n<< /Size " . (count($this->objects) + 1) . " /Root {$catalogObject} 0 R >>\nstartxref\n{$xref}\n%%EOF";

        return $pdf;
    }
}
