<?php

declare(strict_types=1);

/**
 * Employee management controller.
 */

class EmployeeController extends Controller
{
    public function index(): void
    {
        require_auth();
        require_role(['Super Admin', 'HR Officer', 'Finance Officer']);

        $employeeModel = new Employee();
        $search = trim((string) $this->input('search', ''));
        $employees = $search === '' ? $employeeModel->listWithDepartment() : $employeeModel->search($search);

        $this->render('employees/index', [
            'title' => 'Employees',
            'employees' => $employees,
            'search' => $search,
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

        $this->render('employees/create', [
            'title' => 'Create Employee',
            'csrf' => Session::csrfToken(),
            'departments' => $employeeModel->departments(),
            'branches' => $employeeModel->branches(),
            'employmentTypes' => (new EmploymentType())->active(),
            'flashError' => Session::flash('error'),
            'old' => $_SESSION['_old_employee_input'] ?? [],
            'nextEmployeeNumber' => $employeeModel->generateNextEmployeeNumber(),
        ]);

        unset($_SESSION['_old_employee_input']);
    }

    public function profile(string $id): void
    {
        require_auth();
        require_role(['Super Admin', 'HR Officer', 'Finance Officer']);

        $employeeId = (int) $id;
        if ($employeeId <= 0) {
            Session::flash('error', 'Invalid employee id.');
            redirect('employee/index');
        }

        $employeeModel = new Employee();
        $employee = $employeeModel->findDetailed($employeeId);
        if (!$employee) {
            Session::flash('error', 'Employee not found.');
            redirect('employee/index');
        }

        $contractModel = new EmployeeContract();
        $salaryModel = new EmployeeSalary();
        $activeContract = $contractModel->activeForEmployee($employeeId);
        $activeSalary = $salaryModel->activeWithStructureForDate($employeeId, date('Y-m-d'));
        $payrollSummary = $employeeModel->payrollProfileSummary($employeeId);
        $leaveSummary = $employeeModel->leaveProfileSummary($employeeId);

        $gratuityPolicy = $this->configuredGratuityPolicy();
        $checklists = new EmployeeLifecycleChecklist();
        $letters = new EmployeeGeneratedLetter();
        $letterTemplates = new EmployeeLetterTemplate();

        $this->render('employees/profile', [
            'title' => 'Employee Profile',
            'employee' => $employee,
            'payrollSummary' => $payrollSummary,
            'payrollHistory' => $employeeModel->payrollProfileHistory($employeeId),
            'leaveSummary' => $leaveSummary,
            'activeContract' => $activeContract,
            'contractHistory' => $contractModel->listForEmployee($employeeId),
            'activeSalary' => $activeSalary,
            'activeAdvance' => (new SalaryAdvance())->activeForEmployee($employeeId),
            'gratuityEstimate' => $this->estimateGratuity($activeContract, $activeSalary, $gratuityPolicy),
            'lifecycleHistory' => (new EmployeeLifecycle())->forEmployee($employeeId),
            'lifecycleEventTypes' => EmployeeLifecycle::EVENT_TYPES,
            'onboardingChecklist' => $checklists->forEmployee($employeeId, 'Onboarding'),
            'exitChecklist' => $checklists->forEmployee($employeeId, 'Exit'),
            'lifecycleReminders' => (new EmployeeLifecycle())->reminders(45),
            'disciplinaryRecords' => (new EmployeeDisciplinaryRecord())->forEmployee($employeeId),
            'finalDue' => (new EmployeeFinalDue())->latestForEmployee($employeeId),
            'generatedLetters' => $letters->forEmployee($employeeId),
            'letterTemplates' => $letterTemplates->allTemplates(),
            'letterTypes' => EmployeeLetterTemplate::TYPES,
            'profileChangeRequests' => (new EmployeeProfileChangeRequest())->listPending($employeeId),
            'departments' => $employeeModel->departments(),
            'branches' => $employeeModel->branches(),
            'csrf' => Session::csrfToken(),
        ]);
    }

    public function profileChangeReview(string $employeeId = '0', string $requestId = '0'): void
    {
        require_auth();
        require_role(['Super Admin', 'HR Officer']);

        $empId = (int) $employeeId;
        $reqId = (int) $requestId;
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('employee/profile/' . $empId);
        }
        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid request token.');
            redirect('employee/profile/' . $empId);
        }

        $action = (string) $this->input('action', 'approve');
        $notes = trim((string) $this->input('review_notes', ''));
        $model = new EmployeeProfileChangeRequest();

        try {
            if ($action === 'reject') {
                $model->reject($reqId, (int) (current_user()['id'] ?? 0), $notes);
                AuditLog::record('profile_change_reject', 'Rejected employee profile change request.', 'EmployeeProfileChangeRequest', $reqId);
                Session::flash('success', 'Profile change request rejected.');
            } else {
                $model->approve($reqId, (int) (current_user()['id'] ?? 0));
                AuditLog::record('profile_change_approve', 'Approved employee profile change request.', 'EmployeeProfileChangeRequest', $reqId);
                Session::flash('success', 'Profile change request approved and employee record updated.');
            }
        } catch (Throwable $e) {
            Session::flash('error', 'Profile change review failed: ' . $e->getMessage());
        }

        redirect('employee/profile/' . $empId);
    }

    public function lifecycleEvent(string $id): void
    {
        require_auth();
        require_role(['Super Admin', 'HR Officer']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('employee/profile/' . (int) $id);
        }
        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid request token.');
            redirect('employee/profile/' . (int) $id);
        }

