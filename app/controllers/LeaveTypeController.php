<?php

declare(strict_types=1);

class LeaveTypeController extends Controller
{
    public function index(): void
    {
        require_auth();
        require_role(['Super Admin', 'HR Officer']);

        $model = new LeaveType();
        $search = trim((string) $this->input('search', ''));

        $this->render('leave_types/index', [
            'title' => 'Leave Types',
            'types' => $search === '' ? $model->findAll() : $model->search($search),
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

        $model = new LeaveType();
        $this->render('leave_types/create', [
            'title' => 'Create Leave Type',
            'type' => null,
            'old' => $_SESSION['_old_leave_type_input'] ?? ['code' => $model->generateNextCode(), 'is_paid' => 1, 'is_active' => 1],
            'csrf' => Session::csrfToken(),
            'flashError' => Session::flash('error'),
        ]);

        unset($_SESSION['_old_leave_type_input']);
    }

    public function store(): void
    {
        require_auth();
        require_role(['Super Admin', 'HR Officer']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('leave-type/index');
        }
        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid request token.');
            redirect('leave-type/create');
        }

        $data = $this->collectInput();
        $_SESSION['_old_leave_type_input'] = $data;
        $model = new LeaveType();
        $error = $this->validate($model, $data);
        if ($error !== null) {
            Session::flash('error', $error);
            redirect('leave-type/create');
        }

        try {
            $model->insert($data);
            unset($_SESSION['_old_leave_type_input']);
            Session::flash('success', 'Leave type created successfully.');
            redirect('leave-type/index');
        } catch (Throwable $e) {
            Session::flash('error', 'Failed to create leave type: ' . $e->getMessage());
            redirect('leave-type/create');
        }
    }

    public function edit(string $id): void
    {
        require_auth();
        require_role(['Super Admin', 'HR Officer']);

        $model = new LeaveType();
        $type = $model->find((int) $id);
        if (!$type) {
            Session::flash('error', 'Leave type not found.');
            redirect('leave-type/index');
        }

        $this->render('leave_types/create', [
            'title' => 'Edit Leave Type',
            'type' => $type,
            'old' => $_SESSION['_old_leave_type_input'] ?? $type,
            'csrf' => Session::csrfToken(),
            'flashError' => Session::flash('error'),
        ]);

        unset($_SESSION['_old_leave_type_input']);
    }

    public function update(string $id): void
    {
        require_auth();
        require_role(['Super Admin', 'HR Officer']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('leave-type/index');
        }
        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid request token.');
            redirect('leave-type/edit/' . (int) $id);
        }

        $typeId = (int) $id;
        $model = new LeaveType();
        if (!$model->find($typeId)) {
            Session::flash('error', 'Leave type not found.');
            redirect('leave-type/index');
        }

        $data = $this->collectInput();
        $_SESSION['_old_leave_type_input'] = $data;
        $error = $this->validate($model, $data, $typeId);
        if ($error !== null) {
            Session::flash('error', $error);
            redirect('leave-type/edit/' . $typeId);
        }

        try {
            $model->update($typeId, $data);
            unset($_SESSION['_old_leave_type_input']);
            Session::flash('success', 'Leave type updated successfully.');
        } catch (Throwable $e) {
            Session::flash('error', 'Failed to update leave type: ' . $e->getMessage());
        }

        redirect('leave-type/index');
    }

    public function delete(string $id): void
    {
        require_auth();
        require_role(['Super Admin', 'HR Officer']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('leave-type/index');
        }
        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid request token.');
            redirect('leave-type/index');
        }

        try {
            (new LeaveType())->delete((int) $id);
            Session::flash('success', 'Leave type deleted successfully.');
        } catch (Throwable) {
            Session::flash('error', 'Leave type could not be deleted because it is in use.');
        }

        redirect('leave-type/index');
    }

    private function collectInput(): array
    {
        $code = strtoupper(trim((string) $this->input('code', '')));
        if ($code === '') {
            $code = (new LeaveType())->generateNextCode();
        }

        return [
            'name' => trim((string) $this->input('name', '')),
            'code' => $code,
            'days_per_year' => max(0, (int) $this->input('days_per_year', 0)),
            'is_paid' => (int) $this->input('is_paid', 0) === 1 ? 1 : 0,
            'is_active' => (int) $this->input('is_active', 0) === 1 ? 1 : 0,
        ];
    }

    private function validate(LeaveType $model, array $data, ?int $excludeId = null): ?string
    {
        if ($data['name'] === '') {
            return 'Leave type name is required.';
        }
        if ($model->nameExists((string) $data['name'], $excludeId)) {
            return 'A leave type with this name already exists for this company.';
        }
        if ($model->codeExists($data['code'], $excludeId)) {
            return 'A leave type with this code already exists for this company.';
        }

        return null;
    }
}
