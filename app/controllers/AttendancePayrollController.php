<?php

declare(strict_types=1);

class AttendancePayrollController extends Controller
{
    public function index(): void
    {
        require_auth();
        require_role(['Super Admin', 'Admin', 'Finance Officer', 'HR Officer']);

        $model = new AttendancePayrollRule();
        $rules = $model->listForCompany();

        $this->render('attendance_payroll/index', [
            'title' => 'Attendance Payroll Rules',
            'rules' => $rules,
            'activeRule' => $model->activeForDate(date('Y-m-d')),
            'csrf' => Session::csrfToken(),
            'flashSuccess' => Session::flash('success'),
            'flashError' => Session::flash('error'),
        ]);
    }

    public function create(): void
    {
        require_auth();
        require_role(['Super Admin', 'Admin', 'Finance Officer', 'HR Officer']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('attendance-payroll/index');
        }
        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid request token.');
            redirect('attendance-payroll/index');
        }

        $model = new AttendancePayrollRule();
        $id = $model->insert([
            'company_id' => Tenant::id(),
            'name' => 'New Attendance Payroll Policy',
            'payroll_mode' => 'Fixed Salary Only',
            'attendance_impact_enabled' => 0,
            'effective_from' => date('Y-m-d'),
        ]);

        Session::flash('success', 'Attendance payroll rule set created. You can configure it below.');
        redirect('attendance-payroll/edit/' . $id);
    }

    public function edit(string $id): void
    {
        require_auth();
        require_role(['Super Admin', 'Admin', 'Finance Officer', 'HR Officer']);

        $rule = (new AttendancePayrollRule())->find((int) $id);
        if (!$rule) {
            Session::flash('error', 'Attendance payroll rule set not found.');
            redirect('attendance-payroll/index');
        }

        $this->render('attendance_payroll/edit', [
            'title' => 'Edit Attendance Payroll Rules',
            'rule' => $rule,
            'csrf' => Session::csrfToken(),
            'flashError' => Session::flash('error'),
        ]);
    }

    public function update(string $id): void
    {
        require_auth();
        require_role(['Super Admin', 'Admin', 'Finance Officer', 'HR Officer']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('attendance-payroll/index');
        }
        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid request token.');
            redirect('attendance-payroll/edit/' . (int) $id);
        }

        $data = [
            'name' => trim((string) $this->input('name', 'Default Attendance Payroll Policy')),
            'payroll_mode' => (string) $this->input('payroll_mode', 'Fixed Salary Only'),
            'attendance_impact_enabled' => (int) $this->input('attendance_impact_enabled', 0),
            'effective_from' => $this->normalizeDate((string) $this->input('effective_from', date('Y-m-d'))) ?? date('Y-m-d'),
            'effective_to' => $this->normalizeDate((string) $this->input('effective_to', '')),
            'standard_hours_per_day' => max(1, (float) $this->input('standard_hours_per_day', 8)),
            'standard_days_per_month' => max(1, (float) $this->input('standard_days_per_month', 26)),
            'standard_start_time' => $this->normalizeTime((string) $this->input('standard_start_time', '08:00')) ?? '08:00:00',
            'grace_minutes' => max(0, (int) $this->input('grace_minutes', 15)),
            'late_deduction_enabled' => (int) $this->input('late_deduction_enabled', 0),
            'late_rounding_minutes' => max(1, (int) $this->input('late_rounding_minutes', 15)),
            'absence_deduction_enabled' => (int) $this->input('absence_deduction_enabled', 0),
            'absence_deduction_method' => (string) $this->input('absence_deduction_method', 'Daily Rate'),
            'overtime_enabled' => (int) $this->input('overtime_enabled', 0),
            'overtime_requires_approval' => (int) $this->input('overtime_requires_approval', 1),
            'overtime_min_minutes' => max(0, (int) $this->input('overtime_min_minutes', 30)),
            'overtime_rounding_minutes' => max(1, (int) $this->input('overtime_rounding_minutes', 15)),
            'normal_overtime_multiplier' => max(0, (float) $this->input('normal_overtime_multiplier', 1.5)),
            'weekend_overtime_multiplier' => max(0, (float) $this->input('weekend_overtime_multiplier', 2)),
            'holiday_overtime_multiplier' => max(0, (float) $this->input('holiday_overtime_multiplier', 2)),
            'night_shift_allowance_enabled' => (int) $this->input('night_shift_allowance_enabled', 0),
            'night_shift_allowance_amount' => max(0, (float) $this->input('night_shift_allowance_amount', 0)),
            'notes' => trim((string) $this->input('notes', '')) ?: null,
            'is_active' => (int) $this->input('is_active', 0),
        ];

        if (!in_array($data['payroll_mode'], ['Fixed Salary Only', 'Fixed Salary + Attendance Adjustments', 'Attendance-Based Payroll', 'Mixed'], true)) {
            Session::flash('error', 'Invalid payroll mode.');
            redirect('attendance-payroll/edit/' . (int) $id);
        }
        if (!in_array($data['absence_deduction_method'], ['Daily Rate', 'Hourly Rate'], true)) {
            $data['absence_deduction_method'] = 'Daily Rate';
        }

        (new AttendancePayrollRule())->updateRule((int) $id, $data);
        AuditLog::record('attendance_payroll_rules_update', 'Updated attendance payroll rule set.', 'AttendancePayrollRule', (int) $id);
        Session::flash('success', 'Attendance payroll rules updated.');
        redirect('attendance-payroll/index');
    }

    private function normalizeDate(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }
        $date = date_create($value);
        return $date ? $date->format('Y-m-d') : null;
    }

    private function normalizeTime(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }
        $date = date_create($value);
        return $date ? $date->format('H:i:s') : null;
    }
}
