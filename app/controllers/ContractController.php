<?php

declare(strict_types=1);

/**
 * Employee contract management controller.
 */

class ContractController extends Controller
{
    private const CONTRACT_STATUSES = ['Active', 'Expired', 'Terminated', 'Renewed'];

    public function index(): void
    {
        require_auth();
        require_role(['Super Admin', 'HR Officer', 'Finance Officer', 'Viewer']);

        $model = new EmployeeContract();
        $model->autoExpire();

        $search = trim((string) $this->input('search', ''));
        $filter = trim((string) $this->input('filter', ''));

        $contracts = $search !== '' ? $model->search($search) : $model->listAll();

        if ($filter !== '') {
            $contracts = array_values(array_filter(
                $contracts,
                static fn(array $c): bool => ($c['status'] ?? '') === $filter
            ));
        }

        $renewalRequestModel = new ContractRenewalRequest();

        $this->render('contracts/index', [
            'title'           => 'Contract Management',
            'contracts'       => $contracts,
            'search'          => $search,
            'filter'          => $filter,
            'expiring'        => $model->expiringWithinDays(30),
            'renewalRequests' => $renewalRequestModel->pendingForCompany(Tenant::id()),
            'csrf'            => Session::csrfToken(),
            'flashSuccess'    => Session::flash('success'),
            'flashError'      => Session::flash('error'),
        ]);
    }

    public function create(): void
    {
        require_auth();
        require_role(['Super Admin', 'HR Officer']);

        $model         = new EmployeeContract();
        $tmplModel     = new ContractTemplate();
        $preEmployeeId = (int) $this->input('employee_id', 0);

        $this->render('contracts/create', [
            'title'              => 'New Contract',
            'employees'          => $model->employees(),
            'contractTypes'      => (new EmploymentType())->names(),
            'nextContractNumber' => $model->generateContractNumber(),
            'preEmployeeId'      => $preEmployeeId > 0 ? $preEmployeeId : null,
            'templates'          => $tmplModel->listAll(),
            'csrf'               => Session::csrfToken(),
            'flashError'         => Session::flash('error'),
            'old'                => $_SESSION['_old_contract_input'] ?? [],
        ]);

        unset($_SESSION['_old_contract_input']);
    }

    public function store(): void
    {
        require_auth();
        require_role(['Super Admin', 'HR Officer']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('contract/index');
        }

        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid request token.');
            redirect('contract/create');
        }

        $data = $this->collectInput();
        $_SESSION['_old_contract_input'] = $data;

        $error = $this->validate($data);
        if ($error !== null) {
            Session::flash('error', $error);
            redirect('contract/create');
        }

        $model      = new EmployeeContract();
        $tmplModel  = new ContractTemplate();
        $userId     = (int) ($_SESSION['auth_user']['id'] ?? $_SESSION['user']['id'] ?? 0);

        // Auto-resolve template based on employee salary structure + contract type
        $empSalary  = (new EmployeeSalary())->activeWithStructureForDate(
            (int) $data['employee_id'],
            date('Y-m-d')
        );
        $ssId       = $empSalary ? (int) ($empSalary['salary_structure_id'] ?? 0) : null;
        $matched    = $tmplModel->resolve($ssId ?: null, (string) $data['contract_type']);
        $templateId = $matched ? (int) $matched['id'] : null;

        try {
            $newId = $model->createContract(
                (int) $data['employee_id'],
                (string) $data['contract_type'],
                (string) $data['start_date'],
                $data['end_date'],
                $data['notes'],
                $userId > 0 ? $userId : null,
                $templateId
            );

            unset($_SESSION['_old_contract_input']);
            WorkflowEvent::record('contract', 'EmployeeContract', $newId, null, 'Pending HR Review', 'contract_prepare', 'HR prepared contract for approval.');
            $tmplNote = $matched ? ' Template: ' . (string) ($matched['name'] ?? '') . '.' : '';
            Session::flash('success', 'Contract prepared and submitted for approval.' . $tmplNote);
        } catch (Throwable $e) {
            Session::flash('error', 'Failed to create contract: ' . $e->getMessage());
        }

