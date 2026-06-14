<?php

declare(strict_types=1);

/**
 * User management and role assignment scaffold controller.
 */

class UserManagementController extends Controller
{
    public function index(): void
    {
        require_auth();
        require_role(['Super Admin']);

        $model = new User();
        $users = $model->listWithRoles();

        $this->render('users/index', [
            'title' => 'User Management',
            'users' => $users,
            'csrf' => Session::csrfToken(),
            'flashSuccess' => Session::flash('success'),
            'flashError' => Session::flash('error'),
        ]);
    }

    public function create(): void
    {
        require_auth();
        require_role(['Super Admin']);

        $model = new User();
        $this->render('users/create', [
            'title' => 'Create User',
            'user' => null,
            'roles' => $model->companyRoles(),
            'old' => $_SESSION['_old_user_input'] ?? [],
            'csrf' => Session::csrfToken(),
            'flashError' => Session::flash('error'),
        ]);

        unset($_SESSION['_old_user_input']);
    }

    public function store(): void
    {
        require_auth();
        require_role(['Super Admin']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('user-management/index');
        }
        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid request token.');
            redirect('user-management/create');
        }

        $data = $this->collectInput(true);
        $_SESSION['_old_user_input'] = $data;
        $roleId = (int) $this->input('role_id', 0);
        $password = (string) $this->input('password', '');

        $model = new User();
        $error = $this->validate($model, $data, $roleId, $password, true);
        if ($error !== null) {
            Session::flash('error', $error);
            redirect('user-management/create');
        }

        try {
            $model->createWithRole([
                'full_name' => $data['full_name'],
                'email' => $data['email'],
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'is_active' => $data['is_active'],
            ], $roleId);
            unset($_SESSION['_old_user_input']);
            Session::flash('success', 'User created and linked to this company.');
            redirect('user-management/index');
        } catch (Throwable $e) {
            Session::flash('error', 'Failed to create user: ' . $e->getMessage());
            redirect('user-management/create');
        }
    }

    public function edit(string $id): void
    {
        require_auth();
        require_role(['Super Admin']);

        $model = new User();
        $user = $model->findCompanyUser((int) $id);
        if (!$user) {
            Session::flash('error', 'User not found for this company.');
            redirect('user-management/index');
        }

        $this->render('users/create', [
            'title' => 'Edit User',
            'user' => $user,
            'roles' => $model->companyRoles(),
            'old' => $_SESSION['_old_user_input'] ?? $user,
            'csrf' => Session::csrfToken(),
            'flashError' => Session::flash('error'),
        ]);

        unset($_SESSION['_old_user_input']);
    }

    public function update(string $id): void
    {
        require_auth();
        require_role(['Super Admin']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('user-management/index');
        }
        $userId = (int) $id;
        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid request token.');
            redirect('user-management/edit/' . $userId);
        }

        $model = new User();
        if (!$model->findCompanyUser($userId)) {
            Session::flash('error', 'User not found for this company.');
            redirect('user-management/index');
        }

        $data = $this->collectInput(false);
        $_SESSION['_old_user_input'] = $data;
        $roleId = (int) $this->input('role_id', 0);
        $password = (string) $this->input('password', '');
        $error = $this->validate($model, $data, $roleId, $password, false, $userId);
        if ($error !== null) {
            Session::flash('error', $error);
            redirect('user-management/edit/' . $userId);
        }

        $updateData = [
            'full_name' => $data['full_name'],
            'email' => $data['email'],
            'is_active' => $data['is_active'],
        ];
        if (trim($password) !== '') {
            $updateData['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
        }

        try {
            $model->updateCompanyUser($userId, $updateData, $roleId, (int) $this->input('membership_active', 1) === 1);
            unset($_SESSION['_old_user_input']);
            Session::flash('success', 'User updated successfully.');
            redirect('user-management/index');
        } catch (Throwable $e) {
            Session::flash('error', 'Failed to update user: ' . $e->getMessage());
            redirect('user-management/edit/' . $userId);
        }
    }

    private function collectInput(bool $creating): array
    {
        return [
            'full_name' => trim((string) $this->input('full_name', '')),
            'email' => strtolower(trim((string) $this->input('email', ''))),
            'role_id' => (int) $this->input('role_id', 0),
            'is_active' => (int) $this->input('is_active', 0) === 1 ? 1 : 0,
            'membership_active' => $creating ? 1 : ((int) $this->input('membership_active', 0) === 1 ? 1 : 0),
        ];
    }

    private function validate(User $model, array $data, int $roleId, string $password, bool $creating, ?int $excludeId = null): ?string
    {
        if ($data['full_name'] === '') {
            return 'Full name is required.';
        }
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return 'A valid email address is required.';
        }
        if ($model->emailExists($data['email'], $excludeId)) {
            return 'A user with this email already exists.';
        }
        if ($roleId <= 0) {
            return 'Role is required.';
        }
        if ($creating && strlen($password) < 8) {
            return 'Password must be at least 8 characters.';
        }
        if (!$creating && trim($password) !== '' && strlen($password) < 8) {
            return 'New password must be at least 8 characters.';
        }

        return null;
    }
}
