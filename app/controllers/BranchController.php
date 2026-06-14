<?php

declare(strict_types=1);

class BranchController extends Controller
{
    public function index(): void
    {
        require_auth();
        require_role(['Super Admin', 'HR Officer']);

        $model = new Branch();
        $search = trim((string) $this->input('search', ''));

        $this->render('branches/index', [
            'title' => 'Branches',
            'branches' => $search === '' ? $model->listWithCounts() : $model->search($search),
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

        $model = new Branch();
        $this->render('branches/create', [
            'title' => 'Create Branch',
            'branch' => null,
            'old' => $_SESSION['_old_branch_input'] ?? ['code' => $model->generateNextCode(), 'is_active' => 1],
            'employees' => (new Employee())->listWithDepartment(),
            'csrf' => Session::csrfToken(),
            'flashError' => Session::flash('error'),
        ]);

        unset($_SESSION['_old_branch_input']);
    }

    public function store(): void
    {
        require_auth();
        require_role(['Super Admin', 'HR Officer']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('branch/index');
        }
        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid request token.');
            redirect('branch/create');
        }

        $data = $this->collectInput();
        $_SESSION['_old_branch_input'] = $data;
        $model = new Branch();
        $error = $this->validate($model, $data);
        if ($error !== null) {
            Session::flash('error', $error);
            redirect('branch/create');
        }

        try {
            $model->insert($data);
            unset($_SESSION['_old_branch_input']);
            AuditLog::record('created', 'Created branch ' . $data['name'], 'Branch');
            Session::flash('success', 'Branch created successfully.');
            redirect('branch/index');
        } catch (Throwable $e) {
            Session::flash('error', 'Failed to create branch: ' . $e->getMessage());
            redirect('branch/create');
        }
    }

    public function edit(string $id): void
    {
        require_auth();
        require_role(['Super Admin', 'HR Officer']);

        $model = new Branch();
        $branch = $model->find((int) $id);
        if (!$branch) {
            Session::flash('error', 'Branch not found.');
            redirect('branch/index');
        }

        $this->render('branches/create', [
            'title' => 'Edit Branch',
            'branch' => $branch,
            'old' => $_SESSION['_old_branch_input'] ?? $branch,
            'employees' => (new Employee())->listWithDepartment(),
            'csrf' => Session::csrfToken(),
            'flashError' => Session::flash('error'),
        ]);

        unset($_SESSION['_old_branch_input']);
    }

    public function update(string $id): void
    {
        require_auth();
        require_role(['Super Admin', 'HR Officer']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('branch/index');
        }
        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid request token.');
            redirect('branch/edit/' . (int) $id);
        }

        $branchId = (int) $id;
        $model = new Branch();
        $branch = $model->find($branchId);
        if (!$branch) {
            Session::flash('error', 'Branch not found.');
            redirect('branch/index');
        }

        $data = $this->collectInput();
        $_SESSION['_old_branch_input'] = $data;
        $error = $this->validate($model, $data, $branchId);
        if ($error !== null) {
            Session::flash('error', $error);
            redirect('branch/edit/' . $branchId);
        }

        try {
            $model->update($branchId, $data);
            unset($_SESSION['_old_branch_input']);
            AuditLog::recordChanges('updated', 'Updated branch ' . $data['name'], 'Branch', $branchId, $branch, $data);
            Session::flash('success', 'Branch updated successfully.');
        } catch (Throwable $e) {
            Session::flash('error', 'Failed to update branch: ' . $e->getMessage());
        }

        redirect('branch/index');
    }

    public function delete(string $id): void
    {
        require_auth();
        require_role(['Super Admin', 'HR Officer']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('branch/index');
        }
        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid request token.');
            redirect('branch/index');
        }

        try {
            (new Branch())->delete((int) $id);
            AuditLog::record('deleted', 'Deleted branch.', 'Branch', (int) $id);
            Session::flash('success', 'Branch deleted successfully.');
        } catch (Throwable) {
            Session::flash('error', 'Branch could not be deleted because it is in use.');
        }

        redirect('branch/index');
    }

    private function collectInput(): array
    {
        $managerId = (int) $this->input('manager_employee_id', 0);
        $code = strtoupper(trim((string) $this->input('code', '')));
        if ($code === '') {
            $code = (new Branch())->generateNextCode();
        }

        return [
            'name' => trim((string) $this->input('name', '')),
            'code' => $code,
            'phone' => $this->nullable((string) $this->input('phone', '')),
            'email' => $this->nullable((string) $this->input('email', '')),
            'address' => $this->nullable((string) $this->input('address', '')),
            'city' => $this->nullable((string) $this->input('city', '')),
            'manager_employee_id' => $managerId > 0 ? $managerId : null,
            'is_active' => (int) $this->input('is_active', 1) === 1 ? 1 : 0,
        ];
    }

    private function validate(Branch $model, array $data, ?int $excludeId = null): ?string
    {
        if ($data['name'] === '') {
            return 'Branch name is required.';
        }
        if ($model->nameExists((string) $data['name'], $excludeId)) {
            return 'A branch with this name already exists for this company.';
        }
        if ($model->codeExists($data['code'], $excludeId)) {
            return 'A branch with this code already exists for this company.';
        }
        if ($data['email'] !== null && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return 'Branch email format is invalid.';
        }

        return null;
    }

    private function nullable(string $value): ?string
    {
        $value = trim($value);
        return $value === '' ? null : $value;
    }
}