        $employeeId = (int) $id;
        $data = [
            'event_type' => (string) $this->input('event_type', 'Other'),
            'effective_date' => $this->normalizeDate((string) $this->input('effective_date', '')) ?? date('Y-m-d'),
            'probation_end_date' => $this->normalizeDate((string) $this->input('probation_end_date', '')),
            'to_department_id' => (int) $this->input('to_department_id', 0),
            'to_designation' => trim((string) $this->input('to_designation', '')),
            'notes' => trim((string) $this->input('notes', '')),
            'created_by' => (int) (current_user()['id'] ?? 0) ?: null,
        ];

        try {
            (new EmployeeLifecycle())->record($employeeId, $data);
            $workflowType = str_contains(strtolower($data['event_type']), 'termination') || str_contains(strtolower($data['event_type']), 'terminated')
                ? 'employee_termination'
                : (str_contains(strtolower($data['event_type']), 'onboard') ? 'employee_onboarding' : 'employee_lifecycle');
            WorkflowEvent::record($workflowType, 'Employee', $employeeId, null, (string) $data['event_type'], 'lifecycle_event', $data['notes']);
            AuditLog::record('lifecycle_event', 'Recorded lifecycle event: ' . $data['event_type'], 'Employee', $employeeId);
            Session::flash('success', 'Employee lifecycle event recorded.');
        } catch (Throwable $e) {
            Session::flash('error', 'Lifecycle event could not be recorded: ' . $e->getMessage());
        }

