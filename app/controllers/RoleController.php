<?php

declare(strict_types=1);

class RoleController extends Controller
{
    public function index(): void
    {
        require_auth();
        require_role(['Super Admin']);

        $this->render('roles/index', [
            'title' => 'Roles',
            'roles' => (new Role())->companyRoles(),
            'csrf' => Session::csrfToken(),
            'flashSuccess' => Session::flash('success'),
            'flashError' => Session::flash('error'),
        ]);
    }

    public function create(): void
    {
        require_auth();
        require_role(['Super Admin']);

        $this->render('roles/create', [
            'title' => 'Create Role',
            'role' => null,
            'accessLevels' => Role::ACCESS_LEVELS,
            'modules' => module_catalog(),
            'selectedModules' => $_SESSION['_old_role_modules'] ?? array_keys(module_catalog()),
            'old' => $_SESSION['_old_role_input'] ?? [],
            'csrf' => Session::csrfToken(),
            'flashError' => Session::flash('error'),
        ]);

        unset($_SESSION['_old_role_input']);
        unset($_SESSION['_old_role_modules']);
    }

    public function store(): void
    {
        require_auth();
        require_role(['Super Admin']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('role/index');
        }
        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid request token.');
            redirect('role/create');
        }

        $data = $this->collectInput();
        $moduleKeys = $this->collectModuleKeys();
        $_SESSION['_old_role_input'] = $data;
        $_SESSION['_old_role_modules'] = $moduleKeys;
        $model = new Role();
        $error = $this->validate($model, $data);
        if ($error !== null) {
            Session::flash('error', $error);
            redirect('role/create');
        }

        try {
            $roleId = $model->insert($data);
            $model->syncModulePermissions($roleId, $moduleKeys);
            unset($_SESSION['_old_role_input']);
            unset($_SESSION['_old_role_modules']);
            Session::flash('success', 'Role created successfully.');
            redirect('role/index');
        } catch (Throwable $e) {
            Session::flash('error', 'Failed to create role: ' . $e->getMessage());
            redirect('role/create');
        }
    }

    public function edit(string $id): void
    {
        require_auth();
        require_role(['Super Admin']);

        $model = new Role();
        $role = $model->findCompanyRole((int) $id);
        if (!$role) {
            Session::flash('error', 'Role not found.');
            redirect('role/index');
        }

        $this->render('roles/create', [
            'title' => 'Edit Role',
            'role' => $role,
            'accessLevels' => Role::ACCESS_LEVELS,
            'modules' => module_catalog(),
            'selectedModules' => $_SESSION['_old_role_modules'] ?? $model->modulePermissions((int) $role['id']),
            'old' => $_SESSION['_old_role_input'] ?? $role,
            'csrf' => Session::csrfToken(),
            'flashError' => Session::flash('error'),
        ]);

        unset($_SESSION['_old_role_input']);
        unset($_SESSION['_old_role_modules']);
    }

    public function update(string $id): void
    {
        require_auth();
        require_role(['Super Admin']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('role/index');
        }
        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid request token.');
            redirect('role/edit/' . (int) $id);
        }

        $roleId = (int) $id;
        $model = new Role();
        if (!$model->findCompanyRole($roleId)) {
            Session::flash('error', 'Role not found.');
            redirect('role/index');
        }

        $data = $this->collectInput();
        $moduleKeys = $this->collectModuleKeys();
        $_SESSION['_old_role_input'] = $data;
        $_SESSION['_old_role_modules'] = $moduleKeys;
        $error = $this->validate($model, $data, $roleId);
        if ($error !== null) {
            Session::flash('error', $error);
            redirect('role/edit/' . $roleId);
        }

        try {
            $model->update($roleId, $data);
            $model->syncModulePermissions($roleId, $moduleKeys);
            unset($_SESSION['_old_role_input']);
            unset($_SESSION['_old_role_modules']);
            Session::flash('success', 'Role updated successfully.');
        } catch (Throwable $e) {
            Session::flash('error', 'Failed to update role: ' . $e->getMessage());
        }

        redirect('role/index');
    }

    public function delete(string $id): void
    {
        require_auth();
        require_role(['Super Admin']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('role/index');
        }
        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid request token.');
            redirect('role/index');
        }

        try {
            (new Role())->delete((int) $id);
            Session::flash('success', 'Role deleted successfully.');
        } catch (Throwable) {
            Session::flash('error', 'Role could not be deleted because it is assigned to users.');
        }

        redirect('role/index');
    }

    private function collectInput(): array
    {
        return [
            'name' => trim((string) $this->input('name', '')),
            'description' => trim((string) $this->input('description', '')),
            'access_level' => (string) $this->input('access_level', 'Viewer'),
        ];
    }

    private function collectModuleKeys(): array
    {
        $modules = $_POST['modules'] ?? [];
        if (!is_array($modules)) {
            return [];
        }

        return array_values(array_map('strval', $modules));
    }

    private function validate(Role $model, array $data, ?int $excludeId = null): ?string
    {
        if ($data['name'] === '') {
            return 'Role name is required.';
        }
        if (!in_array($data['access_level'], Role::ACCESS_LEVELS, true)) {
            return 'Invalid access profile selected.';
        }
        if ($model->nameExists($data['name'], $excludeId)) {
            return 'A role with this name already exists for this company.';
        }

        return null;
    }
}
