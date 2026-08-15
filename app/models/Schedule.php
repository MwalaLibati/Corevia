<?php

declare(strict_types=1);

class Schedule extends Model
{
    protected string $table = 'shifts';
    protected bool $tenantScoped = true;

    public function __construct()
    {
        parent::__construct();
        $this->ensureSchema();
    }

    public function ensureSchema(): void
    {
        $this->db->exec(
            "CREATE TABLE IF NOT EXISTS shifts (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                company_id BIGINT UNSIGNED NOT NULL,
                name VARCHAR(120) NOT NULL,
                code VARCHAR(40) NOT NULL,
                start_time TIME NOT NULL,
                end_time TIME NOT NULL,
                break_minutes INT NOT NULL DEFAULT 0,
                grace_minutes INT NOT NULL DEFAULT 0,
                expected_hours DECIMAL(6,2) NOT NULL DEFAULT 8.00,
                overtime_after_hours DECIMAL(6,2) NULL,
                color VARCHAR(20) NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uq_shifts_company_code (company_id, code),
                KEY idx_shifts_company_active (company_id, is_active)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $this->db->exec(
            "CREATE TABLE IF NOT EXISTS work_patterns (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                company_id BIGINT UNSIGNED NOT NULL,
                name VARCHAR(140) NOT NULL,
                code VARCHAR(40) NOT NULL,
                description TEXT NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uq_patterns_company_code (company_id, code),
                KEY idx_patterns_company_active (company_id, is_active)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $this->db->exec(
            "CREATE TABLE IF NOT EXISTS work_pattern_days (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                pattern_id BIGINT UNSIGNED NOT NULL,
                day_of_week TINYINT UNSIGNED NOT NULL,
                shift_id BIGINT UNSIGNED NULL,
                is_working_day TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uq_pattern_day (pattern_id, day_of_week),
                KEY idx_pattern_days_shift (shift_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $this->db->exec(
            "CREATE TABLE IF NOT EXISTS employee_schedule_assignments (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                company_id BIGINT UNSIGNED NOT NULL,
                employee_id BIGINT UNSIGNED NOT NULL,
                pattern_id BIGINT UNSIGNED NOT NULL,
                effective_from DATE NOT NULL,
                effective_to DATE NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                notes TEXT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                KEY idx_assignments_company_employee (company_id, employee_id, effective_from, effective_to),
                KEY idx_assignments_pattern (pattern_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $this->db->exec(
            "CREATE TABLE IF NOT EXISTS schedule_exceptions (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                company_id BIGINT UNSIGNED NOT NULL,
                employee_id BIGINT UNSIGNED NOT NULL,
                exception_date DATE NOT NULL,
                shift_id BIGINT UNSIGNED NULL,
                exception_type ENUM('Shift Change','Rest Day','Public Holiday','Leave','Unscheduled Work') NOT NULL DEFAULT 'Shift Change',
                notes TEXT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uq_schedule_exception (company_id, employee_id, exception_date),
                KEY idx_schedule_exception_shift (shift_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }

    public function shifts(bool $activeOnly = false): array
    {
        $sql = 'SELECT * FROM shifts WHERE company_id = :cid';
        if ($activeOnly) {
            $sql .= ' AND is_active = 1';
        }
        $sql .= ' ORDER BY is_active DESC, name ASC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['cid' => Tenant::id()]);
        return $stmt->fetchAll();
    }

    public function patterns(): array
    {
        $stmt = $this->db->prepare(
            'SELECT wp.*,
                    COUNT(wpd.id) AS configured_days,
                    SUM(CASE WHEN wpd.is_working_day = 1 THEN 1 ELSE 0 END) AS working_days
             FROM work_patterns wp
             LEFT JOIN work_pattern_days wpd ON wpd.pattern_id = wp.id
             WHERE wp.company_id = :cid
             GROUP BY wp.id
             ORDER BY wp.is_active DESC, wp.name ASC'
        );
        $stmt->execute(['cid' => Tenant::id()]);
        return $stmt->fetchAll();
    }

    public function pattern(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM work_patterns WHERE id = :id AND company_id = :cid LIMIT 1');
        $stmt->execute(['id' => $id, 'cid' => Tenant::id()]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function patternDays(int $patternId): array
    {
        $stmt = $this->db->prepare(
            'SELECT wpd.*, s.name AS shift_name, s.start_time, s.end_time
             FROM work_pattern_days wpd
             LEFT JOIN shifts s ON s.id = wpd.shift_id
             WHERE wpd.pattern_id = :pid
             ORDER BY wpd.day_of_week ASC'
        );
        $stmt->execute(['pid' => $patternId]);
        $rows = [];
        foreach ($stmt->fetchAll() as $row) {
            $rows[(int) $row['day_of_week']] = $row;
        }
        return $rows;
    }

    public function assignments(): array
    {
        $stmt = $this->db->prepare(
            'SELECT esa.*, e.full_name, e.employee_number, wp.name AS pattern_name
             FROM employee_schedule_assignments esa
             JOIN employees e ON e.id = esa.employee_id
             JOIN work_patterns wp ON wp.id = esa.pattern_id
             WHERE esa.company_id = :cid
             ORDER BY esa.is_active DESC, e.full_name ASC, esa.effective_from DESC'
        );
        $stmt->execute(['cid' => Tenant::id()]);
        return $stmt->fetchAll();
    }

    public function employees(): array
    {
        $stmt = $this->db->prepare(
            'SELECT id, employee_number, full_name
             FROM employees
             WHERE company_id = :cid AND archived_at IS NULL
             ORDER BY full_name ASC'
        );
        $stmt->execute(['cid' => Tenant::id()]);
        return $stmt->fetchAll();
    }

    public function saveShift(array $data, ?int $id = null): int
    {
        $payload = [
            'company_id' => Tenant::id(),
            'name' => trim((string) $data['name']),
            'code' => strtoupper(trim((string) $data['code'])),
            'start_time' => (string) $data['start_time'],
            'end_time' => (string) $data['end_time'],
            'break_minutes' => max(0, (int) $data['break_minutes']),
            'grace_minutes' => max(0, (int) $data['grace_minutes']),
            'expected_hours' => $this->expectedHours((string) $data['start_time'], (string) $data['end_time'], (int) $data['break_minutes']),
            'overtime_after_hours' => $data['overtime_after_hours'] === '' ? null : max(0, (float) $data['overtime_after_hours']),
            'color' => trim((string) ($data['color'] ?? '')) ?: null,
            'is_active' => !empty($data['is_active']) ? 1 : 0,
        ];

        if ($id !== null) {
            $this->updateShift($id, $payload);
            return $id;
        }

        $stmt = $this->db->prepare(
            'INSERT INTO shifts (company_id, name, code, start_time, end_time, break_minutes, grace_minutes, expected_hours, overtime_after_hours, color, is_active)
             VALUES (:company_id, :name, :code, :start_time, :end_time, :break_minutes, :grace_minutes, :expected_hours, :overtime_after_hours, :color, :is_active)'
        );
        $stmt->execute($payload);
        return (int) $this->db->lastInsertId();
    }

    public function savePattern(array $data, array $days, ?int $id = null): int
    {
        $payload = [
            'company_id' => Tenant::id(),
            'name' => trim((string) $data['name']),
            'code' => strtoupper(trim((string) $data['code'])),
            'description' => trim((string) ($data['description'] ?? '')) ?: null,
            'is_active' => !empty($data['is_active']) ? 1 : 0,
        ];

        $this->db->beginTransaction();
        try {
            if ($id === null) {
                $stmt = $this->db->prepare(
                    'INSERT INTO work_patterns (company_id, name, code, description, is_active)
                     VALUES (:company_id, :name, :code, :description, :is_active)'
                );
                $stmt->execute($payload);
                $id = (int) $this->db->lastInsertId();
            } else {
                $stmt = $this->db->prepare(
                    'UPDATE work_patterns SET name = :name, code = :code, description = :description, is_active = :is_active
                     WHERE id = :id AND company_id = :company_id'
                );
                $payload['id'] = $id;
                $stmt->execute($payload);
                $this->db->prepare('DELETE FROM work_pattern_days WHERE pattern_id = :id')->execute(['id' => $id]);
            }

            $insert = $this->db->prepare(
                'INSERT INTO work_pattern_days (pattern_id, day_of_week, shift_id, is_working_day)
                 VALUES (:pattern_id, :day_of_week, :shift_id, :is_working_day)'
            );
            for ($day = 1; $day <= 7; $day++) {
                $shiftId = (int) ($days[$day] ?? 0);
                $insert->execute([
                    'pattern_id' => $id,
                    'day_of_week' => $day,
                    'shift_id' => $shiftId > 0 ? $shiftId : null,
                    'is_working_day' => $shiftId > 0 ? 1 : 0,
                ]);
            }

            $this->db->commit();
            return $id;
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }
    }

    public function assignPattern(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO employee_schedule_assignments (company_id, employee_id, pattern_id, effective_from, effective_to, is_active, notes)
             VALUES (:company_id, :employee_id, :pattern_id, :effective_from, :effective_to, 1, :notes)'
        );
        $stmt->execute([
            'company_id' => Tenant::id(),
            'employee_id' => (int) $data['employee_id'],
            'pattern_id' => (int) $data['pattern_id'],
            'effective_from' => (string) $data['effective_from'],
            'effective_to' => trim((string) ($data['effective_to'] ?? '')) ?: null,
            'notes' => trim((string) ($data['notes'] ?? '')) ?: null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function scheduleForEmployeeDate(int $employeeId, string $date): ?array
    {
        $exception = $this->exceptionForEmployeeDate($employeeId, $date);
        if ($exception !== null) {
            return $exception;
        }

        $stmt = $this->db->prepare(
            'SELECT esa.id AS assignment_id, wp.name AS pattern_name, wpd.is_working_day,
                    s.id AS shift_id, s.name AS shift_name, s.start_time, s.end_time, s.break_minutes,
                    s.grace_minutes, s.expected_hours, s.overtime_after_hours
             FROM employee_schedule_assignments esa
             JOIN work_patterns wp ON wp.id = esa.pattern_id
             JOIN work_pattern_days wpd ON wpd.pattern_id = wp.id AND wpd.day_of_week = :dow
             LEFT JOIN shifts s ON s.id = wpd.shift_id
             WHERE esa.company_id = :cid
               AND esa.employee_id = :employee_id
               AND esa.is_active = 1
               AND esa.effective_from <= :date_from
               AND (esa.effective_to IS NULL OR esa.effective_to >= :date_to)
             ORDER BY esa.effective_from DESC, esa.id DESC
             LIMIT 1'
        );
        $stmt->execute([
            'dow' => (int) date('N', strtotime($date)),
            'cid' => Tenant::id(),
            'employee_id' => $employeeId,
            'date_from' => $date,
            'date_to' => $date,
        ]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function compareAttendance(array $record): array
    {
        $date = (string) ($record['attendance_date'] ?? '');
        $schedule = $this->scheduleForEmployeeDate((int) ($record['employee_id'] ?? 0), $date);
        if (!$schedule) {
            return ['status' => 'Unscheduled', 'label' => 'No schedule', 'late_minutes' => 0, 'early_minutes' => 0, 'overtime_minutes' => 0, 'schedule' => null];
        }

        if (empty($schedule['is_working_day']) || empty($schedule['shift_id'])) {
            return ['status' => 'Rest Day', 'label' => 'Rest day', 'late_minutes' => 0, 'early_minutes' => 0, 'overtime_minutes' => $this->workedMinutes($record), 'schedule' => $schedule];
        }

        $worked = $this->workedMinutes($record);
        if (in_array((string) ($record['status'] ?? ''), ['Absent', 'Leave'], true)) {
            $worked = 0;
        }

        $late = 0;
        $early = 0;
        if (!empty($record['check_in'])) {
            $late = max(0, $this->minutesSinceMidnight((string) $record['check_in']) - $this->minutesSinceMidnight((string) $schedule['start_time']) - (int) $schedule['grace_minutes']);
        }
        if (!empty($record['check_out'])) {
            $early = max(0, $this->minutesSinceMidnight((string) $schedule['end_time']) - $this->minutesSinceMidnight((string) $record['check_out']));
        }

        $expectedMinutes = (int) round((float) $schedule['expected_hours'] * 60);
        $otAfter = (float) ($schedule['overtime_after_hours'] ?? 0) > 0 ? (int) round((float) $schedule['overtime_after_hours'] * 60) : $expectedMinutes;
        $overtime = max(0, $worked - $otAfter);
        $status = $worked <= 0 ? 'Absent' : ($late > 0 ? 'Late' : ($early > 0 ? 'Early Departure' : 'On Time'));

        return [
            'status' => $status,
            'label' => trim((string) $schedule['shift_name']) . ' ' . substr((string) $schedule['start_time'], 0, 5) . '-' . substr((string) $schedule['end_time'], 0, 5),
            'late_minutes' => $late,
            'early_minutes' => $early,
            'overtime_minutes' => $overtime,
            'schedule' => $schedule,
        ];
    }

    public function roster(string $month): array
    {
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = date('Y-m');
        }
        $start = $month . '-01';
        $end = date('Y-m-t', strtotime($start));
        $employees = array_slice($this->employees(), 0, 12);
        $rows = [];
        foreach ($employees as $employee) {
            for ($ts = strtotime($start); $ts <= strtotime($end); $ts = strtotime('+1 day', $ts)) {
                $date = date('Y-m-d', $ts);
                $schedule = $this->scheduleForEmployeeDate((int) $employee['id'], $date);
                if ($schedule && !empty($schedule['shift_id'])) {
                    $rows[] = ['employee' => $employee, 'date' => $date, 'schedule' => $schedule];
                }
            }
        }
        return array_slice($rows, 0, 80);
    }

    public function employeeRoster(int $employeeId, string $month): array
    {
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = date('Y-m');
        }
        $employee = $this->employee($employeeId);
        if (!$employee) {
            return [];
        }
        $start = $month . '-01';
        $end = date('Y-m-t', strtotime($start));
        $rows = [];
        for ($ts = strtotime($start); $ts <= strtotime($end); $ts = strtotime('+1 day', $ts)) {
            $date = date('Y-m-d', $ts);
            $schedule = $this->scheduleForEmployeeDate($employeeId, $date);
            $rows[] = ['employee' => $employee, 'date' => $date, 'schedule' => $schedule];
        }
        return $rows;
    }

    public function varianceRows(string $month, int $employeeId = 0): array
    {
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = date('Y-m');
        }
        $params = ['cid' => Tenant::id(), 'month' => $month];
        $employeeFilter = '';
        if ($employeeId > 0) {
            $employeeFilter = ' AND ar.employee_id = :employee_id';
            $params['employee_id'] = $employeeId;
        }
        $stmt = $this->db->prepare(
            "SELECT ar.*, e.employee_number, e.full_name
             FROM attendance_records ar
             JOIN employees e ON e.id = ar.employee_id
             WHERE e.company_id = :cid
               AND DATE_FORMAT(ar.attendance_date, '%Y-%m') = :month
               {$employeeFilter}
             ORDER BY ar.attendance_date DESC, e.full_name ASC"
        );
        $stmt->execute($params);

        $rows = [];
        foreach ($stmt->fetchAll() as $record) {
            $compare = $this->compareAttendance($record);
            $workedMinutes = $this->workedMinutes($record);
            $expectedMinutes = 0;
            if (!empty($compare['schedule']['expected_hours'])) {
                $expectedMinutes = (int) round((float) $compare['schedule']['expected_hours'] * 60);
            }
            $rows[] = [
                'attendance_date' => $record['attendance_date'],
                'employee_number' => $record['employee_number'],
                'full_name' => $record['full_name'],
                'attendance_status' => $record['status'],
                'schedule_status' => $compare['status'],
                'shift' => $compare['label'],
                'expected_hours' => round($expectedMinutes / 60, 2),
                'worked_hours' => round($workedMinutes / 60, 2),
                'late_minutes' => (int) $compare['late_minutes'],
                'early_minutes' => (int) $compare['early_minutes'],
                'overtime_hours' => round(((int) $compare['overtime_minutes']) / 60, 2),
            ];
        }
        return $rows;
    }

    public function deleteShift(int $id): bool
    {
        $stmt = $this->db->prepare('UPDATE shifts SET is_active = 0 WHERE id = :id AND company_id = :cid');
        return $stmt->execute(['id' => $id, 'cid' => Tenant::id()]);
    }

    public function deletePattern(int $id): bool
    {
        $stmt = $this->db->prepare('UPDATE work_patterns SET is_active = 0 WHERE id = :id AND company_id = :cid');
        return $stmt->execute(['id' => $id, 'cid' => Tenant::id()]);
    }

    public function deleteAssignment(int $id): bool
    {
        $stmt = $this->db->prepare('UPDATE employee_schedule_assignments SET is_active = 0 WHERE id = :id AND company_id = :cid');
        return $stmt->execute(['id' => $id, 'cid' => Tenant::id()]);
    }

    private function updateShift(int $id, array $payload): void
    {
        $payload['id'] = $id;
        $stmt = $this->db->prepare(
            'UPDATE shifts SET name = :name, code = :code, start_time = :start_time, end_time = :end_time,
                    break_minutes = :break_minutes, grace_minutes = :grace_minutes, expected_hours = :expected_hours,
                    overtime_after_hours = :overtime_after_hours, color = :color, is_active = :is_active
             WHERE id = :id AND company_id = :company_id'
        );
        $stmt->execute($payload);
    }

    private function exceptionForEmployeeDate(int $employeeId, string $date): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT se.exception_type AS pattern_name,
                    CASE WHEN se.exception_type IN (\'Rest Day\',\'Public Holiday\',\'Leave\') THEN 0 ELSE 1 END AS is_working_day,
                    s.id AS shift_id, s.name AS shift_name, s.start_time, s.end_time, s.break_minutes,
                    s.grace_minutes, s.expected_hours, s.overtime_after_hours
             FROM schedule_exceptions se
             LEFT JOIN shifts s ON s.id = se.shift_id
             WHERE se.company_id = :cid AND se.employee_id = :employee_id AND se.exception_date = :date
             LIMIT 1'
        );
        $stmt->execute(['cid' => Tenant::id(), 'employee_id' => $employeeId, 'date' => $date]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    private function employee(int $employeeId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, employee_number, full_name
             FROM employees
             WHERE id = :id AND company_id = :cid
             LIMIT 1'
        );
        $stmt->execute(['id' => $employeeId, 'cid' => Tenant::id()]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    private function expectedHours(string $start, string $end, int $breakMinutes): float
    {
        $minutes = $this->minutesSinceMidnight($end) - $this->minutesSinceMidnight($start);
        if ($minutes <= 0) {
            $minutes += 1440;
        }
        return round(max(0, $minutes - max(0, $breakMinutes)) / 60, 2);
    }

    private function workedMinutes(array $record): int
    {
        if (empty($record['check_in']) || empty($record['check_out'])) {
            return 0;
        }
        $minutes = $this->minutesSinceMidnight((string) $record['check_out']) - $this->minutesSinceMidnight((string) $record['check_in']);
        if ($minutes < 0) {
            $minutes += 1440;
        }
        return max(0, $minutes);
    }

    private function minutesSinceMidnight(string $time): int
    {
        $parts = explode(':', $time);
        return ((int) ($parts[0] ?? 0) * 60) + (int) ($parts[1] ?? 0);
    }
}
