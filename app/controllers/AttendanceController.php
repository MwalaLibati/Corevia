<?php

declare(strict_types=1);

/**
 * Attendance and leave management controller.
 */

class AttendanceController extends Controller
{
    public function index(): void
    {
        require_auth();
        require_role(['Super Admin', 'HR Officer']);

        $search = trim((string) $this->input('search', ''));
        $filters = [
            'employee_id' => (int) $this->input('employee_id', 0),
            'month' => $this->normalizeMonth((string) $this->input('month', date('Y-m'))),
        ];
        $employees = [];
        $enrichedRecords = [];
        $summary = $this->attendanceSummary([]);

        try {
            $model = new AttendanceRecord();
            $records = $search === '' ? $model->listWithEmployee($filters) : $model->search($search, $filters);
            $enrichedRecords = $this->enrichAttendanceRecords($records);
            $summary = $this->attendanceSummary($enrichedRecords);
            $employees = $model->employees();
        } catch (Throwable $exception) {
            error_log('Attendance index primary load failed: ' . $exception->getMessage());
            $enrichedRecords = $this->safeAttendanceRows();
            $summary = $this->attendanceSummary($enrichedRecords);
            $employees = $this->safeAttendanceEmployees();
        }

        $this->render('attendance/index', [
            'title' => 'Attendance & Leave',
            'records' => $enrichedRecords,
            'employees' => $employees,
            'filters' => $filters,
            'summary' => $summary,
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

        $model = new AttendanceRecord();

        $this->render('attendance/create', [
            'title' => 'Create Attendance Record',
            'employees' => $model->employees(),
            'csrf' => Session::csrfToken(),
            'flashError' => Session::flash('error'),
            'old' => $_SESSION['_old_attendance_input'] ?? [],
        ]);

        unset($_SESSION['_old_attendance_input']);
    }

    public function store(): void
    {
        require_auth();
        require_role(['Super Admin', 'HR Officer']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('attendance/index');
        }

        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid request token.');
            redirect('attendance/create');
        }

        $data = $this->collectInput();
        $_SESSION['_old_attendance_input'] = $data;

        $error = $this->validateInput($data);
        if ($error !== null) {
            Session::flash('error', $error);
            redirect('attendance/create');
        }

        $authoritativeMonthly = (new MonthlyAttendanceSummary())->forEmployeeMonth(
            (int) $data['employee_id'],
            substr((string) $data['attendance_date'], 0, 7),
            true
        );
        if ($authoritativeMonthly) {
            Session::flash('error', 'Approved monthly hours are the payroll source for this employee and month. Reopen or remove that monthly entry before adding daily attendance.');
            redirect('attendance/create');
        }

        $model = new AttendanceRecord();
        if ($model->existsForEmployeeDate((int) $data['employee_id'], (string) $data['attendance_date'])) {
            Session::flash('error', 'Attendance already exists for selected employee and date.');
            redirect('attendance/create');
        }

        try {
            $model->insert($data);
            unset($_SESSION['_old_attendance_input']);
            Session::flash('success', 'Attendance record created successfully.');
            redirect('attendance/index');
        } catch (PDOException) {
            Session::flash('error', 'Failed to create attendance record.');
            redirect('attendance/create');
        }
    }

    public function edit(string $id): void
    {
        require_auth();
        require_role(['Super Admin', 'HR Officer']);

        $recordId = (int) $id;
        if ($recordId <= 0) {
            Session::flash('error', 'Invalid attendance record id.');
            redirect('attendance/index');
        }

        $model = new AttendanceRecord();
        $record = $model->findDetailed($recordId);

        if (!$record) {
            Session::flash('error', 'Attendance record not found.');
            redirect('attendance/index');
        }

        $this->render('attendance/edit', [
            'title' => 'Edit Attendance Record',
            'record' => $record,
            'employees' => $model->employees(),
            'csrf' => Session::csrfToken(),
            'flashError' => Session::flash('error'),
            'old' => $_SESSION['_old_attendance_input'] ?? [],
        ]);

        unset($_SESSION['_old_attendance_input']);
    }

    public function update(string $id): void
    {
        require_auth();
        require_role(['Super Admin', 'HR Officer']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('attendance/index');
        }

        $recordId = (int) $id;
        if ($recordId <= 0) {
            Session::flash('error', 'Invalid attendance record id.');
            redirect('attendance/index');
        }

        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid request token.');
            redirect('attendance/edit/' . $recordId);
        }

        $model = new AttendanceRecord();
        $existing = $model->find($recordId);
        if (!$existing) {
            Session::flash('error', 'Attendance record not found.');
            redirect('attendance/index');
        }

        $data = $this->collectInput();
        $_SESSION['_old_attendance_input'] = $data;

        $error = $this->validateInput($data);
        if ($error !== null) {
            Session::flash('error', $error);
            redirect('attendance/edit/' . $recordId);
        }


        $authoritativeMonthly = (new MonthlyAttendanceSummary())->forEmployeeMonth(
            (int) $data['employee_id'],
            substr((string) $data['attendance_date'], 0, 7),
            true
        );
        if ($authoritativeMonthly) {
            Session::flash('error', 'Approved monthly hours are the payroll source for this employee and month. Daily attendance cannot be changed.');
            redirect('attendance/edit/' . $recordId);
        }

        if ($model->existsForEmployeeDate((int) $data['employee_id'], (string) $data['attendance_date'], $recordId)) {
            Session::flash('error', 'Attendance already exists for selected employee and date.');
            redirect('attendance/edit/' . $recordId);
        }

        try {
            $model->update($recordId, $data);
            unset($_SESSION['_old_attendance_input']);
            Session::flash('success', 'Attendance record updated successfully.');
            redirect('attendance/index');
        } catch (PDOException) {
            Session::flash('error', 'Failed to update attendance record.');
            redirect('attendance/edit/' . $recordId);
        }
    }

