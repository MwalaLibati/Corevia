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

        $model = new AttendanceRecord();
        $search = trim((string) $this->input('search', ''));
        $records = $search === '' ? $model->listWithEmployee() : $model->search($search);

        $this->render('attendance/index', [
            'title' => 'Attendance & Leave',
            'records' => $records,
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
}
