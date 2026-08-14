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
            Session::flash('error', 'Attendance loaded in safe mode. Some payroll value estimates may be unavailable until setup is refreshed.');
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

        try {
            $model->delete($recordId);
            Session::flash('success', 'Attendance record deleted successfully.');
        } catch (PDOException) {
            Session::flash('error', 'Failed to delete attendance record.');
        }

        redirect('attendance/index');
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

            $record['_attendance_calc'] = $this->attendanceValueForRecord(
                $record,
                $salaryCache[$employeeId . '-' . $ruleKey] ?: [],
                $ruleCache[$ruleKey] ?: []
            );
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

    private function attendanceSummary(array $records): array
    {
        $summary = ['records' => count($records), 'hours' => 0.0, 'estimated_value' => 0.0, 'present' => 0, 'absent' => 0, 'leave' => 0, 'late' => 0];
        foreach ($records as $record) {
            $calc = $record['_attendance_calc'] ?? [];
            $summary['hours'] += (float) ($calc['hours'] ?? 0);
            $summary['estimated_value'] += (float) ($calc['amount'] ?? 0);
            $status = strtolower((string) ($record['status'] ?? ''));
            if (isset($summary[$status])) {
                $summary[$status]++;
            }
        }
        $summary['hours'] = round($summary['hours'], 2);
        $summary['estimated_value'] = round($summary['estimated_value'], 2);
        return $summary;
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
                $row['_attendance_calc'] = [
                    'hours' => round($this->workedMinutes($row) / 60, 2),
                    'amount' => 0.0,
                    'label' => 'Time only',
                    'source' => 'Safe Mode',
                ];
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