        redirect('employee/profile/' . $employeeId);
    }

    public function store(): void
    {
        require_auth();
        require_role(['Super Admin', 'HR Officer']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('employee/index');
        }

        $token = (string) $this->input('_csrf', '');
        if (!Session::verifyCsrf($token)) {
            Session::flash('error', 'Invalid request token.');
            redirect('employee/create');
        }

        $employeeModel = new Employee();

        $data = $this->collectEmployeeInput();
        $data['employee_number'] = $employeeModel->generateNextEmployeeNumber();
        $_SESSION['_old_employee_input'] = $data;

        $error = $this->validateEmployeeInput($data);
        if ($error !== null) {
            Session::flash('error', $error);
            redirect('employee/create');
        }

        if ($data['email'] !== null && $employeeModel->emailExists($data['email'])) {
            Session::flash('error', 'Email already exists.');
            redirect('employee/create');
        }

        try {
            $employeeId = $employeeModel->insert($data);
            WorkflowEvent::record('employee_onboarding', 'Employee', (int) $employeeId, null, 'Created', 'employee_create', 'Employee profile created by HR.');
            AuditLog::record('created', 'Created employee ' . $data['full_name'], 'Employee', (int) $employeeId);
            unset($_SESSION['_old_employee_input']);
            Session::flash('success', 'Employee created successfully.');
            redirect('employee/index');
        } catch (PDOException) {
            Session::flash('error', 'Failed to create employee. Please try again.');
            redirect('employee/create');
        }
    }

    public function edit(string $id): void
    {
        require_auth();
        require_role(['Super Admin', 'HR Officer']);

        $employeeId = (int) $id;
        if ($employeeId <= 0) {
            Session::flash('error', 'Invalid employee id.');
            redirect('employee/index');
        }

        $employeeModel = new Employee();
        $salaryStructureModel = new SalaryStructure();
        $employeeSalaryModel = new EmployeeSalary();
        $employeeDeductionModel = new EmployeeDeduction();
        $contractModel = new EmployeeContract();
        $employee = $employeeModel->findDetailed($employeeId);

        if (!$employee) {
            Session::flash('error', 'Employee not found.');
            redirect('employee/index');
        }

        $this->render('employees/edit', [
            'title' => 'Edit Employee',
            'employee' => $employee,
            'departments' => $employeeModel->departments(),
            'branches' => $employeeModel->branches(),
            'employmentTypes' => (new EmploymentType())->active(),
            'salaryStructures' => $salaryStructureModel->findAll(),
            'activeSalaryAssignment' => $employeeSalaryModel->activeWithStructure($employeeId),
            'salaryAssignmentHistory' => $employeeSalaryModel->historyWithStructure($employeeId),
            'deductionTypes' => $employeeDeductionModel->deductionTypes(),
            'activeDeductionAssignments' => $employeeDeductionModel->activeForEmployee($employeeId),
            'deductionAssignmentHistory' => $employeeDeductionModel->historyForEmployee($employeeId),
            'activeContract' => $contractModel->activeForEmployee($employeeId),
            'contractHistory' => $contractModel->listForEmployee($employeeId),
            'csrf' => Session::csrfToken(),
            'flashError' => Session::flash('error'),
            'flashSuccess' => Session::flash('success'),
            'old' => $_SESSION['_old_employee_input'] ?? [],
            'salaryOld' => $_SESSION['_old_salary_assignment_input'] ?? [],
            'deductionOld' => $_SESSION['_old_deduction_assignment_input'] ?? [],
        ]);

        unset($_SESSION['_old_employee_input']);
        unset($_SESSION['_old_salary_assignment_input']);
        unset($_SESSION['_old_deduction_assignment_input']);
    }

    public function update(string $id): void
    {
        require_auth();
        require_role(['Super Admin', 'HR Officer']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('employee/index');
        }

        $employeeId = (int) $id;
        if ($employeeId <= 0) {
            Session::flash('error', 'Invalid employee id.');
            redirect('employee/index');
        }

        $token = (string) $this->input('_csrf', '');
        if (!Session::verifyCsrf($token)) {
            Session::flash('error', 'Invalid request token.');
            redirect('employee/edit/' . $employeeId);
        }

        $employeeModel = new Employee();
        $existing = $employeeModel->find($employeeId);

        if (!$existing) {
            Session::flash('error', 'Employee not found.');
            redirect('employee/index');
        }

        $data = $this->collectEmployeeInput();
        $data['employee_number'] = (string) ($existing['employee_number'] ?? '');
        $_SESSION['_old_employee_input'] = $data;

        $error = $this->validateEmployeeInput($data);
        if ($error !== null) {
            Session::flash('error', $error);
            redirect('employee/edit/' . $employeeId);
        }

        if ($data['email'] !== null && $employeeModel->emailExists($data['email'], $employeeId)) {
            Session::flash('error', 'Email already exists.');
            redirect('employee/edit/' . $employeeId);
        }

        try {
            $employeeModel->update($employeeId, $data);
            AuditLog::recordChanges('updated', 'Updated employee ' . $data['full_name'], 'Employee', $employeeId, $existing, $data);
            unset($_SESSION['_old_employee_input']);
            Session::flash('success', 'Employee updated successfully.');
            redirect('employee/index');
        } catch (PDOException) {
            Session::flash('error', 'Failed to update employee. Please try again.');
            redirect('employee/edit/' . $employeeId);
        }
    }

    public function delete(string $id): void
    {
        require_auth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('employee/index');
        }

        $employeeId = (int) $id;
        if ($employeeId <= 0) {
            Session::flash('error', 'Invalid employee id.');
            redirect('employee/index');
        }

        $token = (string) $this->input('_csrf', '');
        if (!Session::verifyCsrf($token)) {
            Session::flash('error', 'Invalid request token.');
            redirect('employee/index');
        }

        $employeeModel = new Employee();
        $workflow = new WorkflowDefinition();
        $requiredRole = $workflow->requiredRoleFor('employee_termination', 1, 'HR Officer');

        if (!$this->userMatchesWorkflowRole($requiredRole)) {
            Session::flash('error', "This workflow step requires {$requiredRole} approval.");
            redirect('employee/index');
        }

        try {
            $employee = $employeeModel->findDetailed($employeeId);
            if (!$employee) {
                Session::flash('error', 'Employee not found.');
                redirect('employee/index');
            }

            if ($employeeModel->archive($employeeId, (int) (current_user()['id'] ?? 0))) {
                WorkflowEvent::record('employee_termination', 'Employee', $employeeId, (string) ($employee['lifecycle_status'] ?? 'Active'), 'Archived', 'employee_archive', $workflow->actionLabelFor('employee_termination', 1, 'Archive Employee'));
                AuditLog::record('archived', 'Archived employee ' . (string) $employee['full_name'], 'Employee', $employeeId);
                Session::flash('success', 'Employee archived successfully.');
            } else {
                Session::flash('error', 'Employee could not be archived.');
            }
        } catch (PDOException) {
            Session::flash('error', 'Failed to archive employee.');
        }

        redirect('employee/index');
    }

    public function import(): void
    {
        require_auth();
        require_role(['Super Admin', 'HR Officer']);

        $this->render('employees/import', [
            'title' => 'Import Employees',
            'csrf' => Session::csrfToken(),
            'flashSuccess' => Session::flash('success'),
            'flashError' => Session::flash('error'),
            'results' => $_SESSION['_employee_import_results'] ?? null,
        ]);
        unset($_SESSION['_employee_import_results']);
    }

    public function importStore(): void
    {
        require_auth();
        require_role(['Super Admin', 'HR Officer']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('employee/import');
        }
        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid request token.');
            redirect('employee/import');
        }

        $file = $_FILES['employee_csv'] ?? null;
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            Session::flash('error', 'Please choose a CSV file to import.');
            redirect('employee/import');
        }

        $result = $this->importEmployeesFromCsv((string) $file['tmp_name']);
        $_SESSION['_employee_import_results'] = $result;
        AuditLog::record('employee_import', 'Imported employees from CSV.', 'Employee', null, 'admin', $result);
        Session::flash('success', 'Employee import finished: ' . (int) $result['created'] . ' created, ' . (int) $result['updated'] . ' updated, ' . count($result['errors']) . ' skipped.');
        redirect('employee/import');
    }

    public function export(): void
    {
        require_auth();
        require_role(['Super Admin', 'HR Officer', 'Finance Officer']);

        $employees = (new Employee())->listWithDepartment();
        AuditLog::record('employee_export', 'Exported employee CSV.', 'Employee');

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="employees-' . date('Ymd-His') . '.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['employee_number', 'full_name', 'email', 'phone', 'nrc_number', 'date_of_birth', 'napsa_number', 'tpin', 'branch', 'department', 'designation', 'employment_type', 'bank_name', 'bank_account_number', 'contract_status', 'lifecycle_status', 'hired_at', 'probation_end_date']);
        foreach ($employees as $employee) {
            fputcsv($out, [
                $employee['employee_number'] ?? '',
                $employee['full_name'] ?? '',
                $employee['email'] ?? '',
                $employee['phone'] ?? '',
                $employee['nrc_number'] ?? '',
                $employee['date_of_birth'] ?? '',
                $employee['napsa_number'] ?? '',
                $employee['tpin'] ?? '',
                $employee['branch_name'] ?? '',
                $employee['department_name'] ?? '',
                $employee['designation'] ?? '',
                $employee['employment_type'] ?? '',
                $employee['bank_name'] ?? '',
                $employee['bank_account_number'] ?? '',
                $employee['contract_status'] ?? '',
                $employee['lifecycle_status'] ?? '',
                $employee['hired_at'] ?? '',
                $employee['probation_end_date'] ?? '',
            ]);
        }
        fclose($out);
        exit;
    }

    public function importTemplate(): void
    {
        require_auth();
        require_role(['Super Admin', 'HR Officer']);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="employee-import-template.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['employee_number', 'full_name', 'email', 'phone', 'nrc_number', 'date_of_birth', 'napsa_number', 'tpin', 'department', 'designation', 'employment_type', 'bank_name', 'bank_account_number', 'contract_status', 'lifecycle_status', 'hired_at', 'probation_end_date']);
        fputcsv($out, ['', 'Jane Banda', 'jane@example.com', '0977000000', '123456/78/9', '1992-04-15', '123456789', '1000000000', 'Operations', 'HR Assistant', 'Permanent', 'Bank Name', '1234567890', 'Active', 'Active', date('Y-m-d'), '']);
        fclose($out);
        exit;
    }

    public function assignSalary(string $id): void
    {
        require_auth();
        require_role(['Super Admin', 'HR Officer', 'Finance Officer']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('employee/index');
        }

        $employeeId = (int) $id;
        if ($employeeId <= 0) {
            Session::flash('error', 'Invalid employee id.');
            redirect('employee/index');
        }

        $token = (string) $this->input('_csrf', '');
        if (!Session::verifyCsrf($token)) {
            Session::flash('error', 'Invalid request token.');
            redirect('employee/edit/' . $employeeId);
        }

        $salaryStructureId = (int) $this->input('salary_structure_id', 0);
        $effectiveDate = $this->normalizeDate((string) $this->input('effective_date', ''));

        $_SESSION['_old_salary_assignment_input'] = [
            'salary_structure_id' => $salaryStructureId,
            'effective_date' => $effectiveDate,
        ];

        if ($salaryStructureId <= 0 || $effectiveDate === null) {
            Session::flash('error', 'Salary structure and effective date are required.');
            redirect('employee/edit/' . $employeeId);
        }

        $employeeModel = new Employee();
        if (!$employeeModel->find($employeeId)) {
            Session::flash('error', 'Employee not found.');
            redirect('employee/index');
        }

        $salaryStructureModel = new SalaryStructure();
        if (!$salaryStructureModel->find($salaryStructureId)) {
            Session::flash('error', 'Selected salary structure does not exist.');
            redirect('employee/edit/' . $employeeId);
        }

        try {
            $requestId = (new SalaryChangeRequest())->insert([
                'employee_id' => $employeeId,
                'salary_structure_id' => $salaryStructureId,
                'effective_date' => $effectiveDate,
                'status' => 'Pending Finance Review',
                'reason' => trim((string) $this->input('reason', '')),
                'requested_by' => (int) (current_user()['id'] ?? 0) ?: null,
            ]);
            WorkflowEvent::record('salary_change', 'SalaryChangeRequest', $requestId, null, 'Pending Finance Review', 'salary_change_request', 'Salary change requested.');
            AuditLog::record('salary_change_requested', 'Requested salary structure #' . $salaryStructureId . ' for employee #' . $employeeId, 'SalaryChangeRequest', $requestId, 'admin', [
                'salary_structure_id' => $salaryStructureId,
                'effective_date' => $effectiveDate,
            ]);
            unset($_SESSION['_old_salary_assignment_input']);
            Session::flash('success', 'Salary change submitted for Finance review.');
        } catch (Throwable) {
            Session::flash('error', 'Failed to assign salary structure. Please try again.');
        }

        redirect('employee/edit/' . $employeeId);
    }

    public function assignDeduction(string $id): void
    {
        require_auth();
        require_role(['Super Admin', 'HR Officer', 'Finance Officer']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('employee/index');
        }

        $employeeId = (int) $id;
        if ($employeeId <= 0) {
            Session::flash('error', 'Invalid employee id.');
            redirect('employee/index');
        }

        $token = (string) $this->input('_csrf', '');
        if (!Session::verifyCsrf($token)) {
            Session::flash('error', 'Invalid request token.');
            redirect('employee/edit/' . $employeeId);
        }

        $deductionTypeId = (int) $this->input('deduction_type_id', 0);

        if ($deductionTypeId <= 0) {
            Session::flash('error', 'Deduction type is required.');
            redirect('employee/edit/' . $employeeId);
        }

        $employeeModel = new Employee();
        if (!$employeeModel->find($employeeId)) {
            Session::flash('error', 'Employee not found.');
            redirect('employee/index');
        }

        $deductionTypeModel = new DeductionType();
        $deductionType = $deductionTypeModel->find($deductionTypeId);
        if (!$deductionType) {
            Session::flash('error', 'Selected deduction type does not exist.');
            redirect('employee/edit/' . $employeeId);
        }

        if (EmployeeDeduction::isManagedStatutoryCode($deductionType['code'] ?? null)) {
            Session::flash('error', 'PAYE, NAPSA, and NHIMA are calculated automatically from statutory settings and should not be assigned to individual employees.');
            redirect('employee/edit/' . $employeeId);
        }

        $isStatutory = (int) ($deductionType['is_statutory'] ?? 0) === 1;
        $defaultValue = (float) ($deductionType['default_value'] ?? 0);

        $amount = $isStatutory
            ? $defaultValue
            : $this->normalizeMoney((string) $this->input('amount', '0'));
        $startDate = $isStatutory ? null : $this->normalizeDate((string) $this->input('start_date', ''));
        $endDate = $isStatutory ? null : $this->normalizeDate((string) $this->input('end_date', ''));

        $_SESSION['_old_deduction_assignment_input'] = [
            'deduction_type_id' => $deductionTypeId,
            'amount' => $amount,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ];

        if (!$isStatutory && $amount <= 0) {
            Session::flash('error', 'Amount is required for non-statutory deductions.');
            redirect('employee/edit/' . $employeeId);
        }

        $employeeDeductionModel = new EmployeeDeduction();

        try {
            $employeeDeductionModel->assignAndActivate($employeeId, $deductionTypeId, $amount, $startDate, $endDate);
            AuditLog::record('deduction_assigned', 'Assigned deduction type #' . $deductionTypeId . ' to employee #' . $employeeId, 'Employee', $employeeId, 'admin', [
                'deduction_type_id' => $deductionTypeId,
                'amount' => $amount,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ]);
            unset($_SESSION['_old_deduction_assignment_input']);
            Session::flash('success', $isStatutory
                ? 'Statutory deduction assigned successfully and will apply on every pay run.'
                : 'Employee deduction assigned successfully.');
        } catch (Throwable) {
            Session::flash('error', 'Failed to assign employee deduction. Please try again.');
        }

        redirect('employee/edit/' . $employeeId);
    }

    public function checklistToggle(string $itemId): void
    {
        require_auth();
        require_role(['Super Admin', 'HR Officer']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            redirect('employee/index');
        }

        $employeeId = (int) $this->input('employee_id', 0);
        try {
            (new EmployeeLifecycleChecklist())->toggle((int) $itemId);
            WorkflowEvent::record('employee_checklist', 'Employee', $employeeId, null, 'Checklist Updated', 'checklist_toggle');
            Session::flash('success', 'Checklist updated.');
        } catch (Throwable $e) {
            Session::flash('error', $e->getMessage());
        }
        redirect('employee/profile/' . $employeeId);
    }

    public function disciplinaryStore(string $id): void
    {
        require_auth();
        require_role(['Super Admin', 'HR Officer']);

        $employeeId = (int) $id;
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            redirect('employee/profile/' . $employeeId);
        }

        try {
            $recordId = (new EmployeeDisciplinaryRecord())->insert([
                'employee_id' => $employeeId,
                'incident_date' => $this->normalizeDate((string) $this->input('incident_date', '')) ?? date('Y-m-d'),
                'record_type' => (string) $this->input('record_type', 'Other'),
                'severity' => (string) $this->input('severity', 'Medium'),
                'subject' => trim((string) $this->input('subject', 'Disciplinary record')),
                'description' => trim((string) $this->input('description', '')),
                'action_taken' => trim((string) $this->input('action_taken', '')),
                'status' => (string) $this->input('status', 'Open'),
                'created_by' => (int) (current_user()['id'] ?? 0) ?: null,
            ]);
            WorkflowEvent::record('disciplinary', 'EmployeeDisciplinaryRecord', $recordId, null, 'Open', 'disciplinary_record');
            AuditLog::record('disciplinary_record', 'Created disciplinary record for employee #' . $employeeId, 'Employee', $employeeId);
            Session::flash('success', 'Disciplinary record saved.');
        } catch (Throwable $e) {
            Session::flash('error', 'Could not save disciplinary record: ' . $e->getMessage());
        }
        redirect('employee/profile/' . $employeeId);
    }

    public function finalDuesCalculate(string $id): void
    {
        require_auth();
        require_role(['Super Admin', 'HR Officer', 'Finance Officer']);

        $employeeId = (int) $id;
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            redirect('employee/profile/' . $employeeId);
        }

        try {
            $employee = (new Employee())->findDetailed($employeeId);
            if (!$employee) { throw new RuntimeException('Employee not found.'); }
            $pay = (new Employee())->payrollProfileSummary($employeeId);
            $leave = (new Employee())->leaveProfileSummary($employeeId);
            $salary = (new EmployeeSalary())->activeWithStructureForDate($employeeId, date('Y-m-d')) ?: [];
            $contract = (new EmployeeContract())->activeForEmployee($employeeId);
            $gratuity = $this->estimateGratuity($contract, $salary, $this->configuredGratuityPolicy());

            $dailyRate = ((float) ($salary['basic_pay'] ?? 0)) / 26;
            $leavePay = max(0, (float) ($leave['balance_days'] ?? 0)) * $dailyRate;
            $unpaidSalary = (float) ($pay['outstanding'] ?? 0);
            $gratuityPay = (float) ($gratuity['amount'] ?? 0);
            $deductions = $this->normalizeMoney((string) $this->input('deductions', '0'));
            $net = max(0, $unpaidSalary + $leavePay + $gratuityPay - $deductions);

            $dueId = (new EmployeeFinalDue())->insert([
                'employee_id' => $employeeId,
                'calculation_date' => date('Y-m-d'),
                'unpaid_salary' => $unpaidSalary,
                'leave_pay' => $leavePay,
                'gratuity_pay' => $gratuityPay,
                'deductions' => $deductions,
                'net_final_due' => $net,
                'notes' => trim((string) $this->input('notes', '')),
                'calculated_by' => (int) (current_user()['id'] ?? 0) ?: null,
            ]);
            WorkflowEvent::record('final_dues', 'EmployeeFinalDue', $dueId, null, 'Calculated', 'final_dues_calculate');
            Session::flash('success', 'Final dues calculated.');
        } catch (Throwable $e) {
            Session::flash('error', 'Could not calculate final dues: ' . $e->getMessage());
        }
        redirect('employee/profile/' . $employeeId);
    }

    public function generateLetter(string $id): void
    {
        require_auth();
        require_role(['Super Admin', 'HR Officer']);

        $employeeId = (int) $id;
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            redirect('employee/profile/' . $employeeId);
        }

        try {
            $employee = (new Employee())->findDetailed($employeeId);
            if (!$employee) { throw new RuntimeException('Employee not found.'); }
            $type = (string) $this->input('letter_type', 'Employment Certificate');
            $rendered = (new EmployeeLetterTemplate())->render($type, $employee);
            if (!empty($rendered['missing_tokens'])) {
                throw new RuntimeException('This letter template contains unsupported fields: ' . implode(', ', $rendered['missing_tokens']));
            }
            $letterId = (new EmployeeGeneratedLetter())->insert([
                'employee_id' => $employeeId,
                'letter_type' => $type,
                'title' => (string) ($rendered['title'] ?? ($type . ' - ' . (string) $employee['full_name'])),
                'body_html' => (string) ($rendered['body_html'] ?? ''),
                'generated_by' => (int) (current_user()['id'] ?? 0) ?: null,
            ]);
            WorkflowEvent::record('employee_letter', 'EmployeeGeneratedLetter', $letterId, null, 'Generated', 'letter_generate', $type);
            Session::flash('success', $type . ' generated.');
        } catch (Throwable $e) {
            Session::flash('error', 'Could not generate letter: ' . $e->getMessage());
        }
        redirect('employee/profile/' . $employeeId);
    }

    public function letterView(string $id): void
    {
        require_auth();
        require_role(['Super Admin', 'HR Officer', 'Finance Officer']);
        $letter = (new EmployeeGeneratedLetter())->find((int) $id);
        if (!$letter) {
            Session::flash('error', 'Letter not found.');
            redirect('employee/index');
        }
        $this->renderAuth('employees/letter', ['letter' => $letter, 'title' => (string) $letter['title']]);
    }

    public function letterTemplates(): void
    {
        require_auth();
        require_role(['Super Admin', 'HR Officer']);

        redirect('employee-letter-template/index');
    }

    public function updateLetterTemplates(): void
    {
        require_auth();
        require_role(['Super Admin', 'HR Officer']);

        redirect('employee-letter-template/index');
    }

    private function collectEmployeeInput(): array
    {
        $departmentId = (int) $this->input('department_id', 0);
        $branchId = (int) $this->input('branch_id', 0);

        return [
            'employee_number' => strtoupper(trim((string) $this->input('employee_number', ''))),
            'full_name' => trim((string) $this->input('full_name', '')),
            'email' => $this->normalizeNullableString((string) $this->input('email', '')),
            'phone' => $this->normalizeNullableString((string) $this->input('phone', '')),
            'nrc_number' => $this->normalizeNullableString((string) $this->input('nrc_number', '')),
            'date_of_birth' => $this->normalizeDate((string) $this->input('date_of_birth', '')),
            'address' => $this->normalizeNullableString((string) $this->input('address', '')),
            'napsa_number' => $this->normalizeNullableString((string) $this->input('napsa_number', '')),
            'tpin' => $this->normalizeNullableString((string) $this->input('tpin', '')),
            'branch_id' => $branchId > 0 ? $branchId : null,
            'department_id' => $departmentId > 0 ? $departmentId : null,
            'designation' => $this->normalizeNullableString((string) $this->input('designation', '')),
            'employment_type' => (string) $this->input('employment_type', 'Permanent'),
            'bank_name' => $this->normalizeNullableString((string) $this->input('bank_name', '')),
            'bank_account_number' => $this->normalizeNullableString((string) $this->input('bank_account_number', '')),
            'contract_status' => (string) $this->input('contract_status', 'Active'),
            'lifecycle_status' => (string) $this->input('lifecycle_status', 'Active'),
            'hired_at' => $this->normalizeDate((string) $this->input('hired_at', '')),
            'probation_end_date' => $this->normalizeDate((string) $this->input('probation_end_date', '')),
        ];
    }

    private function importEmployeesFromCsv(string $path): array
    {
        $handle = fopen($path, 'r');
        if (!$handle) {
            return ['created' => 0, 'updated' => 0, 'errors' => ['Could not open uploaded CSV file.']];
        }

        $headers = fgetcsv($handle);
        if (!is_array($headers)) {
            fclose($handle);
            return ['created' => 0, 'updated' => 0, 'errors' => ['CSV file is empty.']];
        }
        $headers = array_map(static fn($h): string => strtolower(trim((string) $h)), $headers);

        $employeeModel = new Employee();
        $departments = [];
        foreach ($employeeModel->departments() as $department) {
            $departments[strtolower((string) $department['name'])] = (int) $department['id'];
        }
        $employmentTypes = (new EmploymentType())->names();
        $allowedStatuses = ['Active', 'Ended', 'Suspended'];
        $created = 0;
        $updated = 0;
        $errors = [];
        $line = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $line++;
            $data = [];
            foreach ($headers as $index => $key) {
                $data[$key] = trim((string) ($row[$index] ?? ''));
            }

            if (($data['full_name'] ?? '') === '') {
                $errors[] = "Line {$line}: full_name is required.";
                continue;
            }

            $departmentId = null;
            if (($data['department'] ?? '') !== '') {
                $departmentKey = strtolower((string) $data['department']);
                if (!isset($departments[$departmentKey])) {
                    $errors[] = "Line {$line}: department '{$data['department']}' does not exist.";
                    continue;
                }
                $departmentId = $departments[$departmentKey];
            }

            $employmentType = (string) ($data['employment_type'] ?? 'Permanent');
            if (!in_array($employmentType, $employmentTypes, true)) {
                $errors[] = "Line {$line}: employment_type '{$employmentType}' is not configured.";
                continue;
            }

            $contractStatus = (string) ($data['contract_status'] ?? 'Active');
            if (!in_array($contractStatus, $allowedStatuses, true)) {
                $contractStatus = 'Active';
            }
            $lifecycleStatus = (string) ($data['lifecycle_status'] ?? $contractStatus);
            if (!in_array($lifecycleStatus, ['Active', 'Probation', 'Suspended', 'Terminated'], true)) {
                $lifecycleStatus = $contractStatus;
            }

            $payload = [
                'employee_number' => strtoupper((string) ($data['employee_number'] ?? '')),
                'full_name' => $data['full_name'],
                'email' => $this->normalizeNullableString((string) ($data['email'] ?? '')),
                'phone' => $this->normalizeNullableString((string) ($data['phone'] ?? '')),
                'nrc_number' => $this->normalizeNullableString((string) ($data['nrc_number'] ?? '')),
                'date_of_birth' => $this->normalizeDate((string) ($data['date_of_birth'] ?? '')),
                'address' => $this->normalizeNullableString((string) ($data['address'] ?? '')),
                'napsa_number' => $this->normalizeNullableString((string) ($data['napsa_number'] ?? $data['ssno'] ?? $data['social_security_number'] ?? '')),
                'tpin' => $this->normalizeNullableString((string) ($data['tpin'] ?? '')),
                'department_id' => $departmentId,
                'designation' => $this->normalizeNullableString((string) ($data['designation'] ?? '')),
                'employment_type' => $employmentType,
                'bank_name' => $this->normalizeNullableString((string) ($data['bank_name'] ?? '')),
                'bank_account_number' => $this->normalizeNullableString((string) ($data['bank_account_number'] ?? '')),
                'contract_status' => $contractStatus,
                'lifecycle_status' => $lifecycleStatus,
                'hired_at' => $this->normalizeDate((string) ($data['hired_at'] ?? '')),
                'probation_end_date' => $this->normalizeDate((string) ($data['probation_end_date'] ?? '')),
            ];
            if ($payload['employee_number'] === '') {
                $payload['employee_number'] = $employeeModel->generateNextEmployeeNumber();
            }
            if ($payload['email'] !== null && !filter_var($payload['email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Line {$line}: email format is invalid.";
                continue;
            }

            try {
                $existing = $this->findEmployeeByNumber($payload['employee_number']);
                if ($payload['email'] !== null && $employeeModel->emailExists($payload['email'], $existing ? (int) $existing['id'] : null)) {
                    $errors[] = "Line {$line}: email already belongs to another employee.";
                    continue;
                }
                if ($existing) {
                    $employeeModel->update((int) $existing['id'], $payload);
                    AuditLog::recordChanges('employee_import_update', 'Updated employee from CSV ' . $payload['employee_number'], 'Employee', (int) $existing['id'], $existing, $payload);
                    $updated++;
                } else {
                    $id = $employeeModel->insert($payload);
                    AuditLog::record('employee_import_create', 'Created employee from CSV ' . $payload['employee_number'], 'Employee', $id, 'admin', ['line' => $line]);
                    $created++;
                }
            } catch (Throwable $e) {
                $errors[] = "Line {$line}: " . $e->getMessage();
            }
        }

        fclose($handle);
        return ['created' => $created, 'updated' => $updated, 'errors' => $errors];
    }

    private function findEmployeeByNumber(string $employeeNumber): ?array
    {
        $cid = Tenant::id();
        $sql = 'SELECT * FROM employees WHERE employee_number = :employee_number' . ($cid > 0 ? ' AND company_id = :cid' : '') . ' LIMIT 1';
        $stmt = db()->prepare($sql);
        $params = ['employee_number' => $employeeNumber];
        if ($cid > 0) {
            $params['cid'] = $cid;
        }
        $stmt->execute($params);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    private function validateEmployeeInput(array $data): ?string
    {
        if ($data['full_name'] === '') {
            return 'Full name is required.';
        }

        if ($data['email'] !== null && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return 'Email format is invalid.';
        }

        $allowedEmploymentTypes = (new EmploymentType())->names();
        if (!in_array($data['employment_type'], $allowedEmploymentTypes, true)) {
            return 'Invalid employment type selected.';
        }

        if (!empty($data['branch_id']) && !(new Branch())->belongsToTenant((int) $data['branch_id'])) {
            return 'Invalid branch selected.';
        }

        $allowedContractStatus = ['Active', 'Ended', 'Suspended'];
        if (!in_array($data['contract_status'], $allowedContractStatus, true)) {
            return 'Invalid contract status selected.';
        }

        $allowedLifecycleStatus = ['Active', 'Probation', 'Suspended', 'Terminated'];
        if (!in_array($data['lifecycle_status'], $allowedLifecycleStatus, true)) {
            $data['lifecycle_status'] = $data['contract_status'];
        }

        if ($data['hired_at'] !== null) {
            $date = date_create($data['hired_at']);
            if ($date === false) {
                return 'Invalid hire date.';
            }
        }

        return null;
    }

    private function normalizeNullableString(string $value): ?string
    {
        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    private function normalizeDate(string $value): ?string
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            return null;
        }

        $date = date_create($trimmed);
        if ($date === false) {
            return $trimmed;
        }

        return $date->format('Y-m-d');
    }

    private function normalizeMoney(string $value): float
    {
        $normalized = str_replace([',', ' '], '', trim($value));
        if ($normalized === '' || !is_numeric($normalized)) {
            return 0.0;
        }

        return max(0.0, round((float) $normalized, 2));
    }

    private function buildEmployeeLetter(string $type, array $employee): string
    {
        $company = current_company() ?? [];
        $companyName = e((string) ($company['name'] ?? app_product_name()));
        $employeeName = e((string) ($employee['full_name'] ?? 'Employee'));
        $employeeNo = e((string) ($employee['employee_number'] ?? ''));
        $designation = e((string) ($employee['designation'] ?? ''));
        $department = e((string) ($employee['department_name'] ?? ''));
        $today = e(date('d M Y'));
        $hireDate = !empty($employee['hired_at']) ? e(format_date((string) $employee['hired_at'])) : 'our records';
        $terminationDate = !empty($employee['termination_date']) ? e(format_date((string) $employee['termination_date'])) : 'the effective exit date';

        $paragraph = match ($type) {
            'Promotion Letter' => "This letter confirms that {$employeeName} has been promoted in accordance with the approved employee lifecycle record. The new designation and effective date are recorded in the HR system.",
            'Transfer Letter' => "This letter confirms that {$employeeName} has been transferred in accordance with the approved employee lifecycle record. The receiving department and effective date are recorded in the HR system.",
            'Confirmation Letter' => "This letter confirms successful completion of probation and confirmation of employment with {$companyName}, subject to continued compliance with company policies.",
            'Termination Letter' => "This letter confirms that employment for {$employeeName} ends on {$terminationDate}. Final clearance and dues are subject to the approved exit checklist and finance calculation.",
            'Final Dues Statement' => "This statement confirms that final dues for {$employeeName} should be read together with the latest final dues calculation recorded in the HR system.",
            default => "This is to certify that {$employeeName}, employee number {$employeeNo}, has been employed by {$companyName} as {$designation} in {$department} from {$hireDate}.",
        };

        return <<<HTML
        <div class="letterhead">
            <h1>{$companyName}</h1>
            <p>Date: {$today}</p>
        </div>
        <h2 style="text-align:center">{$type}</h2>
        <p>To whom it may concern,</p>
        <p>{$paragraph}</p>
        <p>This letter is generated from the official employee lifecycle records maintained by {$companyName}.</p>
        <br><br>
        <p>Authorised Signatory: ______________________________</p>
        <p>Date: ______________________________</p>
        HTML;
    }

    private function configuredGratuityPolicy(): array
    {
        $settings = new Setting();
        $rate = max(0.0, min(100.0, $settings->numericValue('gratuity_rate_percent', 5.0)));
        $qualifyingYears = max(0.0, min(50.0, $settings->numericValue('gratuity_qualifying_years', 2.0)));
        $basis = $settings->value('gratuity_basis', 'annual_basic_earned');
        $paymentTiming = $settings->value('gratuity_payment_timing', 'End of contract');

        if (!in_array($basis, ['annual_basic_earned', 'monthly_basic_served'], true)) {
            $basis = 'annual_basic_earned';
        }

        return [
            'rate' => $rate,
            'qualifying_years' => $qualifyingYears,
            'basis' => $basis,
            'payment_timing' => $paymentTiming !== '' ? $paymentTiming : 'End of contract',
        ];
    }

    private function estimateGratuity(?array $contract, ?array $salary, array $policy): array
    {
        $rate = (float) ($policy['rate'] ?? 5.0);
        $qualifyingYears = (float) ($policy['qualifying_years'] ?? 2.0);
        $basis = (string) ($policy['basis'] ?? 'annual_basic_earned');
        $paymentTiming = (string) ($policy['payment_timing'] ?? 'End of contract');

        if (!$contract || !$salary) {
            return [
                'eligible' => false,
                'amount' => 0.0,
                'accrued_amount' => 0.0,
                'months' => 0,
                'years' => 0.0,
                'rate' => $rate,
                'qualifying_years' => $qualifyingYears,
                'basis' => $basis,
                'payment_timing' => $paymentTiming,
            ];
        }

        $contractType = strtolower((string) ($contract['contract_type'] ?? ''));
        if (!str_contains($contractType, 'contract')) {
            return [
                'eligible' => false,
                'amount' => 0.0,
                'accrued_amount' => 0.0,
                'months' => 0,
                'years' => 0.0,
                'rate' => $rate,
                'qualifying_years' => $qualifyingYears,
                'basis' => $basis,
                'payment_timing' => $paymentTiming,
            ];
        }

        $start = date_create((string) ($contract['start_date'] ?? ''));
        if (!$start) {
            return [
                'eligible' => false,
                'amount' => 0.0,
                'accrued_amount' => 0.0,
                'months' => 0,
                'years' => 0.0,
                'rate' => $rate,
                'qualifying_years' => $qualifyingYears,
                'basis' => $basis,
                'payment_timing' => $paymentTiming,
            ];
        }

        $today = new DateTimeImmutable('today');
        $endDate = !empty($contract['end_date']) ? date_create_immutable((string) $contract['end_date']) : null;
        $asOf = $endDate && $endDate < $today ? $endDate : $today;
        $months = max(0, ((int) $start->diff($asOf)->y * 12) + (int) $start->diff($asOf)->m);
        $years = $months / 12;
        $basicPay = (float) ($salary['basic_pay'] ?? 0);
        $annualBasicEarned = $basis === 'monthly_basic_served'
            ? $basicPay * $months
            : $basicPay * 12 * $years;
        $accruedAmount = $annualBasicEarned * ($rate / 100);
        $eligible = $years + 0.0001 >= $qualifyingYears;

        return [
            'eligible' => $eligible,
            'amount' => $eligible ? $accruedAmount : 0.0,
            'accrued_amount' => $accruedAmount,
            'months' => $months,
            'years' => $years,
            'rate' => $rate,
            'qualifying_years' => $qualifyingYears,
            'basis' => $basis,
            'payment_timing' => $paymentTiming,
        ];
    }

    private function userMatchesWorkflowRole(string $requiredRole): bool
    {
        $user = current_user() ?? [];
        $role = (string) ($user['role'] ?? '');
        $accessLevel = (string) ($user['access_level'] ?? '');

        return $role === 'Super Admin'
            || $accessLevel === 'Super Admin'
            || $role === $requiredRole
            || $accessLevel === $requiredRole;
    }
}