        redirect('contract/index');
    }

    public function edit(string $id): void
    {
        require_auth();
        require_role(['Super Admin', 'HR Officer']);

        $contractId = (int) $id;
        if ($contractId <= 0) {
            Session::flash('error', 'Invalid contract id.');
            redirect('contract/index');
        }

        $model = new EmployeeContract();
        $contract = $model->findDetailed($contractId);

        if (!$contract) {
            Session::flash('error', 'Contract not found.');
            redirect('contract/index');
        }

        $old = $_SESSION['_old_contract_input'] ?? [];
        $formData = !empty($old) ? $old : $contract;

        $this->render('contracts/edit', [
            'title'            => 'Edit Contract',
            'contract'         => $contract,
            'formData'         => $formData,
            'contractTypes'    => (new EmploymentType())->names(),
            'contractStatuses' => self::CONTRACT_STATUSES,
            'csrf'             => Session::csrfToken(),
            'flashError'       => Session::flash('error'),
            'flashSuccess'     => Session::flash('success'),
        ]);

        unset($_SESSION['_old_contract_input']);
    }

    public function update(string $id): void
    {
        require_auth();
        require_role(['Super Admin', 'HR Officer']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('contract/index');
        }

        $contractId = (int) $id;
        if ($contractId <= 0) {
            Session::flash('error', 'Invalid contract id.');
            redirect('contract/index');
        }

        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid request token.');
            redirect('contract/edit/' . $contractId);
        }

        $model = new EmployeeContract();
        if (!$model->find($contractId)) {
            Session::flash('error', 'Contract not found.');
            redirect('contract/index');
        }

        $data = $this->collectInput();
        $data['status'] = (string) $this->input('status', 'Active');
        $_SESSION['_old_contract_input'] = $data;

        $error = $this->validate($data);
        if ($error !== null) {
            Session::flash('error', $error);
            redirect('contract/edit/' . $contractId);
        }

        try {
            $model->update($contractId, [
                'contract_type' => $data['contract_type'],
                'start_date'    => $data['start_date'],
                'end_date'      => $data['end_date'],
                'status'        => $data['status'],
                'notes'         => $data['notes'],
            ]);

            unset($_SESSION['_old_contract_input']);
            Session::flash('success', 'Contract updated successfully.');
        } catch (Throwable) {
            Session::flash('error', 'Failed to update contract.');
        }

        redirect('contract/edit/' . $contractId);
    }

    public function terminate(string $id): void
    {
        require_auth();
        require_role(['Super Admin', 'HR Officer']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('contract/index');
        }

        $contractId = (int) $id;

        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid request token.');
            redirect('contract/index');
        }

        $model = new EmployeeContract();

        try {
            $model->terminateContract($contractId);
            Session::flash('success', 'Contract terminated.');
        } catch (Throwable $e) {
            Session::flash('error', 'Failed to terminate contract: ' . $e->getMessage());
        }

        redirect('contract/index');
    }

    public function approve(string $id): void
    {
        require_auth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('contract/index');
        }

        $contractId = (int) $id;
        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid request token.');
            redirect('contract/index');
        }

        try {
            $status = (new EmployeeContract())->advanceApproval($contractId, (string) $this->input('action', 'approve'), trim((string) $this->input('reason', '')));
            AuditLog::record('contract_approval', "Contract #{$contractId} moved to {$status}.", 'EmployeeContract', $contractId);
            Session::flash('success', 'Contract workflow updated: ' . $status . '.');
        } catch (Throwable $e) {
            Session::flash('error', $e->getMessage());
        }

        redirect('contract/index');
    }

    public function download(string $id): void
    {
        require_auth();
        require_role(['Super Admin', 'HR Officer', 'Finance Officer', 'Viewer']);

        $contractId = (int) $id;
        if ($contractId <= 0) {
            Session::flash('error', 'Invalid contract id.');
            redirect('contract/index');
        }

        $model    = new EmployeeContract();
        $contract = $model->findDetailed($contractId);

        if (!$contract) {
            Session::flash('error', 'Contract not found.');
            redirect('contract/index');
        }

        $employeeModel = new Employee();
        $employee      = $employeeModel->findDetailed((int) $contract['employee_id']) ?? [];

        $empSlug      = strtolower(preg_replace('/[^A-Za-z0-9]+/', '-', (string) ($employee['full_name'] ?? 'employee')));
        $contractSlug = strtolower((string) ($contract['contract_number'] ?? 'contract'));
        $downloadName = trim($empSlug . '-' . $contractSlug, '-');

        // Resolve dynamic template
        $tmplModel    = new ContractTemplate();
        $template     = null;
        $renderedBody = null;
        $missingFields = [];

        $templateId   = (int) ($contract['template_id'] ?? 0);
        if ($templateId > 0) {
            $template = $tmplModel->findDetailed($templateId);
        }

        if (!$template) {
            // Fall back to auto-resolve by structure + type
            $empSalary  = (new EmployeeSalary())->activeWithStructureForDate(
                (int) $contract['employee_id'],
                date('Y-m-d')
            );
            $ssId     = $empSalary ? (int) ($empSalary['salary_structure_id'] ?? 0) : null;
            $template = $tmplModel->resolve($ssId ?: null, (string) ($contract['contract_type'] ?? ''));
            if ($template && $empSalary) {
                $employee['salary_structure_name'] = (string) ($empSalary['structure_name'] ?? '');
                $employee['basic_pay']             = (float)  ($empSalary['basic_pay']      ?? 0);
                $employee['housing_allowance']     = (float)  ($empSalary['housing_allowance'] ?? 0);
                $employee['transport_allowance']   = (float)  ($empSalary['transport_allowance'] ?? 0);
                $employee['other_allowances']      = (float)  ($empSalary['other_allowances'] ?? 0);
            }
        } else {
            $empSalary = (new EmployeeSalary())->activeWithStructureForDate(
                (int) $contract['employee_id'],
                date('Y-m-d')
            );
            $employee['salary_structure_name'] = (string) ($empSalary['structure_name'] ?? '');
            $employee['basic_pay']             = (float)  ($empSalary['basic_pay']      ?? 0);
            $employee['housing_allowance']     = (float)  ($empSalary['housing_allowance'] ?? 0);
            $employee['transport_allowance']   = (float)  ($empSalary['transport_allowance'] ?? 0);
            $employee['other_allowances']      = (float)  ($empSalary['other_allowances'] ?? 0);
        }

        if ($template) {
            $tokenValues  = $tmplModel->buildTokenValues($contract, $employee);
            $missingFields = $tmplModel->missingFields((string) $template['body'], $tokenValues);
            $renderedBody = $tmplModel->renderBody((string) $template['body'], $tokenValues);
        }

        $this->renderAuth('contracts/download', [
            'contract'     => $contract,
            'employee'     => $employee,
            'downloadName' => $downloadName !== '' ? $downloadName : 'contract',
            'renderedBody' => $renderedBody,
            'missingFields'=> $missingFields,
        ]);
    }

    public function email(string $id): void
    {
        require_auth();
        require_role(['Super Admin', 'HR Officer', 'Finance Officer']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('contract/index');
        }

        $contractId = (int) $id;
        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid request token.');
            redirect('contract/index');
        }

        $result = $this->sendContractEmail($contractId);
        Session::flash($result['ok'] ? 'success' : 'error', $result['message']);
        redirect('contract/index');
    }

    public function emailAllActive(): void
    {
        require_auth();
        require_role(['Super Admin', 'HR Officer', 'Finance Officer']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('contract/index');
        }

        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid request token.');
            redirect('contract/index');
        }

        $contracts = array_filter(
            (new EmployeeContract())->listAll(),
            static fn(array $contract): bool => (string) ($contract['status'] ?? '') === 'Active'
        );

        $sent = 0;
        $skipped = 0;
        $failed = 0;
        $errors = [];

        foreach ($contracts as $contract) {
            $result = $this->sendContractEmail((int) $contract['id']);
            if ($result['ok']) {
                $sent++;
                continue;
            }

            $message = (string) $result['message'];
            if (str_contains($message, 'no email address')) {
                $skipped++;
            } else {
                $failed++;
                $errors[] = $message;
            }
        }

        $summary = "{$sent} contract email(s) sent.";
        if ($skipped > 0) {
            $summary .= " {$skipped} skipped because employee email is missing.";
        }
        if ($failed > 0) {
            Session::flash('error', $summary . " {$failed} failed. " . implode(' ', array_slice($errors, 0, 2)));
        } else {
            Session::flash('success', $summary);
        }

        redirect('contract/index');
    }

    public function renew(string $id): void
    {
        require_auth();
        require_role(['Super Admin', 'HR Officer']);

        $contractId = (int) $id;
        if ($contractId <= 0) {
            Session::flash('error', 'Invalid contract id.');
            redirect('contract/index');
        }

        $model = new EmployeeContract();
        $contract = $model->findDetailed($contractId);

        if (!$contract) {
            Session::flash('error', 'Contract not found.');
            redirect('contract/index');
        }

        $pendingRequest = (new ContractRenewalRequest())->pendingForEmployeeContract(
            (int) ($contract['employee_id'] ?? 0),
            $contractId
        );

        $this->render('contracts/renew', [
            'title'              => 'Renew Contract',
            'contract'           => $contract,
            'pendingRequest'     => $pendingRequest,
            'contractTypes'      => (new EmploymentType())->names(),
            'nextContractNumber' => $model->generateContractNumber(),
            'csrf'               => Session::csrfToken(),
            'flashError'         => Session::flash('error'),
            'old'                => $_SESSION['_old_contract_input'] ?? [],
        ]);

        unset($_SESSION['_old_contract_input']);
    }

    public function renewStore(string $id): void
    {
        require_auth();
        require_role(['Super Admin', 'HR Officer']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('contract/index');
        }

        $contractId = (int) $id;

        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid request token.');
            redirect('contract/renew/' . $contractId);
        }

        $contractType = (string) $this->input('contract_type', 'Contract');
        $startDate    = $this->normalizeDate((string) $this->input('start_date', ''));
        $endDate      = $this->normalizeDate((string) $this->input('end_date', ''));
        $notes        = $this->normalizeNullableString((string) $this->input('notes', ''));

        $_SESSION['_old_contract_input'] = [
            'contract_type' => $contractType,
            'start_date'    => $startDate,
            'end_date'      => $endDate,
            'notes'         => $notes,
        ];

        if ($startDate === null) {
            Session::flash('error', 'New start date is required.');
            redirect('contract/renew/' . $contractId);
        }

        $userId    = (int) ($_SESSION['auth_user']['id'] ?? 0);
        $model     = new EmployeeContract();
        $tmplModel = new ContractTemplate();

        // Resolve template for the renewal
        $originalForTmpl = $model->find($contractId);
        $empSalaryForTmpl = $originalForTmpl
            ? (new EmployeeSalary())->activeWithStructureForDate(
                (int) $originalForTmpl['employee_id'],
                date('Y-m-d')
            ) : null;
        $ssIdForTmpl  = $empSalaryForTmpl ? (int) ($empSalaryForTmpl['salary_structure_id'] ?? 0) : null;
        $matchedTmpl  = $tmplModel->resolve($ssIdForTmpl ?: null, $contractType);
        $renewTplId   = $matchedTmpl ? (int) $matchedTmpl['id'] : null;

        try {
            $original    = $model->findDetailed($contractId);
            $newId       = $model->renewContract(
                $contractId,
                $contractType,
                $startDate,
                $endDate,
                $notes,
                $userId > 0 ? $userId : null,
                $renewTplId
            );

            if ($original) {
                $empModel   = new Employee();
                $emp        = $empModel->findDetailed((int) $original['employee_id']) ?? [];
                $notifier   = new ContractNotification();
                $notifier->dispatchRenewal(
                    $newId,
                    (string) ($emp['full_name']      ?? $original['employee_name'] ?? ''),
                    (string) ($emp['employee_number'] ?? $original['employee_number'] ?? ''),
                    (string) ($emp['email']           ?? '')
                );
            }

            (new ContractRenewalRequest())->markRenewedForContract($contractId, $userId, 'Closed automatically when HR renewed the contract.');

            unset($_SESSION['_old_contract_input']);
            Session::flash('success', 'Contract renewed successfully.');
        } catch (Throwable $e) {
            Session::flash('error', 'Failed to renew contract: ' . $e->getMessage());
        }

        redirect('contract/index');
    }

    public function sendReminders(): void
    {
        require_auth();
        require_role(['Super Admin', 'HR Officer']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('contract/index');
        }

        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid request token.');
            redirect('contract/index');
        }

        $notifier = new ContractNotification();
        $result   = $notifier->dispatchAll();

        if ($result['sent'] > 0) {
            Session::flash('success', $result['sent'] . ' notification email(s) sent successfully.');
        } elseif (!empty($result['errors'])) {
            Session::flash('error', 'Notification errors: ' . implode('; ', array_slice($result['errors'], 0, 3)));
        } else {
            Session::flash('success', 'No pending notifications to send.');
        }

        redirect('contract/index');
    }

    private function sendContractEmail(int $contractId): array
    {
        $model = new EmployeeContract();
        $contract = $model->findDetailed($contractId);

        if (!$contract) {
            return ['ok' => false, 'message' => 'Contract not found.'];
        }

        $employee = (new Employee())->findDetailed((int) $contract['employee_id']) ?? [];
        $email = trim((string) ($employee['email'] ?? $contract['employee_email'] ?? ''));
        $employeeName = (string) ($employee['full_name'] ?? $contract['employee_name'] ?? 'Employee');

        if ($email === '') {
            return ['ok' => false, 'message' => 'Employee has no email address on file.'];
        }

        $tmplModel = new ContractTemplate();
        $template = null;

        $templateId = (int) ($contract['template_id'] ?? 0);
        if ($templateId > 0) {
            $template = $tmplModel->findDetailed($templateId);
        }

        $empSalary = (new EmployeeSalary())->activeWithStructureForDate(
            (int) $contract['employee_id'],
            date('Y-m-d')
        );

        if (!$template) {
            $ssId = $empSalary ? (int) ($empSalary['salary_structure_id'] ?? 0) : null;
            $template = $tmplModel->resolve($ssId ?: null, (string) ($contract['contract_type'] ?? ''));
        }

        if ($empSalary) {
            $employee['salary_structure_name'] = (string) ($empSalary['structure_name'] ?? '');
            $employee['basic_pay'] = (float) ($empSalary['basic_pay'] ?? 0);
            $employee['housing_allowance'] = (float) ($empSalary['housing_allowance'] ?? 0);
            $employee['transport_allowance'] = (float) ($empSalary['transport_allowance'] ?? 0);
            $employee['other_allowances'] = (float) ($empSalary['other_allowances'] ?? 0);
        }

        $company = current_company() ?? [];
        $companyName = (string) ($company['name'] ?? 'Payroll Office');
        $renderedBody = '<p>Your employment contract is ready.</p>';

        if ($template) {
            $values = $tmplModel->buildTokenValues($contract, $employee);
            $renderedBody = $tmplModel->renderBody((string) $template['body'], $values);
        }

        $emailTemplates = new CompanyEmailTemplate();
        $tokens = $this->contractEmailTokens($contract, $employee, $company);
        $subject = $emailTemplates->renderSubject('contract', $tokens);
        $html = $emailTemplates->renderBody('contract', $tokens);
        $attachments = [];

        if ($emailTemplates->template('contract')['attach_document']) {
            $filename = strtolower(preg_replace('/[^A-Za-z0-9_-]+/', '-', (string) ($contract['contract_number'] ?? 'contract'))) ?: 'contract';
            $attachments[] = [
                'filename' => $filename . '.html',
                'mime' => 'text/html; charset=UTF-8',
                'content' => $this->buildContractAttachmentHtml($renderedBody, $contract, $employee, $companyName),
            ];
        }

        $mailer = (new ContractNotification())->buildMailer();
        $ok = $mailer->send($email, $employeeName, $subject, $html, $attachments);

        if ($ok) {
            AuditLog::record('contract_email', "Contract #{$contractId} emailed to {$email}.", 'EmployeeContract', $contractId);
            return ['ok' => true, 'message' => "Contract emailed to {$employeeName} ({$email})."];
        }

        $detail = $mailer->lastError() !== '' ? ' ' . $mailer->lastError() : ' Check SMTP settings.';
        return ['ok' => false, 'message' => "Failed to email contract to {$employeeName} ({$email}).{$detail}"];
    }

    private function contractEmailTokens(array $contract, array $employee, array $company): array
    {
        return [
            'company_name' => (string) ($company['name'] ?? app_product_name()),
            'company_email' => (string) ($company['email'] ?? ''),
            'company_phone' => (string) ($company['phone'] ?? ''),
            'company_address' => (string) ($company['address'] ?? ''),
            'employee_name' => (string) ($employee['full_name'] ?? $contract['employee_name'] ?? 'Employee'),
            'employee_number' => (string) ($employee['employee_number'] ?? $contract['employee_number'] ?? ''),
            'employee_email' => (string) ($employee['email'] ?? $contract['employee_email'] ?? ''),
            'contract_number' => (string) ($contract['contract_number'] ?? ''),
            'contract_type' => (string) ($contract['contract_type'] ?? ''),
            'contract_start_date' => (string) ($contract['start_date'] ?? ''),
            'contract_end_date' => (string) ($contract['end_date'] ?? 'Open-ended'),
            'today' => date('d M Y'),
        ];
    }

    private function buildContractAttachmentHtml(string $contractHtml, array $contract, array $employee, string $companyName): string
    {
        $company = e($companyName);
        $employeeName = e((string) ($employee['full_name'] ?? $contract['employee_name'] ?? 'Employee'));
        $contractNumber = e((string) ($contract['contract_number'] ?? 'Contract'));

        return <<<HTML
        <!doctype html>
        <html>
        <head>
            <meta charset="utf-8">
            <title>{$contractNumber}</title>
            <style>
                body{font-family:Arial,sans-serif;color:#111827;margin:36px;line-height:1.55}
                .header{border-bottom:2px solid #1a3a2a;padding-bottom:14px;margin-bottom:24px}
                .meta{color:#475569;font-size:13px;margin-top:4px}
                .document{font-family:"Times New Roman",serif;font-size:15px}
                @media print{body{margin:24mm}.no-print{display:none}}
            </style>
        </head>
        <body>
            <div class="header">
                <h1 style="margin:0">{$company}</h1>
                <div class="meta">Employment Contract | {$contractNumber} | {$employeeName}</div>
            </div>
            <div class="document">{$contractHtml}</div>
        </body>
        </html>
        HTML;
    }

    private function wrapContractEmailHtml(string $contractHtml, string $companyName, string $employeeName): string
    {
        $company = e($companyName);
        $name = e($employeeName);

        return <<<HTML
        <html><body style="font-family:Arial,sans-serif;color:#1f2937;background:#f8fafc;padding:24px">
            <div style="max-width:760px;margin:0 auto;background:#fff;border:1px solid #e5e7eb;border-radius:8px;overflow:hidden">
                <div style="background:#1a3a2a;color:#fff;padding:18px 22px">
                    <h2 style="margin:0;font-size:20px">Employment Contract</h2>
                    <div style="opacity:.85;font-size:13px">{$company}</div>
                </div>
                <div style="padding:22px">
                    <p>Hello <strong>{$name}</strong>,</p>
                    <p>Please find your employment contract details below.</p>
                    <div style="border-top:1px solid #e5e7eb;margin-top:18px;padding-top:18px;font-family:'Times New Roman',serif;line-height:1.65">
                        {$contractHtml}
                    </div>
                    <p style="font-size:12px;color:#64748b;margin-top:18px">This is a computer-generated contract email from {$company}.</p>
                </div>
            </div>
        </body></html>
        HTML;
    }

    private function collectInput(): array
    {
        $employeeId = (int) $this->input('employee_id', 0);

        return [
            'employee_id'   => $employeeId > 0 ? $employeeId : null,
            'contract_type' => (string) $this->input('contract_type', 'Contract'),
            'start_date'    => $this->normalizeDate((string) $this->input('start_date', '')),
            'end_date'      => $this->normalizeDate((string) $this->input('end_date', '')),
            'notes'         => $this->normalizeNullableString((string) $this->input('notes', '')),
        ];
    }

    private function validate(array $data): ?string
    {
        if (empty($data['employee_id'])) {
            return 'Employee is required.';
        }

        if (!in_array($data['contract_type'], (new EmploymentType())->names(), true)) {
            return 'Invalid contract type.';
        }

        if ($data['start_date'] === null) {
            return 'Start date is required.';
        }

        if ($data['end_date'] !== null && $data['end_date'] < $data['start_date']) {
            return 'End date cannot be before start date.';
        }

        return null;
    }

    private function normalizeDate(string $value): ?string
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            return null;
        }

        $date = date_create($trimmed);

        return $date === false ? null : $date->format('Y-m-d');
    }

    private function normalizeNullableString(string $value): ?string
    {
        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
