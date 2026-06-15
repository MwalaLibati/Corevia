<?php

declare(strict_types=1);

class OnboardingController extends Controller
{
    public function index(): void
    {
        require_auth();
        require_role(['Super Admin', 'HR Officer']);

        $status = trim((string) $this->input('status', ''));
        $model = new EmployeeOnboardingRequest();

        $this->render('onboarding/index', [
            'title' => 'Onboarding Links',
            'requests' => $model->listAll($status),
            'status' => $status,
            'csrf' => Session::csrfToken(),
            'flashSuccess' => Session::flash('success'),
            'flashError' => Session::flash('error'),
        ]);
    }

    public function create(): void
    {
        require_auth();
        require_role(['Super Admin', 'HR Officer']);

        $employeeModel = new Employee();
        $this->render('onboarding/create', [
            'title' => 'Create Onboarding Link',
            'csrf' => Session::csrfToken(),
            'employees' => $employeeModel->listWithDepartment(),
            'departments' => $employeeModel->departments(),
            'employmentTypes' => (new EmploymentType())->active(),
            'requiredFieldOptions' => $this->availableRequiredFields(),
            'old' => $_SESSION['_old_onboarding_input'] ?? [],
            'flashError' => Session::flash('error'),
        ]);
        unset($_SESSION['_old_onboarding_input']);
    }

    public function store(): void
    {
        require_auth();
        require_role(['Super Admin', 'HR Officer']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('onboarding/index');
        }
        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid request token.');
            redirect('onboarding/create');
        }

        $data = $this->collectInvitationInput();
        $_SESSION['_old_onboarding_input'] = $data;
        $error = $this->validateInvitation($data);
        if ($error !== null) {
            Session::flash('error', $error);
            redirect('onboarding/create');
        }

