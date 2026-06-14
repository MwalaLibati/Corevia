<?php

declare(strict_types=1);

class DepartmentController extends Controller
{
    public function index(): void
    {
        require_auth();
        require_role(['Super Admin', 'HR Officer']);

        $model = new Department();
        $search = trim((string) $this->input('search', ''));

        $this->render('departments/index', [
            'title' => 'Departments',
            'departments' => $search === '' ? $model->findAll() : $model->search($search),
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

        $model = new Department();
        $this->render('departments/create', [
            'title' => 'Create Department',
            'department' => null,
            'old' => $_SESSION['_old_department_input'] ?? ['code' => $model->generateNextCode()],
            'csrf' => Session::csrfToken(),
            'flashError' => Session::flash('error'),
        ]);

        unset($_SESSION['_old_department_input']);
    }

    public function store(): void
    {
        require_auth();
        require_role(['Super Admin', 'HR Officer']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('department/index');
        }
        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid request token.');
            redirect('department/create');
        }

        $data = $this->collectInput();
        $_SESSION['_old_department_input'] = $data;
        $model = new Department();
        $error = $this->validate($model, $data);
        if ($error !== null) {
            Session::flash('error', $error);
            redirect('department/create');
        }

        try {
            $model->insert($data);
            unset($_SESSION['_old_department_input']);
            Session::flash('success', 'Department created successfully.');
            redirect('department/index');
        } catch (Throwable $e) {
            Session::flash('error', 'Failed to create department: ' . $e->getMessage());
            redirect('department/create');
        }
    }

    public function edit(string $id): void
    {
        require_auth();
        require_role(['Super Admin', 'HR Officer']);

        $model = new Department();
        $department = $model->find((int) $id);
        if (!$department) {
            Session::flash('error', 'Department not found.');
            redirect('department/index');
        }

        $this->render('departments/create', [
            'title' => 'Edit Department',
            'department' => $department,
            'old' => $_SESSION['_old_department_input'] ?? $department,
            'csrf' => Session::csrfToken(),
            'flashError' => Session::flash('error'),
        ]);

        unset($_SESSION['_old_department_input']);
    }

    public function update(string $id): void
    {
        require_auth();
        require_role(['Super Admin', 'HR Officer']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('department/index');
        }
        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid request token.');
            redirect('department/edit/' . (int) $id);
        }

        $departmentId = (int) $id;
        $model = new Department();
        if (!$model->find($departmentId)) {
            Session::flash('error', 'Department not found.');
            redirect('department/index');
        }

        $data = $this->collectInput();
        $_SESSION['_old_department_input'] = $data;
        $error = $this->validate($model, $data, $departmentId);
        if ($error !== null) {
            Session::flash('error', $error);
            redirect('department/edit/' . $departmentId);
        }

        try {
            $model->update($departmentId, $data);
            unset($_SESSION['_old_department_input']);
            Session::flash('success', 'Department updated successfully.');
        } catch (Throwable $e) {
            Session::flash('error', 'Failed to update department: ' . $e->getMessage());
        }

        redirect('department/index');
    }

    public function delete(string $id): void
    {
        require_auth();
        require_role(['Super Admin', 'HR Officer']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('department/index');
        }
        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid request token.');
            redirect('department/index');
        }

        try {
            (new Department())->delete((int) $id);
            Session::flash('success', 'Department deleted successfully.');
        } catch (Throwable) {
            Session::flash('error', 'Department could not be deleted because it is in use.');
        }

        redirect('department/index');
    }

    private function collectInput(): array
    {
        $code = strtoupper(trim((string) $this->input('code', '')));
        if ($code === '') {
            $code = (new Department())->generateNextCode();
        }

        return [
            'name' => trim((string) $this->input('name', '')),
            'code' => $code,
        ];
    }

    private function validate(Department $model, array $data, ?int $excludeId = null): ?string
    {
        if ($data['name'] === '') {
            return 'Department name is required.';
        }
        if ($model->nameExists((string) $data['name'], $excludeId)) {
            return 'A department with this name already exists for this company.';
        }
        if ($model->codeExists($data['code'], $excludeId)) {
            return 'A department with this code already exists for this company.';
        }

        return null;
    }
}
