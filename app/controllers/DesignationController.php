<?php

declare(strict_types=1);

class DesignationController extends Controller
{
    public function index(): void
    {
        require_auth();
        require_role(['Super Admin', 'HR Officer']);

        $search = trim((string) $this->input('search', ''));
        $model = new Designation();
        $setupWarning = $model->isReady() ? null : 'Designation setup is not complete on this server. Please run the production database migration, then reload this page.';

        $this->render('designations/index', [
            'title' => 'Designations',
            'designations' => $model->listWithDepartment($search),
            'search' => $search,
            'setupWarning' => $setupWarning,
            'csrf' => Session::csrfToken(),
            'flashSuccess' => Session::flash('success'),
            'flashError' => Session::flash('error'),
        ]);
    }

    public function create(): void
    {
        require_auth();
        require_role(['Super Admin', 'HR Officer']);

        $model = new Designation();
        $this->render('designations/create', [
            'title' => 'Create Designation',
            'designation' => null,
            'departments' => (new Employee())->departments(),
            'old' => $_SESSION['_old_designation_input'] ?? ['code' => $model->generateNextCode(), 'is_active' => 1],
            'csrf' => Session::csrfToken(),
            'flashError' => Session::flash('error'),
        ]);
        unset($_SESSION['_old_designation_input']);
    }

    public function store(): void
    {
        require_auth();
        require_role(['Super Admin', 'HR Officer']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid request token.');
            redirect('designation/create');
        }

        $data = $this->collectInput();
        $_SESSION['_old_designation_input'] = $data;
        $model = new Designation();
        $error = $this->validate($model, $data);
        if ($error !== null) {
            Session::flash('error', $error);
            redirect('designation/create');
        }

        $model->insert($data);
        unset($_SESSION['_old_designation_input']);
        Session::flash('success', 'Designation created successfully.');
        redirect('designation/index');
    }

    public function edit(string $id): void
    {
        require_auth();
        require_role(['Super Admin', 'HR Officer']);

        $model = new Designation();
        $designation = $model->find((int) $id);
        if (!$designation) {
            Session::flash('error', 'Designation not found.');
            redirect('designation/index');
        }

        $this->render('designations/create', [
            'title' => 'Edit Designation',
            'designation' => $designation,
            'departments' => (new Employee())->departments(),
            'old' => $_SESSION['_old_designation_input'] ?? $designation,
            'csrf' => Session::csrfToken(),
            'flashError' => Session::flash('error'),
        ]);
        unset($_SESSION['_old_designation_input']);
    }

    public function update(string $id): void
    {
        require_auth();
        require_role(['Super Admin', 'HR Officer']);

        $designationId = (int) $id;
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid request token.');
            redirect('designation/edit/' . $designationId);
        }

        $model = new Designation();
        if (!$model->find($designationId)) {
            Session::flash('error', 'Designation not found.');
            redirect('designation/index');
        }

        $data = $this->collectInput();
        $_SESSION['_old_designation_input'] = $data;
        $error = $this->validate($model, $data, $designationId);
        if ($error !== null) {
            Session::flash('error', $error);
            redirect('designation/edit/' . $designationId);
        }

        $model->update($designationId, $data);
        unset($_SESSION['_old_designation_input']);
        Session::flash('success', 'Designation updated successfully.');
        redirect('designation/index');
    }

    public function delete(string $id): void
    {
        require_auth();
        require_role(['Super Admin', 'HR Officer']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid request token.');
            redirect('designation/index');
        }

        (new Designation())->update((int) $id, ['is_active' => 0]);
        Session::flash('success', 'Designation deactivated successfully.');
        redirect('designation/index');
    }

    private function collectInput(): array
    {
        $departmentId = (int) $this->input('department_id', 0);
        $code = strtoupper(trim((string) $this->input('code', '')));
        if ($code === '') {
            $code = (new Designation())->generateNextCode();
        }

        return [
            'department_id' => $departmentId > 0 ? $departmentId : null,
            'code' => $code,
            'name' => trim((string) $this->input('name', '')),
            'description' => trim((string) $this->input('description', '')) ?: null,
            'is_active' => (int) $this->input('is_active', 0) === 1 ? 1 : 0,
        ];
    }

    private function validate(Designation $model, array $data, ?int $excludeId = null): ?string
    {
        if ((string) $data['name'] === '') {
            return 'Designation name is required.';
        }
        if ($model->nameExists((string) $data['name'], $excludeId)) {
            return 'A designation with this name already exists for this company.';
        }
        if ($model->codeExists((string) $data['code'], $excludeId)) {
            return 'A designation with this code already exists for this company.';
        }

        return null;
    }
}