        try {
            $id = (new EmployeeOnboardingRequest())->createInvitation($data);
            AuditLog::record('onboarding_invite_create', 'Created onboarding link for ' . $data['invited_full_name'], 'EmployeeOnboardingRequest', $id, 'admin', [
                'selected_employee_id' => $data['selected_employee_id'] ?? null,
                'required_fields' => $data['required_fields_json'] ?? null,
            ]);
            unset($_SESSION['_old_onboarding_input']);
            Session::flash('success', 'Onboarding link created. Copy and send it to the employee.');
            redirect('onboarding/show/' . $id);
        } catch (Throwable $e) {
            Session::flash('error', 'Failed to create onboarding link: ' . $e->getMessage());
            redirect('onboarding/create');
        }
    }

    public function show(string $id): void
    {
        require_auth();
        require_role(['Super Admin', 'HR Officer']);

        $requestId = (int) $id;
        $model = new EmployeeOnboardingRequest();
        $request = $model->findDetailed($requestId);
        if (!$request) {
            Session::flash('error', 'Onboarding request not found.');
            redirect('onboarding/index');
        }

        $this->render('onboarding/show', [
            'title' => 'Onboarding Request',
            'request' => $request,
            'documents' => $model->documents($requestId),
            'publicLink' => public_url('onboarding/form/' . (string) $request['token']),
            'approvalStep' => (new WorkflowDefinition())->firstStep('employee_onboarding'),
            'canApproveOnboarding' => (new WorkflowDefinition())->canCurrentUserApprove('employee_onboarding'),
            'csrf' => Session::csrfToken(),
            'flashSuccess' => Session::flash('success'),
            'flashError' => Session::flash('error'),
        ]);
    }

    public function approve(string $id): void
    {
        require_auth();
        require_role(['Super Admin', 'HR Officer']);

        $requestId = (int) $id;
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('onboarding/show/' . $requestId);
        }
        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid request token.');
            redirect('onboarding/show/' . $requestId);
        }

        $model = new EmployeeOnboardingRequest();
        $request = $model->findDetailed($requestId);
        if (!$request || (string) ($request['status'] ?? '') !== 'Submitted') {
            Session::flash('error', 'Only submitted onboarding requests can be approved.');
            redirect('onboarding/show/' . $requestId);
        }
        if (!(new WorkflowDefinition())->canCurrentUserApprove('employee_onboarding')) {
            Session::flash('error', 'You are not assigned to the current onboarding approval step.');
            redirect('onboarding/show/' . $requestId);
        }

        try {
            $employeeId = $this->applyEmployeeFromRequest($request);
            $model->approve($requestId, $employeeId, (int) (current_user()['id'] ?? 0));
            WorkflowEvent::record('employee_onboarding', 'Employee', $employeeId, null, 'Approved', 'onboarding_link_approved', 'Approved from onboarding data capture link.');
            AuditLog::record('onboarding_approve', 'Approved onboarding request.', 'EmployeeOnboardingRequest', $requestId, 'admin', ['employee_id' => $employeeId]);
            Session::flash('success', !empty($request['selected_employee_id']) ? 'Onboarding approved and employee profile updated.' : 'Onboarding approved and employee profile created.');
            redirect('employee/profile/' . $employeeId);
        } catch (Throwable $e) {
            Session::flash('error', 'Approval failed: ' . $e->getMessage());
            redirect('onboarding/show/' . $requestId);
        }
    }

    public function cancel(string $id): void
    {
        require_auth();
        require_role(['Super Admin', 'HR Officer']);

        $requestId = (int) $id;
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid request.');
            redirect('onboarding/show/' . $requestId);
        }

        (new EmployeeOnboardingRequest())->cancel($requestId);
        AuditLog::record('onboarding_cancel', 'Cancelled onboarding request.', 'EmployeeOnboardingRequest', $requestId);
        Session::flash('success', 'Onboarding request cancelled.');
        redirect('onboarding/index');
    }

    public function form(string $token): void
    {
        $model = new EmployeeOnboardingRequest();
        $request = $this->publicRequest($token);

        if ($this->isExpired($request)) {
            $this->renderAuth('onboarding/public-expired', ['request' => $request]);
            return;
        }

        $model->markOpened((int) $request['id']);
        $this->renderAuth('onboarding/public-form', [
            'request' => $request,
            'requiredFields' => $this->requiredFieldsFromRequest($request),
            'csrf' => Session::csrfToken(),
            'flashError' => Session::flash('error'),
            'old' => $_SESSION['_old_public_onboarding'] ?? [],
        ]);
        unset($_SESSION['_old_public_onboarding']);
    }

    public function submit(string $token): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('onboarding/form/' . $token);
        }
        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Your form session expired. Please submit again.');
            redirect('onboarding/form/' . $token);
        }

        $model = new EmployeeOnboardingRequest();
        $request = $this->publicRequest($token);
        if ($this->isExpired($request) || in_array((string) $request['status'], ['Submitted','Approved','Cancelled','Expired'], true)) {
            $this->renderAuth('onboarding/public-expired', ['request' => $request]);
            return;
        }

        $data = $this->collectPublicInput($request);
        $_SESSION['_old_public_onboarding'] = $data;
        $error = $this->validatePublicInput($data, $request);
        if ($error !== null) {
            Session::flash('error', $error);
            redirect('onboarding/form/' . $token);
        }

        try {
            $model->submit((int) $request['id'], $data);
            $this->saveUploadedDocuments((int) $request['id'], (int) $request['company_id']);
            AuditLog::record('onboarding_submit', 'Employee submitted onboarding data.', 'EmployeeOnboardingRequest', (int) $request['id'], 'public');
            unset($_SESSION['_old_public_onboarding']);
            $this->renderAuth('onboarding/public-success', ['request' => $request]);
        } catch (Throwable $e) {
            Session::flash('error', 'Submission failed: ' . $e->getMessage());
            redirect('onboarding/form/' . $token);
        }
    }

    private function collectInvitationInput(): array
    {
        $selectedEmployeeId = (int) $this->input('selected_employee_id', 0);
        $selectedEmployee = $selectedEmployeeId > 0 ? (new Employee())->find($selectedEmployeeId) : null;
        $departmentId = (int) $this->input('department_id', 0);
        $expiresDays = max(1, min(30, (int) $this->input('expires_days', 7)));
        $requiredFields = $this->selectedRequiredFields();
        return [
            'company_id' => Tenant::id(),
            'selected_employee_id' => $selectedEmployeeId > 0 ? $selectedEmployeeId : null,
            'invited_full_name' => trim((string) $this->input('invited_full_name', '')) ?: (string) ($selectedEmployee['full_name'] ?? ''),
            'invited_email' => $this->nullable((string) $this->input('invited_email', '')) ?? $this->nullable((string) ($selectedEmployee['email'] ?? '')),
            'invited_phone' => $this->nullable((string) $this->input('invited_phone', '')) ?? $this->nullable((string) ($selectedEmployee['phone'] ?? '')),
            'department_id' => $departmentId > 0 ? $departmentId : (!empty($selectedEmployee['department_id']) ? (int) $selectedEmployee['department_id'] : null),
            'designation' => $this->nullable((string) $this->input('designation', '')) ?? $this->nullable((string) ($selectedEmployee['designation'] ?? '')),
            'employment_type' => trim((string) $this->input('employment_type', '')) ?: (string) ($selectedEmployee['employment_type'] ?? 'Permanent'),
            'expected_start_date' => $this->dateOrNull((string) $this->input('expected_start_date', '')),
            'expires_at' => date('Y-m-d H:i:s', strtotime('+' . $expiresDays . ' days')),
            'created_by' => (int) (current_user()['id'] ?? 0) ?: null,
            'hr_notes' => $this->nullable((string) $this->input('hr_notes', '')),
            'required_fields_json' => json_encode($requiredFields, JSON_UNESCAPED_SLASHES),
        ];
    }

    private function validateInvitation(array $data): ?string
    {
        if ((int) ($data['company_id'] ?? 0) <= 0) {
            return 'Company context is required.';
        }
        if ((string) ($data['invited_full_name'] ?? '') === '') {
            return 'Employee name is required.';
        }
        if (!empty($data['invited_email']) && !filter_var((string) $data['invited_email'], FILTER_VALIDATE_EMAIL)) {
            return 'Employee email format is invalid.';
        }
        if (!empty($data['selected_employee_id']) && !(new Employee())->find((int) $data['selected_employee_id'])) {
            return 'Selected employee was not found.';
        }
        return null;
    }

    private function collectPublicInput(array $request): array
    {
        return [
            'full_name' => trim((string) $this->input('full_name', $this->requestDefault($request, 'full_name'))),
            'email' => $this->nullable((string) $this->input('email', $this->requestDefault($request, 'email'))),
            'phone' => $this->nullable((string) $this->input('phone', $this->requestDefault($request, 'phone'))),
            'nrc_number' => $this->nullable((string) $this->input('nrc_number', $this->requestDefault($request, 'nrc_number'))),
            'date_of_birth' => $this->dateOrNull((string) $this->input('date_of_birth', $this->requestDefault($request, 'date_of_birth'))),
            'gender' => $this->nullable((string) $this->input('gender', '')),
            'address' => $this->nullable((string) $this->input('address', $this->requestDefault($request, 'address'))),
            'napsa_number' => $this->nullable((string) $this->input('napsa_number', $this->requestDefault($request, 'napsa_number'))),
            'tpin' => $this->nullable((string) $this->input('tpin', $this->requestDefault($request, 'tpin'))),
            'nhima_number' => $this->nullable((string) $this->input('nhima_number', '')),
            'bank_name' => $this->nullable((string) $this->input('bank_name', $this->requestDefault($request, 'bank_name'))),
            'bank_account_number' => $this->nullable((string) $this->input('bank_account_number', $this->requestDefault($request, 'bank_account_number'))),
            'next_of_kin_name' => $this->nullable((string) $this->input('next_of_kin_name', '')),
            'next_of_kin_phone' => $this->nullable((string) $this->input('next_of_kin_phone', '')),
            'next_of_kin_relationship' => $this->nullable((string) $this->input('next_of_kin_relationship', '')),
            'notes' => $this->nullable((string) $this->input('notes', '')),
        ];
    }

    private function validatePublicInput(array $data, array $request): ?string
    {
        $required = $this->requiredFieldsFromRequest($request);
        foreach ($required as $key => $label) {
            if (empty($data[$key])) {
                return $label . ' is required.';
            }
        }
        if (!empty($data['email']) && !filter_var((string) $data['email'], FILTER_VALIDATE_EMAIL)) {
            return 'Email format is invalid.';
        }
        return null;
    }

    private function applyEmployeeFromRequest(array $request): int
    {
        $employeeModel = new Employee();
        $email = $this->nullable((string) ($request['email'] ?? $request['invited_email'] ?? ''));
        $selectedEmployeeId = (int) ($request['selected_employee_id'] ?? 0);
        if ($email !== null && $employeeModel->emailExists($email, $selectedEmployeeId > 0 ? $selectedEmployeeId : null)) {
            throw new RuntimeException('An employee with this email already exists.');
        }

        $data = [
            'full_name' => trim((string) ($request['full_name'] ?? $request['invited_full_name'] ?? '')),
            'email' => $email,
            'phone' => $this->nullable((string) ($request['phone'] ?? $request['invited_phone'] ?? '')),
            'nrc_number' => $this->nullable((string) ($request['nrc_number'] ?? '')),
            'date_of_birth' => $this->dateOrNull((string) ($request['date_of_birth'] ?? '')),
            'address' => $this->nullable((string) ($request['address'] ?? '')),
            'napsa_number' => $this->nullable((string) ($request['napsa_number'] ?? '')),
            'tpin' => $this->nullable((string) ($request['tpin'] ?? '')),
            'department_id' => !empty($request['department_id']) ? (int) $request['department_id'] : null,
            'designation' => $this->nullable((string) ($request['designation'] ?? '')),
            'employment_type' => (string) ($request['employment_type'] ?? 'Permanent'),
            'bank_name' => $this->nullable((string) ($request['bank_name'] ?? '')),
            'bank_account_number' => $this->nullable((string) ($request['bank_account_number'] ?? '')),
            'contract_status' => 'Active',
            'lifecycle_status' => 'Probation',
            'hired_at' => $this->dateOrNull((string) ($request['expected_start_date'] ?? '')),
            'probation_end_date' => null,
        ];

        if ($selectedEmployeeId > 0) {
            $existing = $employeeModel->find($selectedEmployeeId);
            if (!$existing) {
                throw new RuntimeException('Selected employee could not be found.');
            }

            unset($data['contract_status'], $data['lifecycle_status'], $data['probation_end_date']);
            $data = array_filter($data, static fn($value): bool => $value !== null && $value !== '');
            $employeeModel->update($selectedEmployeeId, $data);
            return $selectedEmployeeId;
        }

        $data['hired_at'] = $data['hired_at'] ?? date('Y-m-d');
        $data['probation_end_date'] = null;
        $data['employee_number'] = $employeeModel->generateNextEmployeeNumber();
        return $employeeModel->insert($data);
    }

    private function saveUploadedDocuments(int $requestId, int $companyId): void
    {
        if (empty($_FILES['documents']) || !is_array($_FILES['documents']['name'] ?? null)) {
            return;
        }

        $allowed = ['application/pdf', 'image/jpeg', 'image/png'];
        $baseDir = BASE_PATH . '/uploads/onboarding/' . $requestId;
        if (!is_dir($baseDir)) {
            mkdir($baseDir, 0775, true);
        }

        $model = new EmployeeOnboardingRequest();
        foreach ($_FILES['documents']['name'] as $index => $name) {
            if (($_FILES['documents']['error'][$index] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                continue;
            }
            $tmp = (string) ($_FILES['documents']['tmp_name'][$index] ?? '');
            $size = (int) ($_FILES['documents']['size'][$index] ?? 0);
            $mime = (string) (mime_content_type($tmp) ?: '');
            if ($size <= 0 || $size > 5 * 1024 * 1024 || !in_array($mime, $allowed, true)) {
                continue;
            }

            $extension = strtolower(pathinfo((string) $name, PATHINFO_EXTENSION));
            if (!in_array($extension, ['pdf', 'jpg', 'jpeg', 'png'], true)) {
                continue;
            }
            $safeName = 'doc-' . date('YmdHis') . '-' . bin2hex(random_bytes(4)) . '.' . $extension;
            $target = $baseDir . '/' . $safeName;
            if (move_uploaded_file($tmp, $target)) {
                $model->addDocument($requestId, $companyId, [
                    'document_type' => 'Supporting Document',
                    'original_name' => (string) $name,
                    'stored_path' => 'uploads/onboarding/' . $requestId . '/' . $safeName,
                    'mime_type' => $mime,
                    'file_size' => $size,
                ]);
            }
        }
    }

    private function publicRequest(string $token): array
    {
        $request = (new EmployeeOnboardingRequest())->findByToken($token);
        if (!$request) {
            http_response_code(404);
            exit('Onboarding link not found.');
        }
        return $request;
    }

    private function selectedRequiredFields(): array
    {
        $labels = $this->availableRequiredFields();
        $selected = $_POST['required_fields'] ?? [];
        if (!is_array($selected) || $selected === []) {
            return $this->defaultRequiredFields();
        }

        return array_values(array_intersect(array_keys($labels), array_map('strval', $selected)));
    }

    private function requiredFieldsFromRequest(array $request): array
    {
        $labels = $this->availableRequiredFields();
        $decoded = json_decode((string) ($request['required_fields_json'] ?? ''), true);
        if (!is_array($decoded) || $decoded === []) {
            $decoded = $this->defaultRequiredFields();
        }

        $required = [];
        foreach ($decoded as $field) {
            if (isset($labels[$field])) {
                $required[$field] = $labels[$field];
            }
        }

        return $required;
    }

    private function availableRequiredFields(): array
    {
        return [
            'full_name' => 'Full name',
            'email' => 'Email',
            'phone' => 'Phone',
            'nrc_number' => 'NRC number',
            'date_of_birth' => 'Date of birth',
            'napsa_number' => 'NAPSA number',
            'tpin' => 'TPIN',
            'nhima_number' => 'NHIMA number',
            'bank_name' => 'Bank name',
            'bank_account_number' => 'Bank account number',
            'next_of_kin_name' => 'Next of kin name',
            'next_of_kin_phone' => 'Next of kin phone',
        ];
    }

    private function defaultRequiredFields(): array
    {
        return ['full_name', 'email', 'phone', 'nrc_number', 'date_of_birth'];
    }

    private function requestDefault(array $request, string $field): string
    {
        $selectedKey = 'selected_employee_' . $field;
        return (string) ($request[$field] ?? $request[$selectedKey] ?? $request['invited_' . $field] ?? '');
    }

    private function isExpired(array $request): bool
    {
        return strtotime((string) ($request['expires_at'] ?? '')) < time();
    }

    private function nullable(string $value): ?string
    {
        $value = trim($value);
        return $value === '' ? null : $value;
    }

    private function dateOrNull(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }
        $time = strtotime($value);
        return $time === false ? null : date('Y-m-d', $time);
    }
}
