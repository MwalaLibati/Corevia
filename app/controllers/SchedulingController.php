<?php

declare(strict_types=1);

class SchedulingController extends Controller
{
    private array $days = [1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday'];

    public function index(): void
    {
        require_auth();
        require_role(['Super Admin', 'HR Officer']);

        $model = new Schedule();
        $month = $this->normalizeMonth((string) $this->input('month', date('Y-m')));

        $this->render('scheduling/index', [
            'title' => 'Scheduling',
            'shifts' => $model->shifts(),
            'activeShifts' => $model->shifts(true),
            'patterns' => $model->patterns(),
            'assignments' => $model->assignments(),
            'employees' => $model->employees(),
            'roster' => $model->roster($month),
            'month' => $month,
            'days' => $this->days,
            'csrf' => Session::csrfToken(),
            'flashSuccess' => Session::flash('success'),
            'flashError' => Session::flash('error'),
        ]);
    }

    public function storeShift(): void
    {
        $this->guardPost('scheduling/index');
        $data = $this->shiftInput();
        if ($data['name'] === '' || $data['code'] === '' || $data['start_time'] === '' || $data['end_time'] === '') {
            Session::flash('error', 'Shift name, code, start time and end time are required.');
            redirect('scheduling/index');
        }

        try {
            (new Schedule())->saveShift($data);
            Session::flash('success', 'Shift created successfully.');
        } catch (Throwable $exception) {
            Session::flash('error', 'Shift could not be saved: ' . $exception->getMessage());
        }
        redirect('scheduling/index');
    }

    public function editShift(string $id): void
    {
        require_auth();
        require_role(['Super Admin', 'HR Officer']);
        $model = new Schedule();
        $shift = $model->find((int) $id);
        if (!$shift) {
            Session::flash('error', 'Shift not found.');
            redirect('scheduling/index');
        }
        $this->render('scheduling/shift-form', [
            'title' => 'Edit Shift',
            'shift' => $shift,
            'csrf' => Session::csrfToken(),
            'flashError' => Session::flash('error'),
        ]);
    }

    public function updateShift(string $id): void
    {
        $this->guardPost('scheduling/editShift/' . (int) $id);
        try {
            (new Schedule())->saveShift($this->shiftInput(), (int) $id);
            Session::flash('success', 'Shift updated successfully.');
            redirect('scheduling/index');
        } catch (Throwable $exception) {
            Session::flash('error', 'Shift could not be updated: ' . $exception->getMessage());
            redirect('scheduling/editShift/' . (int) $id);
        }
    }

    public function deleteShift(string $id): void
    {
        $this->guardPost('scheduling/index');
        (new Schedule())->deleteShift((int) $id);
        Session::flash('success', 'Shift archived.');
        redirect('scheduling/index');
    }

    public function storePattern(): void
    {
        $this->guardPost('scheduling/index');
        $data = $this->patternInput();
        if ($data['name'] === '' || $data['code'] === '') {
            Session::flash('error', 'Pattern name and code are required.');
            redirect('scheduling/index');
        }
        try {
            (new Schedule())->savePattern($data, $this->dayInput());
            Session::flash('success', 'Work pattern created successfully.');
        } catch (Throwable $exception) {
            Session::flash('error', 'Work pattern could not be saved: ' . $exception->getMessage());
        }
        redirect('scheduling/index');
    }

    public function editPattern(string $id): void
    {
        require_auth();
        require_role(['Super Admin', 'HR Officer']);
        $model = new Schedule();
        $pattern = $model->pattern((int) $id);
        if (!$pattern) {
            Session::flash('error', 'Work pattern not found.');
            redirect('scheduling/index');
        }

        $this->render('scheduling/pattern-form', [
            'title' => 'Edit Work Pattern',
            'pattern' => $pattern,
            'patternDays' => $model->patternDays((int) $id),
            'activeShifts' => $model->shifts(true),
            'days' => $this->days,
            'csrf' => Session::csrfToken(),
            'flashError' => Session::flash('error'),
        ]);
    }

    public function updatePattern(string $id): void
    {
        $this->guardPost('scheduling/editPattern/' . (int) $id);
        try {
            (new Schedule())->savePattern($this->patternInput(), $this->dayInput(), (int) $id);
            Session::flash('success', 'Work pattern updated successfully.');
            redirect('scheduling/index');
        } catch (Throwable $exception) {
            Session::flash('error', 'Work pattern could not be updated: ' . $exception->getMessage());
            redirect('scheduling/editPattern/' . (int) $id);
        }
    }

    public function deletePattern(string $id): void
    {
        $this->guardPost('scheduling/index');
        (new Schedule())->deletePattern((int) $id);
        Session::flash('success', 'Work pattern archived.');
        redirect('scheduling/index');
    }

    public function assign(): void
    {
        $this->guardPost('scheduling/index');
        $data = [
            'employee_id' => (int) $this->input('employee_id', 0),
            'pattern_id' => (int) $this->input('pattern_id', 0),
            'effective_from' => (string) $this->input('effective_from', date('Y-m-d')),
            'effective_to' => (string) $this->input('effective_to', ''),
            'notes' => (string) $this->input('notes', ''),
        ];
        if ($data['employee_id'] <= 0 || $data['pattern_id'] <= 0 || !$this->validDate($data['effective_from'])) {
            Session::flash('error', 'Employee, work pattern and start date are required.');
            redirect('scheduling/index');
        }
        if ($data['effective_to'] !== '' && !$this->validDate($data['effective_to'])) {
            Session::flash('error', 'End date must be a valid date.');
            redirect('scheduling/index');
        }
        (new Schedule())->assignPattern($data);
        Session::flash('success', 'Schedule assigned successfully.');
        redirect('scheduling/index');
    }

    public function deleteAssignment(string $id): void
    {
        $this->guardPost('scheduling/index');
        (new Schedule())->deleteAssignment((int) $id);
        Session::flash('success', 'Schedule assignment archived.');
        redirect('scheduling/index');
    }

    private function guardPost(string $redirect): void
    {
        require_auth();
        require_role(['Super Admin', 'HR Officer']);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid request token.');
            redirect($redirect);
        }
    }

    private function shiftInput(): array
    {
        return [
            'name' => trim((string) $this->input('name', '')),
            'code' => trim((string) $this->input('code', '')),
            'start_time' => trim((string) $this->input('start_time', '')),
            'end_time' => trim((string) $this->input('end_time', '')),
            'break_minutes' => (int) $this->input('break_minutes', 0),
            'grace_minutes' => (int) $this->input('grace_minutes', 0),
            'overtime_after_hours' => trim((string) $this->input('overtime_after_hours', '')),
            'color' => trim((string) $this->input('color', '#2563eb')),
            'is_active' => (int) $this->input('is_active', 1),
        ];
    }

    private function patternInput(): array
    {
        return [
            'name' => trim((string) $this->input('name', '')),
            'code' => trim((string) $this->input('code', '')),
            'description' => trim((string) $this->input('description', '')),
            'is_active' => (int) $this->input('is_active', 1),
        ];
    }

    private function dayInput(): array
    {
        $days = [];
        foreach ($this->days as $number => $_name) {
            $days[$number] = (int) $this->input('day_' . $number . '_shift_id', 0);
        }
        return $days;
    }

    private function normalizeMonth(string $month): string
    {
        return preg_match('/^\d{4}-\d{2}$/', $month) ? $month : date('Y-m');
    }

    private function validDate(string $date): bool
    {
        return (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) && strtotime($date) !== false;
    }
}
