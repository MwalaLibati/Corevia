<?php

declare(strict_types=1);

/**
 * Builds a NAPSA monthly return workbook from generated payroll data.
 */
class NapsaReturnExporter
{
    private PDO $db;
    private string $sharedStringsPath = '';
    private array $sharedStrings = [];
    private int $sharedStringCount = 0;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? db();
    }

    public function exportForPayrollRun(int $payrollRunId): string
    {
        $run = $this->payrollRun($payrollRunId);
        if (!$run) {
            throw new RuntimeException('Payroll run not found.');
        }

        $settings = new Setting();
        $company = current_company() ?: [];
        $employerName = $settings->value('statutory_registered_employer_name', (string) ($company['name'] ?? ''));
        $accountNumber = $settings->value('statutory_napsa_account_number', '');

        if (trim($accountNumber) === '') {
            throw new RuntimeException('Please enter the company NAPSA account number under System Settings > Statutory Registration Details.');
        }

        $rows = $this->napsaRows($payrollRunId);
        if ($rows === []) {
            throw new RuntimeException('No NAPSA payroll deductions were found for this payroll run.');
        }
        $this->validateNapsaRows($rows);

        $template = BASE_PATH . '/resources/templates/2026_NAPSA_Template.xlsm';
        if (!is_file($template)) {
            throw new RuntimeException('NAPSA template file is missing.');
        }

        $period = $this->periodParts($run);
        $outputDir = BASE_PATH . '/uploads/statutory/napsa';
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0775, true);
        }

        $safePeriod = preg_replace('/[^0-9A-Za-z_-]/', '-', (string) ($run['pay_period'] ?? date('Y-m')));
        $output = $outputDir . '/NAPSA_Return_' . $safePeriod . '_Run_' . $payrollRunId . '_' . date('YmdHis') . '.xlsm';

        $workDir = sys_get_temp_dir() . '/corevia_napsa_' . uniqid('', true);
        mkdir($workDir, 0775, true);

        try {
            $this->extractTemplate($template, $workDir);
            $this->loadSharedStrings($workDir . '/xl/sharedStrings.xml');

            $this->updateSectionA($workDir . '/xl/worksheets/sheet4.xml', $employerName, $accountNumber, $period['year'], $period['month'], count($rows));
            $this->updateReturnDetails($workDir . '/xl/worksheets/sheet5.xml', $employerName, $accountNumber, $period['year'], $period['month'], $rows);
            $this->openOnReturnDetails($workDir);
            $this->saveSharedStrings();

            $zipPath = $output . '.zip';
            if (is_file($zipPath)) {
                unlink($zipPath);
            }

            $this->buildArchive($zipPath, $workDir);

            if (is_file($output)) {
                unlink($output);
            }
            rename($zipPath, $output);
            $this->verifyGeneratedWorkbook($output, $rows);
        } finally {
            $this->deleteDirectory($workDir);
        }

        AuditLog::record('napsa_export', 'Generated NAPSA return for payroll run #' . $payrollRunId, 'PayrollRun', $payrollRunId);

        return $output;
    }

    private function extractTemplate(string $template, string $workDir): void
    {
        if (class_exists('ZipArchive')) {
            $zip = new ZipArchive();
            if ($zip->open($template) !== true) {
                throw new RuntimeException('Could not open the NAPSA template workbook.');
            }
            $zip->extractTo($workDir);
            $zip->close();
            return;
        }

        $phar = new PharData($template);
        $phar->extractTo($workDir, null, true);
    }

    private function buildArchive(string $zipPath, string $workDir): void
    {
        if (class_exists('ZipArchive')) {
            $zip = new ZipArchive();
            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException('Could not create the NAPSA return workbook.');
            }
            $this->addDirectoryToZip($zip, $workDir, $workDir);
            $zip->close();
            return;
        }

        $this->buildZipFallback($zipPath, $workDir);
    }

    private function payrollRun(int $payrollRunId): ?array
    {
        $cid = Tenant::id();
        $sql = 'SELECT * FROM payroll_runs WHERE id = :id' . ($cid > 0 ? ' AND company_id = :cid' : '') . ' LIMIT 1';
        $params = ['id' => $payrollRunId];
        if ($cid > 0) {
            $params['cid'] = $cid;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    private function napsaRows(int $payrollRunId): array
    {
        $cid = Tenant::id();
        $and = $cid > 0 ? ' AND pr.company_id = :run_cid AND e.company_id = :employee_cid' : '';
        $sql = "SELECT
                    TRIM(e.employee_number) AS employee_number,
                    TRIM(e.full_name) AS full_name,
                    TRIM(e.napsa_number) AS napsa_number,
                    TRIM(e.nrc_number) AS nrc_number,
                    e.date_of_birth,
                    pi.gross_pay,
                    COALESCE(napsa_employee.calculation_base, pi.gross_pay) AS contribution_wage,
                    COALESCE(napsa_employee.amount, 0) AS employee_share,
                    COALESCE(napsa_employer.amount, napsa_employee.amount, 0) AS employer_share
                FROM payroll_items pi
                JOIN payroll_runs pr ON pr.id = pi.payroll_run_id
                JOIN employees e ON e.id = pi.employee_id
                LEFT JOIN payroll_item_deductions napsa_employee
                    ON napsa_employee.payroll_item_id = pi.id
                   AND UPPER(napsa_employee.deduction_code) = 'NAPSA'
                   AND napsa_employee.deduction_category = 'statutory_employee'
                LEFT JOIN payroll_item_deductions napsa_employer
                    ON napsa_employer.payroll_item_id = pi.id
                   AND UPPER(napsa_employer.deduction_code) = 'NAPSA'
                   AND napsa_employer.deduction_category = 'statutory_employer'
                WHERE pi.payroll_run_id = :run_id
                  AND napsa_employee.id IS NOT NULL
                  AND pr.reversed_at IS NULL
                  $and
                ORDER BY e.full_name ASC, e.employee_number ASC";

        $params = ['run_id' => $payrollRunId];
        if ($cid > 0) {
            $params['run_cid'] = $cid;
            $params['employee_cid'] = $cid;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    private function validateNapsaRows(array $rows): void
    {
        $missing = [];
        foreach ($rows as $row) {
            $employee = trim((string) ($row['employee_number'] ?? 'Employee'));
            $name = trim((string) ($row['full_name'] ?? ''));
            $problems = [];

            if ($name === '') {
                $problems[] = 'name';
            }
            if (trim((string) ($row['napsa_number'] ?? '')) === '') {
                $problems[] = 'SSNo/NAPSA number';
            }
            if (trim((string) ($row['nrc_number'] ?? '')) === '') {
                $problems[] = 'NRC number';
            }
            if (trim((string) ($row['date_of_birth'] ?? '')) === '') {
                $problems[] = 'date of birth';
            }

            if ($problems !== []) {
                $missing[] = $employee . ' missing ' . implode(', ', $problems);
            }
        }

        if ($missing !== []) {
            throw new RuntimeException('Complete employee statutory details before generating the NAPSA return: ' . implode('; ', array_slice($missing, 0, 5)) . (count($missing) > 5 ? '; and more.' : '.'));
        }
    }

    private function verifyGeneratedWorkbook(string $path, array $rows): void
    {
        $expected = $rows[0] ?? [];
        $expectedNapsa = trim((string) ($expected['napsa_number'] ?? ''));
        $expectedNrc = trim((string) ($expected['nrc_number'] ?? ''));

        if ($expectedNapsa === '' || $expectedNrc === '') {
            return;
        }

        $sheetXml = null;
        $sharedStringsXml = null;
        if (class_exists('ZipArchive')) {
            $zip = new ZipArchive();
            if ($zip->open($path) === true) {
                $sheetXml = $zip->getFromName('xl/worksheets/sheet5.xml');
                $sharedStringsXml = $zip->getFromName('xl/sharedStrings.xml');
                $zip->close();
            }
        } else {
            try {
                $phar = new PharData($path);
                if (isset($phar['xl/worksheets/sheet5.xml'])) {
                    $sheetXml = (string) $phar['xl/worksheets/sheet5.xml']->getContent();
                }
                if (isset($phar['xl/sharedStrings.xml'])) {
                    $sharedStringsXml = (string) $phar['xl/sharedStrings.xml']->getContent();
                }
            } catch (Throwable) {
                $sheetXml = null;
            }
        }

        if (!is_string($sheetXml) || $sheetXml === '') {
            throw new RuntimeException('NAPSA return was created but could not be verified. Please regenerate it.');
        }

        $haystack = (string) $sheetXml . (string) $sharedStringsXml;
        $containsNapsa = str_contains($haystack, '<t>' . htmlspecialchars($expectedNapsa, ENT_XML1 | ENT_COMPAT, 'UTF-8') . '</t>');
        $containsNrc = str_contains($haystack, '<t>' . htmlspecialchars($expectedNrc, ENT_XML1 | ENT_COMPAT, 'UTF-8') . '</t>');

        if (!$containsNapsa || !$containsNrc) {
            throw new RuntimeException('NAPSA return verification failed: employee statutory details were not written into the workbook. Restart Apache and regenerate the file.');
        }
    }

    private function periodParts(array $run): array
    {
        $period = (string) ($run['pay_period'] ?? '');
        $year = (int) date('Y');
        $month = (int) date('n');

        if (preg_match('/^(20\d{2})[-\/](0?[1-9]|1[0-2])$/', $period, $m)) {
            $year = (int) $m[1];
            $month = (int) $m[2];
        } elseif (!empty($run['run_date'])) {
            $time = strtotime((string) $run['run_date']);
            if ($time !== false) {
                $year = (int) date('Y', $time);
                $month = (int) date('n', $time);
            }
        }

        return ['year' => $year, 'month' => $month];
    }

    private function updateSectionA(string $path, string $employerName, string $accountNumber, int $year, int $month, int $employeeCount): void
    {
        $xml = (string) file_get_contents($path);
        $xml = $this->replaceCellXml($xml, 'B2', $employerName, 'string');
        $xml = $this->replaceCellXml($xml, 'B3', $accountNumber, 'number');
        $xml = $this->replaceCellXml($xml, 'B4', $year, 'number');
        $xml = $this->replaceCellXml($xml, 'B5', $month, 'number');
        $xml = $this->replaceCellXml($xml, 'B6', $employeeCount, 'number');
        file_put_contents($path, $xml);
    }

    private function updateReturnDetails(string $path, string $employerName, string $accountNumber, int $year, int $month, array $employees): void
    {
        $xml = (string) file_get_contents($path);
        $xml = $this->replaceCellXml($xml, 'D2', $employerName, 'string');

        $total = 0.0;
        foreach ($employees as $employee) {
            $total += (float) ($employee['employer_share'] ?? 0) + (float) ($employee['employee_share'] ?? 0);
        }
        $xml = $this->replaceCellXml($xml, 'D4', ['SUM(K8:L15007)', round($total, 2)], 'formula');

        foreach (array_values($employees) as $index => $employee) {
            $row = 8 + $index;
            [$surname, $firstName, $otherName] = $this->splitName((string) ($employee['full_name'] ?? $employee['employee_number'] ?? ''));
            $wage = round((float) ($employee['contribution_wage'] ?? $employee['gross_pay'] ?? 0), 2);
            $employerShare = round((float) ($employee['employer_share'] ?? 0), 2);
            $employeeShare = round((float) ($employee['employee_share'] ?? 0), 2);

            $xml = $this->replaceCellXml($xml, 'A' . $row, $accountNumber, 'number');
            $xml = $this->replaceCellXml($xml, 'B' . $row, $year, 'number');
            $xml = $this->replaceCellXml($xml, 'C' . $row, $month, 'number');
            $xml = $this->replaceCellXml($xml, 'D' . $row, (string) ($employee['napsa_number'] ?? ''), 'string');
            $xml = $this->replaceCellXml($xml, 'E' . $row, (string) ($employee['nrc_number'] ?? ''), 'string');
            $xml = $this->replaceCellXml($xml, 'F' . $row, $surname, 'string');
            $xml = $this->replaceCellXml($xml, 'G' . $row, $firstName, 'string');
            $xml = $this->replaceCellXml($xml, 'H' . $row, $otherName, 'string');
            $xml = $this->replaceCellXml($xml, 'I' . $row, $this->excelDate((string) ($employee['date_of_birth'] ?? '')), 'number');
            $xml = $this->replaceCellXml($xml, 'J' . $row, $wage, 'number');
            $xml = $this->replaceCellXml($xml, 'K' . $row, ['IF(J' . $row . '>=37236,37236* 0.05,ROUND(J' . $row . '*0.05,2))', $employerShare], 'formula');
            $xml = $this->replaceCellXml($xml, 'L' . $row, ['K' . $row, $employeeShare], 'formula');
        }

        file_put_contents($path, $xml);
    }

    private function openOnReturnDetails(string $workDir): void
    {
        $workbookPath = $workDir . '/xl/workbook.xml';
        $sectionPath = $workDir . '/xl/worksheets/sheet4.xml';
        $returnPath = $workDir . '/xl/worksheets/sheet5.xml';

        if (is_file($workbookPath)) {
            $xml = (string) file_get_contents($workbookPath);
            $xml = preg_replace('/activeTab="\d+"/', 'activeTab="4"', $xml) ?? $xml;
            file_put_contents($workbookPath, $xml);
        }

        if (is_file($sectionPath)) {
            $xml = (string) file_get_contents($sectionPath);
            $xml = str_replace(' tabSelected="1"', '', $xml);
            $xml = str_replace('tabSelected="1" ', '', $xml);
            file_put_contents($sectionPath, $xml);
        }

        if (is_file($returnPath)) {
            $xml = (string) file_get_contents($returnPath);
            $xml = preg_replace('/<sheetView\b(?![^>]*\btabSelected=)/', '<sheetView tabSelected="1"', $xml, 1) ?? $xml;
            $xml = preg_replace('/<selection\b[^>]*\/>/', '<selection activeCell="D8" sqref="D8"/>', $xml, 1) ?? $xml;
            file_put_contents($returnPath, $xml);
        }

        $sectionDrawingPath = $workDir . '/xl/drawings/drawing3.xml';
        if (is_file($sectionDrawingPath)) {
            $xml = (string) file_get_contents($sectionDrawingPath);
            $xml = str_replace('macro="[0]!Sheet4.TemplatePrep"', 'macro=""', $xml);
            $xml = preg_replace(
                '/(<xdr:cNvPr id="7" name="TextBox 6">)/',
                '$1<a:hlinkClick xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" r:id="rId2"/>',
                $xml,
                1
            ) ?? $xml;
            file_put_contents($sectionDrawingPath, $xml);
        }

        $sectionDrawingRelsPath = $workDir . '/xl/drawings/_rels/drawing3.xml.rels';
        if (is_file($sectionDrawingRelsPath)) {
            $xml = (string) file_get_contents($sectionDrawingRelsPath);
            if (!str_contains($xml, 'Id="rId2"')) {
                $relationship = '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/hyperlink" Target="#Return_Details!A1"/>';
                $xml = str_replace('</Relationships>', $relationship . '</Relationships>', $xml);
                file_put_contents($sectionDrawingRelsPath, $xml);
            }
        }
    }

    private function replaceCellXml(string $xml, string $ref, mixed $value, string $type): string
    {
        $node = $this->cellXml($ref, '', $value, $type);
        $patternSelf = '/<c r="' . preg_quote($ref, '/') . '"([^\/>]*)\/>/s';
        if (preg_match($patternSelf, $xml, $match, PREG_OFFSET_CAPTURE)) {
            $attrs = preg_replace('/\s+t="[^"]*"/', '', (string) $match[1][0]);
            $node = $this->cellXml($ref, $attrs, $value, $type);
            return substr_replace($xml, $node, (int) $match[0][1], strlen((string) $match[0][0]));
        }

        $patternClosed = '/<c r="' . preg_quote($ref, '/') . '"([^>]*)>.*?<\/c>/s';
        if (preg_match($patternClosed, $xml, $match, PREG_OFFSET_CAPTURE)) {
            $attrs = preg_replace('/\s+t="[^"]*"/', '', (string) $match[1][0]);
            $node = $this->cellXml($ref, $attrs, $value, $type);
            return substr_replace($xml, $node, (int) $match[0][1], strlen((string) $match[0][0]));
        }

        if (!preg_match('/^([A-Z]+)(\d+)$/', $ref, $parts)) {
            return $xml;
        }
        $targetColIndex = $this->columnIndex($parts[1]);
        $rowNumber = $parts[2];
        $attrs = ' s="' . ($this->styleForColumn($parts[1]) ?? '45') . '"';
        $node = $this->cellXml($ref, $attrs, $value, $type);
        $rowPattern = '/(<row\b[^>]*\br="' . preg_quote($rowNumber, '/') . '"[^>]*>)(.*?)(<\/row>)/s';
        if (!preg_match($rowPattern, $xml, $rowMatch, PREG_OFFSET_CAPTURE)) {
            return $xml;
        }

        $insertAt = (int) $rowMatch[3][1];
        if (preg_match_all('/<c r="([A-Z]+)' . preg_quote($rowNumber, '/') . '"[^>]*(?:\/>|>.*?<\/c>)/s', (string) $rowMatch[2][0], $cells, PREG_OFFSET_CAPTURE)) {
            foreach ($cells[0] as $i => $cellMatch) {
                $cellCol = (string) $cells[1][$i][0];
                if ($this->columnIndex($cellCol) > $targetColIndex) {
                    $insertAt = (int) $rowMatch[2][1] + (int) $cellMatch[1];
                    break;
                }
            }
        }
        return substr_replace($xml, $node, $insertAt, 0);
    }

    private function columnIndex(string $column): int
    {
        $index = 0;
        foreach (str_split($column) as $char) {
            $index = $index * 26 + (ord($char) - 64);
        }
        return $index;
    }

    private function cellXml(string $ref, string $attrs, mixed $value, string $type): string
    {
        $attrs = $attrs === '' ? '' : $attrs;
        if ($type === 'string') {
            $text = (string) $value;
            return $text === ''
                ? '<c r="' . $ref . '"' . $attrs . '/>'
                : '<c r="' . $ref . '"' . $attrs . ' t="s"><v>' . $this->sharedStringIndex($text) . '</v></c>';
        }

        if ($type === 'formula') {
            $formula = htmlspecialchars((string) ($value[0] ?? ''), ENT_XML1 | ENT_COMPAT, 'UTF-8');
            $cached = htmlspecialchars((string) ($value[1] ?? 0), ENT_XML1 | ENT_COMPAT, 'UTF-8');
            return '<c r="' . $ref . '"' . $attrs . '><f>' . $formula . '</f><v>' . $cached . '</v></c>';
        }

        $number = (string) $value;
        return $number === ''
            ? '<c r="' . $ref . '"' . $attrs . '/>'
            : '<c r="' . $ref . '"' . $attrs . '><v>' . htmlspecialchars($number, ENT_XML1 | ENT_COMPAT, 'UTF-8') . '</v></c>';
    }

    private function loadSharedStrings(string $path): void
    {
        $this->sharedStringsPath = $path;
        $this->sharedStrings = [];
        $this->sharedStringCount = 0;

        if (!is_file($path)) {
            return;
        }

        $xml = (string) file_get_contents($path);
        if (preg_match('/<sst\b[^>]*\bcount="(\d+)"/', $xml, $match)) {
            $this->sharedStringCount = (int) $match[1];
        }

        if (preg_match_all('/<si>(.*?)<\/si>/s', $xml, $matches)) {
            foreach ($matches[1] as $index => $si) {
                if (preg_match_all('/<t(?:\s+xml:space="preserve")?>(.*?)<\/t>/s', (string) $si, $textMatches)) {
                    $text = '';
                    foreach ($textMatches[1] as $part) {
                        $text .= html_entity_decode((string) $part, ENT_QUOTES | ENT_XML1, 'UTF-8');
                    }
                    $this->sharedStrings[$text] = $index;
                }
            }
        }

        if ($this->sharedStringCount <= 0) {
            $this->sharedStringCount = count($this->sharedStrings);
        }
    }

    private function sharedStringIndex(string $text): int
    {
        if (isset($this->sharedStrings[$text])) {
            $this->sharedStringCount++;
            return (int) $this->sharedStrings[$text];
        }

        $index = count($this->sharedStrings);
        $this->sharedStrings[$text] = $index;
        $this->sharedStringCount++;

        return $index;
    }

    private function saveSharedStrings(): void
    {
        if ($this->sharedStringsPath === '') {
            return;
        }

        $items = '';
        foreach ($this->sharedStrings as $text => $index) {
            $escaped = htmlspecialchars((string) $text, ENT_XML1 | ENT_COMPAT, 'UTF-8');
            $space = preg_match('/^\s|\s$/', (string) $text) ? ' xml:space="preserve"' : '';
            $items .= '<si><t' . $space . '>' . $escaped . '</t></si>';
        }

        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\r\n"
            . '<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" count="' . $this->sharedStringCount . '" uniqueCount="' . count($this->sharedStrings) . '">'
            . $items
            . '</sst>';

        file_put_contents($this->sharedStringsPath, $xml);
    }

    private function loadXml(string $path): DOMDocument
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = false;
        $dom->load($path);
        return $dom;
    }

    private function saveXml(DOMDocument $dom, string $path): void
    {
        $dom->save($path);
    }

    private function setCell(DOMDocument $dom, string $ref, string|int|float $value, string $type = 'string', bool $preserveFormula = false): void
    {
        $cell = $this->cell($dom, $ref);
        while ($cell->firstChild) {
            if ($preserveFormula && $cell->firstChild instanceof DOMElement && $cell->firstChild->localName === 'f') {
                break;
            }
            $cell->removeChild($cell->firstChild);
        }

        if (!$preserveFormula) {
            foreach (iterator_to_array($cell->childNodes) as $child) {
                $cell->removeChild($child);
            }
        } else {
            foreach (iterator_to_array($cell->childNodes) as $child) {
                if ($child instanceof DOMElement && $child->localName === 'v') {
                    $cell->removeChild($child);
                }
            }
        }

        if ($type === 'string') {
            if ((string) $value === '') {
                if ($cell->hasAttribute('t')) {
                    $cell->removeAttribute('t');
                }
                return;
            }
            $cell->setAttribute('t', 'inlineStr');
            $is = $dom->createElementNS('http://schemas.openxmlformats.org/spreadsheetml/2006/main', 'is');
            $t = $dom->createElementNS('http://schemas.openxmlformats.org/spreadsheetml/2006/main', 't');
            $t->appendChild($dom->createTextNode((string) $value));
            $is->appendChild($t);
            $cell->appendChild($is);
            return;
        }

        if ($cell->hasAttribute('t')) {
            $cell->removeAttribute('t');
        }
        $v = $dom->createElementNS('http://schemas.openxmlformats.org/spreadsheetml/2006/main', 'v');
        $v->appendChild($dom->createTextNode((string) $value));
        $cell->appendChild($v);
    }

    private function clearCell(DOMDocument $dom, string $ref): void
    {
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $cell = $xpath->query("//x:c[@r='{$ref}']")->item(0);
        if (!$cell instanceof DOMElement) {
            return;
        }
        while ($cell->firstChild) {
            $cell->removeChild($cell->firstChild);
        }
        if ($cell->hasAttribute('t')) {
            $cell->removeAttribute('t');
        }
    }

    private function setFormulaCell(DOMDocument $dom, string $ref, string $formula, string|int|float $cachedValue): void
    {
        $cell = $this->cell($dom, $ref);
        foreach (iterator_to_array($cell->childNodes) as $child) {
            if ($child instanceof DOMElement && in_array($child->localName, ['f', 'v'], true)) {
                $cell->removeChild($child);
            }
        }
        if ($cell->hasAttribute('t')) {
            $cell->removeAttribute('t');
        }

        $f = $dom->createElementNS('http://schemas.openxmlformats.org/spreadsheetml/2006/main', 'f');
        $f->appendChild($dom->createTextNode($formula));
        $v = $dom->createElementNS('http://schemas.openxmlformats.org/spreadsheetml/2006/main', 'v');
        $v->appendChild($dom->createTextNode((string) $cachedValue));
        $cell->appendChild($f);
        $cell->appendChild($v);
    }

    private function removeDataRows(DOMDocument $dom, int $fromRow): void
    {
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $rows = $xpath->query("//x:row[number(@r) >= {$fromRow}]");
        foreach (iterator_to_array($rows) as $row) {
            if ($row instanceof DOMElement && $row->parentNode) {
                $row->parentNode->removeChild($row);
            }
        }
    }

    private function clearUnusedTemplateRows(DOMDocument $dom, int $fromRow, int $toRow): void
    {
        if ($fromRow > $toRow) {
            return;
        }

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $rows = $xpath->query("//x:row[number(@r) >= {$fromRow} and number(@r) <= {$toRow}]");
        foreach ($rows as $row) {
            if (!$row instanceof DOMElement) {
                continue;
            }
            foreach (iterator_to_array($row->childNodes) as $cell) {
                if (!$cell instanceof DOMElement || $cell->localName !== 'c') {
                    continue;
                }
                $ref = (string) $cell->getAttribute('r');
                if (!preg_match('/^([A-Z]+)/', $ref, $m)) {
                    continue;
                }
                if (!in_array($m[1], ['A','B','C','D','E','F','G','H','I','J','K','L'], true)) {
                    continue;
                }
                while ($cell->firstChild) {
                    $cell->removeChild($cell->firstChild);
                }
                if ($cell->hasAttribute('t')) {
                    $cell->removeAttribute('t');
                }
            }
        }
    }

    private function setDimension(DOMDocument $dom, string $ref): void
    {
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $dimension = $xpath->query('//x:dimension')->item(0);
        if ($dimension instanceof DOMElement) {
            $dimension->setAttribute('ref', $ref);
        }
    }

    private function cell(DOMDocument $dom, string $ref): DOMElement
    {
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');

        $found = $xpath->query("//x:c[@r='{$ref}']")->item(0);
        if ($found instanceof DOMElement) {
            return $found;
        }

        preg_match('/^([A-Z]+)(\d+)$/', $ref, $m);
        $col = $m[1] ?? 'A';
        $rowNumber = (int) ($m[2] ?? 1);

        $sheetData = $xpath->query('//x:sheetData')->item(0);
        if (!$sheetData instanceof DOMElement) {
            throw new RuntimeException('Invalid NAPSA worksheet: sheetData missing.');
        }

        $row = $xpath->query("//x:row[@r='{$rowNumber}']")->item(0);
        if (!$row instanceof DOMElement) {
            $row = $dom->createElementNS('http://schemas.openxmlformats.org/spreadsheetml/2006/main', 'row');
            $row->setAttribute('r', (string) $rowNumber);
            $sheetData->appendChild($row);
        }

        $cell = $dom->createElementNS('http://schemas.openxmlformats.org/spreadsheetml/2006/main', 'c');
        $cell->setAttribute('r', $ref);
        $style = $this->styleForColumn($col);
        if ($style !== null) {
            $cell->setAttribute('s', $style);
        }
        $row->appendChild($cell);

        return $cell;
    }

    private function styleForColumn(string $col): ?string
    {
        return [
            'A' => '45', 'B' => '45', 'C' => '45',
            'D' => '31', 'E' => '31', 'F' => '31', 'G' => '31', 'H' => '32',
            'I' => '35', 'J' => '33', 'K' => '12', 'L' => '12',
        ][$col] ?? null;
    }

    private function splitName(string $fullName): array
    {
        $parts = preg_split('/\s+/', trim($fullName)) ?: [];
        $parts = array_values(array_filter($parts, static fn(string $part): bool => $part !== ''));
        if (count($parts) === 0) {
            return ['', '', ''];
        }
        if (count($parts) === 1) {
            return [$parts[0], '', ''];
        }

        $surname = array_pop($parts);
        $firstName = array_shift($parts) ?? '';
        $otherName = implode(' ', $parts);

        return [$surname, $firstName, $otherName];
    }

    private function excelDate(string $date): int|string
    {
        if ($date === '' || $date === '0000-00-00') {
            return '';
        }
        $time = strtotime($date);
        if ($time === false) {
            return '';
        }

        return (int) floor(($time - strtotime('1899-12-30')) / 86400);
    }

    private function addDirectoryToArchive(PharData $archive, string $dir, string $base): void
    {
        $items = scandir($dir);
        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . '/' . $item;
            if (is_dir($path)) {
                $this->addDirectoryToArchive($archive, $path, $base);
                continue;
            }
            $local = str_replace('\\', '/', ltrim(substr($path, strlen($base)), '/\\'));
            $archive->addFile($path, $local);
        }
    }

    private function buildZipFallback(string $zipPath, string $workDir): void
    {
        $files = $this->zipFileList($workDir, $workDir);
        $handle = fopen($zipPath, 'wb');
        if ($handle === false) {
            throw new RuntimeException('Could not create the NAPSA return workbook.');
        }

        $centralDirectory = '';
        $offset = 0;

        foreach ($files as $file) {
            $name = $file['name'];
            $path = $file['path'];
            $contents = (string) file_get_contents($path);
            $compressed = function_exists('gzdeflate') ? gzdeflate($contents, 9) : $contents;
            $method = function_exists('gzdeflate') ? 8 : 0;
            $crc = crc32($contents);
            $size = strlen($contents);
            $compressedSize = strlen($compressed);
            [$dosTime, $dosDate] = $this->dosDateTime((int) filemtime($path));

            $localHeader = pack('VvvvvvVVVvv',
                0x04034b50,
                20,
                0,
                $method,
                $dosTime,
                $dosDate,
                $crc,
                $compressedSize,
                $size,
                strlen($name),
                0
            ) . $name;

            fwrite($handle, $localHeader);
            fwrite($handle, $compressed);

            $centralDirectory .= pack('VvvvvvvVVVvvvvvVV',
                0x02014b50,
                20,
                20,
                0,
                $method,
                $dosTime,
                $dosDate,
                $crc,
                $compressedSize,
                $size,
                strlen($name),
                0,
                0,
                0,
                0,
                0,
                $offset
            ) . $name;

            $offset += strlen($localHeader) + $compressedSize;
        }

        fwrite($handle, $centralDirectory);
        fwrite($handle, pack('VvvvvVVv',
            0x06054b50,
            0,
            0,
            count($files),
            count($files),
            strlen($centralDirectory),
            $offset,
            0
        ));
        fclose($handle);
    }

    private function zipFileList(string $dir, string $base): array
    {
        $files = [];
        $items = scandir($dir);
        if ($items === false) {
            return $files;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $dir . '/' . $item;
            if (is_dir($path)) {
                $files = array_merge($files, $this->zipFileList($path, $base));
                continue;
            }

            $files[] = [
                'path' => $path,
                'name' => str_replace('\\', '/', ltrim(substr($path, strlen($base)), '/\\')),
            ];
        }

        return $files;
    }

    private function dosDateTime(int $timestamp): array
    {
        $time = getdate($timestamp);
        $year = max(1980, (int) $time['year']);

        $dosTime = ((int) $time['hours'] << 11)
            | ((int) $time['minutes'] << 5)
            | ((int) floor(((int) $time['seconds']) / 2));

        $dosDate = (($year - 1980) << 9)
            | ((int) $time['mon'] << 5)
            | (int) $time['mday'];

        return [$dosTime, $dosDate];
    }

    private function addDirectoryToZip(ZipArchive $archive, string $dir, string $base): void
    {
        $items = scandir($dir);
        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . '/' . $item;
            if (is_dir($path)) {
                $this->addDirectoryToZip($archive, $path, $base);
                continue;
            }
            $local = str_replace('\\', '/', ltrim(substr($path, strlen($base)), '/\\'));
            $archive->addFile($path, $local);
        }
    }

    private function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) ?: [] as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . '/' . $item;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }
}