    public function delete(string $id): void
    {
        require_auth();
        require_role(['Super Admin', 'HR Officer']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('attendance/index');
        }

        $recordId = (int) $id;
        if ($recordId <= 0) {
            Session::flash('error', 'Invalid attendance record id.');
            redirect('attendance/index');
        }

        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid request token.');
            redirect('attendance/index');
        }

        $model = new AttendanceRecord();

        $existing = $model->findDetailed($recordId);
        if (!$existing) {
            Session::flash('error', 'Attendance record not found.');
            redirect('attendance/index');
        }
        $authoritativeMonthly = (new MonthlyAttendanceSummary())->forEmployeeMonth(
            (int) $existing['employee_id'],
            substr((string) $existing['attendance_date'], 0, 7),
            true
        );
        if ($authoritativeMonthly) {
            Session::flash('error', 'Approved monthly hours are the payroll source for this period. Daily records are retained for audit and cannot be deleted.');
            redirect('attendance/index?month=' . urlencode(substr((string) $existing['attendance_date'], 0, 7)));
        }

        try {
            $model->delete($recordId);
            Session::flash('success', 'Attendance record deleted successfully.');
        } catch (PDOException) {
            Session::flash('error', 'Failed to delete attendance record.');
        }

        redirect('attendance/index');
    }

    public function import(): void
    {
        require_auth();
        require_role(['Super Admin', 'HR Officer']);

        $model = new AttendanceRecord();

        $this->render('attendance/import', [
            'title' => 'Import Attendance',
            'employees' => $model->employees(),
            'branches' => $model->branches(),
            'selectedMonth' => $this->normalizeMonth((string) $this->input('month', date('Y-m'))),
            'csrf' => Session::csrfToken(),
            'flashSuccess' => Session::flash('success'),
            'flashError' => Session::flash('error'),
            'results' => $_SESSION['_attendance_import_results'] ?? null,
        ]);

        unset($_SESSION['_attendance_import_results']);
    }

    public function importStore(): void
    {
        require_auth();
        require_role(['Super Admin', 'HR Officer']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('attendance/import');
        }

        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid request token.');
            redirect('attendance/import');
        }

        $file = $_FILES['attendance_csv'] ?? null;
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            Session::flash('error', 'Please choose an attendance CSV file to import.');
            redirect('attendance/import');
        }

        $extension = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
        if (!in_array($extension, ['csv', 'txt'], true)) {
            Session::flash('error', 'Please upload the attendance template as CSV. Excel can open and save this template as CSV.');
            redirect('attendance/import');
        }

        if ((int) ($file['size'] ?? 0) > 5 * 1024 * 1024) {
            Session::flash('error', 'The uploaded file is too large. Please keep attendance imports below 5MB.');
            redirect('attendance/import');
        }

        $overwrite = (string) $this->input('overwrite_existing', '') === '1';
        $result = $this->importAttendanceFromCsv((string) $file['tmp_name'], $overwrite);
        $_SESSION['_attendance_import_results'] = $result;

        AuditLog::record('attendance_import', 'Imported attendance from CSV.', 'AttendanceRecord', null, 'admin', $result);
        Session::flash(
            'success',
            'Attendance import finished: ' . (int) $result['created'] . ' created, '
            . (int) $result['updated'] . ' updated, '
            . (int) $result['skipped'] . ' skipped.'
        );
        redirect('attendance/import');
    }

    public function importTemplate(): void
    {
        require_auth();
        require_role(['Super Admin', 'HR Officer']);

        $month = $this->normalizeMonth((string) $this->input('month', date('Y-m')));
        $employeeId = max(0, (int) $this->input('employee_id', 0));
        $branchId = max(0, (int) $this->input('branch_id', 0));
        $dateBasis = (string) $this->input('date_basis', 'scheduled');
        if (!in_array($dateBasis, ['scheduled', 'weekdays', 'calendar'], true)) {
            $dateBasis = 'scheduled';
        }

        $model = new AttendanceRecord();
        $employees = $model->employeesForTemplate($employeeId, $branchId);
        $schedule = null;
        if ($dateBasis === 'scheduled') {
            try {
                $schedule = new Schedule();
            } catch (Throwable $exception) {
                error_log('Attendance template schedule lookup unavailable: ' . $exception->getMessage());
            }
        }

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="attendance-import-' . $month . '.csv"');

        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, ['employee_number', 'employee_name_reference', 'attendance_date', 'status', 'check_in', 'check_out', 'remarks']);
        $monthStart = $month . '-01';
        $monthEnd = date('Y-m-t', strtotime($monthStart));
        foreach ($employees as $employee) {
            $employeeStart = !empty($employee['hired_at']) && (string) $employee['hired_at'] > $monthStart
                ? (string) $employee['hired_at']
                : $monthStart;
            $employeeEnd = !empty($employee['termination_date']) && (string) $employee['termination_date'] < $monthEnd
                ? (string) $employee['termination_date']
                : $monthEnd;
            if ($employeeEnd < $employeeStart) {
                continue;
            }

            for ($ts = strtotime($employeeStart); $ts <= strtotime($employeeEnd); $ts = strtotime('+1 day', $ts)) {
                $date = date('Y-m-d', $ts);
                if (!$this->includeTemplateDate((int) $employee['id'], $date, $dateBasis, $schedule)) {
                    continue;
                }
                fputcsv($out, [
                    (string) $employee['employee_number'],
                    (string) $employee['full_name'],
                    $date,
                    '',
                    '',
                    '',
                    '',
                ]);
            }
        }
        fclose($out);
        exit;
    }

    public function monthlyImportTemplate(): void
    {
        require_auth();
        require_role(['Super Admin', 'HR Officer']);

        $month = $this->normalizeMonth((string) $this->input('month', date('Y-m')));
        $employeeId = max(0, (int) $this->input('employee_id', 0));
        $employees = (new AttendanceRecord())->employeesForTemplate($employeeId);
        $existing = [];
        foreach ((new MonthlyAttendanceSummary())->listForMonth($month) as $row) {
            $existing[(int) $row['employee_id']] = $row;
        }

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="monthly-hours-import-' . $month . '.csv"');

        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, [
            'employee_number',
            'employee_name_reference',
            'attendance_month',
            'total_hours',
            'overtime_hours',
            'status',
            'notes',
        ]);
        $monthStart = $month . '-01';
        $monthEnd = date('Y-m-t', strtotime($monthStart));
        foreach ($employees as $employee) {
            $row = $existing[(int) $employee['id']] ?? [];
            if ((!empty($employee['hired_at']) && (string) $employee['hired_at'] > $monthEnd)
                || (!empty($employee['termination_date']) && (string) $employee['termination_date'] < $monthStart)
                || (string) ($row['status'] ?? '') === 'Locked') {
                continue;
            }
            fputcsv($out, [
                (string) $employee['employee_number'],
                (string) $employee['full_name'],
                $month,
                $row !== [] ? (string) $row['total_hours'] : '',
                $row !== [] ? (string) $row['overtime_hours'] : '0',
                $row !== [] ? (string) $row['status'] : 'Draft',
                $row !== [] ? (string) ($row['notes'] ?? '') : '',
            ]);
        }
        fclose($out);
        exit;
    }

    public function monthlyImportStore(): void
    {
        require_auth();
        require_role(['Super Admin', 'HR Officer']);

        $month = $this->normalizeMonth((string) $this->input('month', date('Y-m')));
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('attendance/monthly?month=' . urlencode($month));
        }
        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid request token.');
            redirect('attendance/monthly?month=' . urlencode($month));
        }

        $file = $_FILES['monthly_hours_csv'] ?? null;
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            Session::flash('error', 'Please choose a monthly hours CSV file to import.');
            redirect('attendance/monthly?month=' . urlencode($month));
        }
        $extension = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
        if (!in_array($extension, ['csv', 'txt'], true)) {
            Session::flash('error', 'Please upload the monthly hours template as CSV.');
            redirect('attendance/monthly?month=' . urlencode($month));
        }
        if ((int) ($file['size'] ?? 0) > 5 * 1024 * 1024) {
            Session::flash('error', 'The uploaded file is too large. Please keep monthly hours imports below 5MB.');
            redirect('attendance/monthly?month=' . urlencode($month));
        }

        $overwrite = (string) $this->input('overwrite_existing', '') === '1';
        $result = $this->importMonthlyHoursFromCsv((string) $file['tmp_name'], $overwrite);
        $_SESSION['_monthly_attendance_import_results'] = $result;
        AuditLog::record('monthly_attendance_import', 'Imported monthly attendance hours from CSV.', 'MonthlyAttendanceSummary', null, 'admin', $result);
        Session::flash(
            'success',
            'Monthly hours import finished: ' . (int) $result['created'] . ' created, '
            . (int) $result['updated'] . ' updated, '
            . (int) $result['skipped'] . ' skipped.'
        );
        redirect('attendance/monthly?month=' . urlencode($month));
    }

    public function monthly(): void
    {
        require_auth();
        require_role(['Super Admin', 'HR Officer']);

        $month = $this->normalizeMonth((string) $this->input('month', date('Y-m')));
        $attendance = new AttendanceRecord();
        $monthly = new MonthlyAttendanceSummary();
        $rows = $this->enrichMonthlyRows($monthly->listForMonth($month), $month);

        $this->render('attendance/monthly', [
            'title' => 'Monthly Attendance Hours',
            'month' => $month,
            'employees' => $attendance->employeesForTemplate(),
            'rows' => $rows,
            'csrf' => Session::csrfToken(),
            'flashSuccess' => Session::flash('success'),
            'flashError' => Session::flash('error'),
            'importResults' => $_SESSION['_monthly_attendance_import_results'] ?? null,
        ]);

        unset($_SESSION['_monthly_attendance_import_results']);
    }

    public function monthlyStore(): void
    {
        require_auth();
        require_role(['Super Admin', 'HR Officer']);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('attendance/monthly');
        }
        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid request token.');
            redirect('attendance/monthly');
        }

        $month = $this->normalizeMonth((string) $this->input('attendance_month', date('Y-m')));
        $employeeId = (int) $this->input('employee_id', 0);
        $totalHours = round((float) $this->input('total_hours', 0), 2);
        $overtimeHours = round((float) $this->input('overtime_hours', 0), 2);
        $status = (string) $this->input('save_action', 'Draft') === 'Approved' ? 'Approved' : 'Draft';
        $notes = trim((string) $this->input('notes', ''));

        $employees = (new AttendanceRecord())->employees();
        $validEmployeeIds = array_map(static fn(array $employee): int => (int) $employee['id'], $employees);
        if ($employeeId <= 0 || !in_array($employeeId, $validEmployeeIds, true)) {
            Session::flash('error', 'Select a valid employee from this company.');
            redirect('attendance/monthly?month=' . urlencode($month));
        }
        if ($totalHours < 0 || $totalHours > 744) {
            Session::flash('error', 'Total hours must be between 0 and 744 for the month.');
            redirect('attendance/monthly?month=' . urlencode($month));
        }
        if ($overtimeHours < 0 || $overtimeHours > $totalHours) {
            Session::flash('error', 'Overtime hours cannot be negative or greater than total hours.');
            redirect('attendance/monthly?month=' . urlencode($month));
        }

        try {
            $model = new MonthlyAttendanceSummary();
            $before = $model->forEmployeeMonth($employeeId, $month);
            $id = $model->saveEntry([
                'employee_id' => $employeeId,
                'attendance_month' => $month,
                'total_hours' => $totalHours,
                'overtime_hours' => $overtimeHours,
                'notes' => $notes,
                'status' => $status,
            ]);
            $after = $model->find($id) ?: [];
            AuditLog::recordChanges('monthly_attendance_save', 'Saved monthly attendance hours.', 'MonthlyAttendanceSummary', $id, $before ?: [], $after);
            Session::flash('success', $status === 'Approved'
                ? 'Monthly hours saved and approved for payroll.'
                : 'Monthly hours saved as a draft.');
        } catch (Throwable $exception) {
            Session::flash('error', $exception->getMessage());
        }
        redirect('attendance/monthly?month=' . urlencode($month));
    }

    public function monthlyApprove(string $id): void
    {
        require_auth();
        require_role(['Super Admin', 'HR Officer']);
        $month = $this->normalizeMonth((string) $this->input('month', date('Y-m')));
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid approval request.');
            redirect('attendance/monthly?month=' . urlencode($month));
        }
        $model = new MonthlyAttendanceSummary();
        if ($model->approve((int) $id)) {
            AuditLog::record('monthly_attendance_approve', 'Approved monthly attendance hours.', 'MonthlyAttendanceSummary', (int) $id, 'admin');
            Session::flash('success', 'Monthly attendance hours approved for payroll.');
        } else {
            Session::flash('error', 'The entry could not be approved. It may already be approved or locked.');
        }
        redirect('attendance/monthly?month=' . urlencode($month));
    }

    public function monthlyDelete(string $id): void
    {
        require_auth();
        require_role(['Super Admin', 'HR Officer']);
        $month = $this->normalizeMonth((string) $this->input('month', date('Y-m')));
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid delete request.');
            redirect('attendance/monthly?month=' . urlencode($month));
        }
        $model = new MonthlyAttendanceSummary();
        if ($model->deleteEditable((int) $id)) {
            AuditLog::record('monthly_attendance_delete', 'Deleted monthly attendance hours.', 'MonthlyAttendanceSummary', (int) $id, 'admin');
            Session::flash('success', 'Monthly attendance entry deleted.');
        } else {
            Session::flash('error', 'Locked monthly attendance cannot be deleted.');
        }
        redirect('attendance/monthly?month=' . urlencode($month));
    }

    public function monthlyReopen(string $id): void
    {
        require_auth();
        require_role(['Super Admin', 'HR Officer']);
        $month = $this->normalizeMonth((string) $this->input('month', date('Y-m')));
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid reopen request.');
            redirect('attendance/monthly?month=' . urlencode($month));
        }
        $model = new MonthlyAttendanceSummary();
        if ($model->reopen((int) $id)) {
            AuditLog::record('monthly_attendance_reopen', 'Reopened approved monthly attendance hours.', 'MonthlyAttendanceSummary', (int) $id, 'admin');
            Session::flash('success', 'Monthly hours reopened as a draft. They no longer override daily attendance until approved again.');
        } else {
            Session::flash('error', 'The entry could not be reopened. Locked payroll periods remain read-only.');
        }
        redirect('attendance/monthly?month=' . urlencode($month));
    }

    private function collectInput(): array
    {
        return [
            'employee_id' => (int) $this->input('employee_id', 0),
            'attendance_date' => $this->normalizeDate((string) $this->input('attendance_date', '')),
            'check_in' => $this->normalizeTime((string) $this->input('check_in', '')),
            'check_out' => $this->normalizeTime((string) $this->input('check_out', '')),
            'status' => (string) $this->input('status', 'Present'),
            'remarks' => $this->normalizeNullableString((string) $this->input('remarks', '')),
        ];
    }

    private function validateInput(array $data): ?string
    {
        if ((int) $data['employee_id'] <= 0 || $data['attendance_date'] === null) {
            return 'Employee and attendance date are required.';
        }

        $allowedStatus = ['Present', 'Absent', 'Late', 'Leave'];
        if (!in_array($data['status'], $allowedStatus, true)) {
            return 'Invalid attendance status.';
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

        return $date === false ? null : $date->format('Y-m-d');
    }

    private function normalizeTime(string $value): ?string
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            return null;
        }

        $date = date_create($trimmed);

        return $date === false ? null : $date->format('H:i:s');
    }

    private function normalizeMonth(string $value): string
    {
        $trimmed = trim($value);
        return preg_match('/^\d{4}-\d{2}$/', $trimmed) ? $trimmed : date('Y-m');
    }

    private function enrichAttendanceRecords(array $records): array
    {
        $salaryModel = null;
        $ruleModel = null;
        $scheduleModel = null;
        try {
            $salaryModel = new EmployeeSalary();
        } catch (Throwable $exception) {
            error_log('Attendance salary enrichment unavailable: ' . $exception->getMessage());
        }
        try {
            $ruleModel = new AttendancePayrollRule();
        } catch (Throwable $exception) {
            error_log('Attendance payroll rule enrichment unavailable: ' . $exception->getMessage());
        }
        try {
            $scheduleModel = new Schedule();
        } catch (Throwable $exception) {
            error_log('Attendance schedule enrichment unavailable: ' . $exception->getMessage());
        }
        $salaryCache = [];
        $ruleCache = [];

        foreach ($records as &$record) {
            $employeeId = (int) ($record['employee_id'] ?? 0);
            $date = (string) ($record['attendance_date'] ?? date('Y-m-d'));
            $monthEnd = date('Y-m-t', strtotime($date));
            $ruleKey = substr($date, 0, 7);

            if (!isset($salaryCache[$employeeId . '-' . $ruleKey])) {
                try {
                    $salaryCache[$employeeId . '-' . $ruleKey] = $salaryModel
                        ? $salaryModel->activeWithStructureForDate($employeeId, $monthEnd)
                        : null;
                } catch (Throwable $exception) {
                    error_log('Attendance salary row enrichment failed: ' . $exception->getMessage());
                    $salaryCache[$employeeId . '-' . $ruleKey] = null;
                }
            }
            if (!isset($ruleCache[$ruleKey])) {
                try {
                    $ruleCache[$ruleKey] = $ruleModel ? $ruleModel->activeForDate($monthEnd) : [];
                } catch (Throwable $exception) {
                    error_log('Attendance payroll rule row enrichment failed: ' . $exception->getMessage());
                    $ruleCache[$ruleKey] = [];
                }
            }

            try {
                $record['_attendance_calc'] = $this->attendanceValueForRecord(
                    $record,
                    $salaryCache[$employeeId . '-' . $ruleKey] ?: [],
                    $ruleCache[$ruleKey] ?: []
                );
            } catch (Throwable $exception) {
                error_log('Attendance value calculation failed: ' . $exception->getMessage());
                $record['_attendance_calc'] = $this->timeOnlyCalculation($record);
            }

            try {
                $record['_schedule_compare'] = $scheduleModel
                    ? $scheduleModel->compareAttendance($record)
                    : ['status' => 'Unscheduled', 'label' => 'No schedule', 'late_minutes' => 0, 'early_minutes' => 0, 'overtime_minutes' => 0];
            } catch (Throwable $exception) {
                error_log('Attendance schedule comparison failed: ' . $exception->getMessage());
                $record['_schedule_compare'] = ['status' => 'Unscheduled', 'label' => 'No schedule', 'late_minutes' => 0, 'early_minutes' => 0, 'overtime_minutes' => 0];
            }
        }
        unset($record);

        return $records;
    }

    private function attendanceValueForRecord(array $record, array $salary, array $rule): array
    {
        $workedMinutes = $this->workedMinutes($record);
        $hours = round($workedMinutes / 60, 2);
        $source = (string) ($salary['basic_pay_source'] ?? 'Fixed Salary');
        $standardDays = max(1.0, (float) ($rule['standard_days_per_month'] ?? 26));
        $standardHours = max(1.0, (float) ($rule['standard_hours_per_day'] ?? 8));
        $fullShiftHours = max(1.0, (float) ($rule['full_shift_hours'] ?? $standardHours));
        $overtimeAfterHours = max(1.0, (float) ($rule['overtime_after_hours_per_day'] ?? $standardHours));
        $basic = (float) ($salary['basic_pay'] ?? 0);
        $dailyRate = (float) ($salary['daily_rate'] ?? 0) > 0 ? (float) $salary['daily_rate'] : $basic / $standardDays;
        $hourlyRate = (float) ($salary['hourly_rate'] ?? 0) > 0 ? (float) $salary['hourly_rate'] : $dailyRate / $standardHours;
        $shiftRate = (float) ($salary['shift_rate'] ?? 0) > 0 ? (float) $salary['shift_rate'] : $dailyRate;
        $amount = 0.0;
        $label = 'Time only';
        $isWeekend = in_array((int) date('N', strtotime((string) ($record['attendance_date'] ?? date('Y-m-d')))), [6, 7], true);
        $overtimeEnabled = !empty($rule['overtime_enabled']);

        if (in_array((string) ($record['status'] ?? ''), ['Absent', 'Leave'], true)) {
            $hours = 0.0;
        } elseif ($source === 'Attendance Hours') {
            if ($isWeekend && $overtimeEnabled) {
                $weekendRate = $hourlyRate * (float) ($rule['weekend_overtime_multiplier'] ?? 2.0);
                $amount = round($hours * $weekendRate, 2);
                $label = 'Weekend hours x ' . format_currency($weekendRate);
            } elseif ($overtimeEnabled && $hours > $overtimeAfterHours) {
                $normalHours = $overtimeAfterHours;
                $overtimeHours = $hours - $overtimeAfterHours;
                $overtimeRate = $hourlyRate * (float) ($rule['normal_overtime_multiplier'] ?? 1.5);
                $amount = round(($normalHours * $hourlyRate) + ($overtimeHours * $overtimeRate), 2);
                $label = number_format($normalHours, 2) . ' normal + ' . number_format($overtimeHours, 2) . ' OT hrs';
            } else {
                $amount = round($hours * $hourlyRate, 2);
                $label = 'Hours x ' . format_currency($hourlyRate);
            }
        } elseif ($source === 'Days Worked') {
            $amount = $workedMinutes > 0 ? round($dailyRate, 2) : 0.0;
            $label = 'Day x ' . format_currency($dailyRate);
            if (!$isWeekend && $overtimeEnabled && $hours > $overtimeAfterHours) {
                $overtimeHours = $hours - $overtimeAfterHours;
                $overtimeRate = $hourlyRate * (float) ($rule['normal_overtime_multiplier'] ?? 1.5);
                $amount = round($amount + ($overtimeHours * $overtimeRate), 2);
                $label .= ' + ' . number_format($overtimeHours, 2) . ' OT hrs';
            }
        } elseif ($source === 'Shifts Worked') {
            $method = (string) ($rule['shift_count_method'] ?? 'Attendance Day');
            $quantity = $method === 'Worked Hours / Full Shift'
                ? round($workedMinutes / max(1, $fullShiftHours * 60), 4)
                : ($workedMinutes > 0 ? 1.0 : 0.0);
            $amount = round($quantity * $shiftRate, 2);
            $label = number_format($quantity, 2) . ' shift(s) x ' . format_currency($shiftRate);
            if (!$isWeekend && $overtimeEnabled && $hours > $overtimeAfterHours) {
                $overtimeHours = $hours - $overtimeAfterHours;
                $overtimeRate = $hourlyRate * (float) ($rule['normal_overtime_multiplier'] ?? 1.5);
                $amount = round($amount + ($overtimeHours * $overtimeRate), 2);
                $label .= ' + ' . number_format($overtimeHours, 2) . ' OT hrs';
            }
        } elseif ((string) ($rule['payroll_mode'] ?? '') === 'Fixed Salary + Attendance Adjustments') {
            $label = 'Fixed salary adjustment basis';
        }

        return [
            'hours' => $hours,
            'amount' => $amount,
            'label' => $label,
            'source' => $source,
        ];
    }

    private function timeOnlyCalculation(array $record): array
    {
        return [
            'hours' => round($this->workedMinutes($record) / 60, 2),
            'amount' => 0.0,
            'label' => 'Time only',
            'source' => 'Time',
        ];
    }

    private function attendanceSummary(array $records): array
    {
        $summary = ['records' => count($records), 'hours' => 0.0, 'estimated_value' => 0.0, 'present' => 0, 'absent' => 0, 'leave' => 0, 'late' => 0, 'scheduled_late' => 0, 'early_departures' => 0, 'overtime_hours' => 0.0];
        foreach ($records as $record) {
            $calc = $record['_attendance_calc'] ?? [];
            $schedule = $record['_schedule_compare'] ?? [];
            $summary['hours'] += (float) ($calc['hours'] ?? 0);
            $summary['estimated_value'] += (float) ($calc['amount'] ?? 0);
            $summary['overtime_hours'] += round(((int) ($schedule['overtime_minutes'] ?? 0)) / 60, 2);
            if ((int) ($schedule['late_minutes'] ?? 0) > 0) {
                $summary['scheduled_late']++;
            }
            if ((int) ($schedule['early_minutes'] ?? 0) > 0) {
                $summary['early_departures']++;
            }
            $status = strtolower((string) ($record['status'] ?? ''));
            if (isset($summary[$status])) {
                $summary[$status]++;
            }
        }
        $summary['hours'] = round($summary['hours'], 2);
        $summary['estimated_value'] = round($summary['estimated_value'], 2);
        $summary['overtime_hours'] = round($summary['overtime_hours'], 2);
        return $summary;
    }

    private function importAttendanceFromCsv(string $path, bool $overwrite): array
    {
        $handle = fopen($path, 'r');
        if (!$handle) {
            return ['created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => ['Could not open uploaded CSV file.']];
        }

        $headers = fgetcsv($handle);
        if (!is_array($headers)) {
            fclose($handle);
            return ['created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => ['CSV file is empty.']];
        }

        $headers = array_map(fn($header): string => $this->normalizeImportHeader((string) $header), $headers);
        $required = ['employee_number', 'attendance_date', 'status'];
        foreach ($required as $header) {
            if (!in_array($header, $headers, true)) {
                fclose($handle);
                return ['created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => ['Missing required column: ' . $header . '.']];
            }
        }

        $model = new AttendanceRecord();
        $monthlyModel = new MonthlyAttendanceSummary();
        $monthlyAuthorityCache = [];
        $employeesByNumber = [];
        foreach ($model->employees() as $employee) {
            $number = strtoupper(trim((string) ($employee['employee_number'] ?? '')));
            if ($number !== '') {
                $employeesByNumber[$number] = $employee;
            }
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];
        $line = 1;
        $allowedStatus = ['Present', 'Absent', 'Late', 'Leave'];

        while (($row = fgetcsv($handle)) !== false) {
            $line++;
            $data = [];
            foreach ($headers as $index => $key) {
                if ($key !== '') {
                    $data[$key] = trim((string) ($row[$index] ?? ''));
                }
            }

            if ($this->isEmptyImportRow($data) || str_contains(strtolower((string) ($data['employee_number'] ?? '')), 'employee numbers available')) {
                continue;
            }

            $employeeNumber = strtoupper((string) ($data['employee_number'] ?? ''));
            if ($employeeNumber === '' || !isset($employeesByNumber[$employeeNumber])) {
                $errors[] = "Line {$line}: employee_number '{$employeeNumber}' was not found in this company.";
                $skipped++;
                continue;
            }

            $attendanceDate = $this->normalizeDate((string) ($data['attendance_date'] ?? ''));
            if ($attendanceDate === null) {
                $errors[] = "Line {$line}: attendance_date is required and must be a valid date.";
                $skipped++;
                continue;
            }

            $status = $this->normalizeAttendanceStatus((string) ($data['status'] ?? ''));
            if (!in_array($status, $allowedStatus, true)) {
                $errors[] = "Line {$line}: status must be Present, Late, Absent, or Leave.";
                $skipped++;
                continue;
            }

            $payload = [
                'employee_id' => (int) $employeesByNumber[$employeeNumber]['id'],
                'attendance_date' => $attendanceDate,
                'check_in' => $this->normalizeTime((string) ($data['check_in'] ?? '')),
                'check_out' => $this->normalizeTime((string) ($data['check_out'] ?? '')),
                'status' => $status,
                'remarks' => $this->normalizeNullableString((string) ($data['remarks'] ?? '')),
            ];

            $authorityKey = (int) $payload['employee_id'] . '-' . substr($attendanceDate, 0, 7);
            if (!array_key_exists($authorityKey, $monthlyAuthorityCache)) {
                $monthlyAuthorityCache[$authorityKey] = $monthlyModel->forEmployeeMonth(
                    (int) $payload['employee_id'],
                    substr($attendanceDate, 0, 7),
                    true
                );
            }
            if ($monthlyAuthorityCache[$authorityKey]) {
                $errors[] = "Line {$line}: approved monthly hours are already the payroll source for {$employeeNumber} in " . substr($attendanceDate, 0, 7) . '.';
                $skipped++;
                continue;
            }

            try {
                $existing = $model->findByEmployeeDate((int) $payload['employee_id'], $attendanceDate);
                if ($existing && !$overwrite) {
                    $errors[] = "Line {$line}: attendance already exists for {$employeeNumber} on {$attendanceDate}.";
                    $skipped++;
                    continue;
                }

                if ($existing) {
                    $model->update((int) $existing['id'], $payload);
                    AuditLog::recordChanges('attendance_import_update', 'Updated attendance from CSV.', 'AttendanceRecord', (int) $existing['id'], $existing, $payload);
                    $updated++;
                } else {
                    $id = $model->insert($payload);
                    AuditLog::record('attendance_import_create', 'Created attendance from CSV.', 'AttendanceRecord', $id, 'admin', ['line' => $line, 'employee_number' => $employeeNumber]);
                    $created++;
                }
            } catch (Throwable $exception) {
                $errors[] = "Line {$line}: " . $exception->getMessage();
                $skipped++;
            }
        }

        fclose($handle);

        return ['created' => $created, 'updated' => $updated, 'skipped' => $skipped, 'errors' => $errors];
    }

    private function importMonthlyHoursFromCsv(string $path, bool $overwrite): array
    {
        $handle = fopen($path, 'r');
        if (!$handle) {
            return ['created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => ['Could not open uploaded CSV file.']];
        }

        $headers = fgetcsv($handle);
        if (!is_array($headers)) {
            fclose($handle);
            return ['created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => ['CSV file is empty.']];
        }
        $headers = array_map(fn($header): string => $this->normalizeImportHeader((string) $header), $headers);
        foreach (['employee_number', 'attendance_month', 'total_hours'] as $required) {
            if (!in_array($required, $headers, true)) {
                fclose($handle);
                return ['created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => ['Missing required column: ' . $required . '.']];
            }
        }

        $employeesByNumber = [];
        foreach ((new AttendanceRecord())->employees() as $employee) {
            $number = strtoupper(trim((string) ($employee['employee_number'] ?? '')));
            if ($number !== '') {
                $employeesByNumber[$number] = $employee;
            }
        }

        $model = new MonthlyAttendanceSummary();
        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];
        $line = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $line++;
            $data = [];
            foreach ($headers as $index => $key) {
                if ($key !== '') {
                    $data[$key] = trim((string) ($row[$index] ?? ''));
                }
            }
            if ($this->isEmptyImportRow($data)) {
                continue;
            }

            $employeeNumber = strtoupper((string) ($data['employee_number'] ?? ''));
            if ($employeeNumber === '' || !isset($employeesByNumber[$employeeNumber])) {
                $errors[] = "Line {$line}: employee_number '{$employeeNumber}' was not found in this company.";
                $skipped++;
                continue;
            }

            $rawMonth = substr(trim((string) ($data['attendance_month'] ?? '')), 0, 7);
            if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $rawMonth)) {
                $errors[] = "Line {$line}: attendance_month must use YYYY-MM format.";
                $skipped++;
                continue;
            }

            $rawTotal = trim((string) ($data['total_hours'] ?? ''));
            $rawOvertime = trim((string) ($data['overtime_hours'] ?? '0'));
            if ($rawTotal === '') {
                $skipped++;
                continue;
            }
            if (!is_numeric($rawTotal) || !is_numeric($rawOvertime === '' ? '0' : $rawOvertime)) {
                $errors[] = "Line {$line}: total_hours and overtime_hours must be numbers.";
                $skipped++;
                continue;
            }
            $totalHours = round((float) $rawTotal, 2);
            $overtimeHours = round((float) ($rawOvertime === '' ? 0 : $rawOvertime), 2);
            if ($totalHours < 0 || $totalHours > 744) {
                $errors[] = "Line {$line}: total_hours must be between 0 and 744.";
                $skipped++;
                continue;
            }
            if ($overtimeHours < 0 || $overtimeHours > $totalHours) {
                $errors[] = "Line {$line}: overtime_hours cannot be negative or greater than total_hours.";
                $skipped++;
                continue;
            }

            $rawStatus = trim((string) ($data['status'] ?? ''));
            $status = $rawStatus === '' ? 'Draft' : ucfirst(strtolower($rawStatus));
            if (!in_array($status, ['Draft', 'Approved'], true)) {
                $errors[] = "Line {$line}: status must be Draft or Approved.";
                $skipped++;
                continue;
            }

            $employeeId = (int) $employeesByNumber[$employeeNumber]['id'];
            $existing = $model->forEmployeeMonth($employeeId, $rawMonth);
            if ($existing && (string) $existing['status'] === 'Locked') {
                $errors[] = "Line {$line}: {$employeeNumber} is locked by payroll for {$rawMonth}.";
                $skipped++;
                continue;
            }
            if ($existing && !$overwrite) {
                $errors[] = "Line {$line}: monthly hours already exist for {$employeeNumber} in {$rawMonth}. Select update existing entries to replace them.";
                $skipped++;
                continue;
            }

            try {
                $id = $model->saveEntry([
                    'employee_id' => $employeeId,
                    'attendance_month' => $rawMonth,
                    'total_hours' => $totalHours,
                    'overtime_hours' => $overtimeHours,
                    'notes' => trim((string) ($data['notes'] ?? '')),
                    'status' => $status,
                ]);
                if ($existing) {
                    $updated++;
                    AuditLog::recordChanges('monthly_attendance_import_update', 'Updated monthly attendance from CSV.', 'MonthlyAttendanceSummary', $id, $existing, $model->find($id) ?: []);
                } else {
                    $created++;
                    AuditLog::record('monthly_attendance_import_create', 'Created monthly attendance from CSV.', 'MonthlyAttendanceSummary', $id, 'admin', ['line' => $line, 'employee_number' => $employeeNumber]);
                }
            } catch (Throwable $exception) {
                $errors[] = "Line {$line}: " . $exception->getMessage();
                $skipped++;
            }
        }

        fclose($handle);
        return ['created' => $created, 'updated' => $updated, 'skipped' => $skipped, 'errors' => $errors];
    }

    private function normalizeImportHeader(string $header): string
    {
        $header = preg_replace('/^\xEF\xBB\xBF/', '', trim($header)) ?? '';
        $header = strtolower($header);

        return trim((string) preg_replace('/[^a-z0-9]+/', '_', $header), '_');
    }

    private function normalizeAttendanceStatus(string $status): string
    {
        $status = strtolower(trim($status));
        return match ($status) {
            'present' => 'Present',
            'absent' => 'Absent',
            'late' => 'Late',
            'leave', 'on_leave', 'on leave' => 'Leave',
            default => '',
        };
    }

    private function isEmptyImportRow(array $data): bool
    {
        foreach ($data as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    private function includeTemplateDate(int $employeeId, string $date, string $dateBasis, ?Schedule $schedule): bool
    {
        if ($dateBasis === 'calendar') {
            return true;
        }
        if ($dateBasis === 'weekdays') {
            return (int) date('N', strtotime($date)) <= 5;
        }

        if ($schedule) {
            try {
                $scheduled = $schedule->scheduleForEmployeeDate($employeeId, $date);
                if ($scheduled !== null) {
                    return !empty($scheduled['is_working_day']) && !empty($scheduled['shift_id']);
                }
            } catch (Throwable $exception) {
                error_log('Attendance template date schedule lookup failed: ' . $exception->getMessage());
            }
        }
        return (int) date('N', strtotime($date)) <= 5;
    }

    private function enrichMonthlyRows(array $rows, string $month): array
    {
        $salaryModel = new EmployeeSalary();
        $ruleModel = new AttendancePayrollRule();
        $monthEnd = date('Y-m-t', strtotime($month . '-01'));
        $rule = $ruleModel->activeForDate($monthEnd);

        foreach ($rows as &$row) {
            $salary = $salaryModel->activeWithStructureForDate((int) $row['employee_id'], $monthEnd) ?: [];
            $row['_estimate'] = $this->monthlyValueEstimate($row, $salary, $rule);
        }
        unset($row);
        return $rows;
    }

    private function monthlyValueEstimate(array $row, array $salary, array $rule): array
    {
        $totalHours = max(0.0, (float) ($row['total_hours'] ?? 0));
        $overtimeHours = min($totalHours, max(0.0, (float) ($row['overtime_hours'] ?? 0)));
        $regularHours = max(0.0, $totalHours - $overtimeHours);
        $source = (string) ($salary['basic_pay_source'] ?? 'Fixed Salary');
        $standardDays = max(1.0, (float) ($rule['standard_days_per_month'] ?? 26));
        $standardHours = max(1.0, (float) ($rule['standard_hours_per_day'] ?? 8));
        $fullShiftHours = max(1.0, (float) ($rule['full_shift_hours'] ?? $standardHours));
        $basic = (float) ($salary['basic_pay'] ?? 0);
        $dailyRate = (float) ($salary['daily_rate'] ?? 0) > 0 ? (float) $salary['daily_rate'] : $basic / $standardDays;
        $hourlyRate = (float) ($salary['hourly_rate'] ?? 0) > 0 ? (float) $salary['hourly_rate'] : $dailyRate / $standardHours;
        $shiftRate = (float) ($salary['shift_rate'] ?? 0) > 0 ? (float) $salary['shift_rate'] : $dailyRate;
        $overtimeRate = !empty($rule['overtime_enabled'])
            ? $hourlyRate * (float) ($rule['normal_overtime_multiplier'] ?? 1.5)
            : $hourlyRate;
        $amount = 0.0;
        $label = 'Recorded for information only';

        if ($source === 'Attendance Hours') {
            $amount = ($regularHours * $hourlyRate) + ($overtimeHours * $overtimeRate);
            $label = 'Monthly hours x configured hourly rates';
        } elseif ($source === 'Shifts Worked' && (string) ($rule['shift_count_method'] ?? '') === 'Worked Hours / Full Shift') {
            $shifts = $totalHours / $fullShiftHours;
            $amount = $shifts * $shiftRate;
            $label = number_format($shifts, 2) . ' equivalent shift(s)';
        }

        return [
            'regular_hours' => round($regularHours, 2),
            'overtime_hours' => round($overtimeHours, 2),
            'hourly_rate' => round($hourlyRate, 4),
            'overtime_rate' => round($overtimeRate, 4),
            'amount' => round($amount, 2),
            'label' => $label,
            'source' => $source,
        ];
    }

    private function workedMinutes(array $record): int
    {
        if (empty($record['check_in']) || empty($record['check_out'])) {
            return 0;
        }
        $date = (string) ($record['attendance_date'] ?? date('Y-m-d'));
        $in = strtotime($date . ' ' . (string) $record['check_in']);
        $out = strtotime($date . ' ' . (string) $record['check_out']);
        if ($in === false || $out === false) {
            return 0;
        }
        if ($out < $in) {
            $out += 86400;
        }
        return max(0, (int) floor(($out - $in) / 60));
    }

    private function safeAttendanceRows(): array
    {
        try {
            $cid = Tenant::id();
            $where = $cid > 0 ? ' WHERE e.company_id = :cid' : '';
            $stmt = db()->prepare(
                'SELECT ar.id, ar.employee_id, ar.attendance_date, ar.status, ar.check_in, ar.check_out, ar.remarks,
                        e.full_name AS employee_name, e.employee_number
                 FROM attendance_records ar
                 JOIN employees e ON e.id = ar.employee_id' . $where . '
                 ORDER BY ar.attendance_date DESC, ar.id DESC
                 LIMIT 500'
            );
            $stmt->execute($cid > 0 ? ['cid' => $cid] : []);
            $rows = $stmt->fetchAll();
            foreach ($rows as &$row) {
                $row['_attendance_calc'] = $this->timeOnlyCalculation($row);
            }
            unset($row);
            return $rows;
        } catch (Throwable $exception) {
            error_log('Attendance safe rows failed: ' . $exception->getMessage());
            return [];
        }
    }

    private function safeAttendanceEmployees(): array
    {
        try {
            $cid = Tenant::id();
            $stmt = db()->prepare(
                'SELECT id, full_name, employee_number
                 FROM employees' . ($cid > 0 ? ' WHERE company_id = :cid' : '') . '
                 ORDER BY full_name ASC'
            );
            $stmt->execute($cid > 0 ? ['cid' => $cid] : []);
            return $stmt->fetchAll();
        } catch (Throwable $exception) {
            error_log('Attendance safe employees failed: ' . $exception->getMessage());
            return [];
        }
    }
}
