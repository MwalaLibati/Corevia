<?php

declare(strict_types=1);

class SuperadminPlatformAdminController extends Controller
{
    public function index(): void
    {
        require_superadmin();

        $admins = db()->query(
            'SELECT id, full_name, email, is_active, last_login_at, created_at
             FROM platform_admins
             ORDER BY full_name ASC'
        )->fetchAll();

        $this->renderSuperAdmin('superadmin/platform_admins/index', [
            'title' => 'Platform Admins',
            'admins' => $admins,
            'csrf' => Session::csrfToken(),
            'flash' => Session::flash('success'),
            'flashErr' => Session::flash('error'),
        ]);
    }

    public function create(): void
    {
        require_superadmin();

        $this->renderSuperAdmin('superadmin/platform_admins/create', [
            'title' => 'Create Platform Admin',
            'csrf' => Session::csrfToken(),
            'flash' => Session::flash('error'),
        ]);
    }

    public function store(): void
    {
        require_superadmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('superadmin/platform-admin/index'); }
        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid token.');
            redirect('superadmin/platform-admin/create');
        }

        $name = trim((string) $this->input('full_name', ''));
        $email = strtolower(trim((string) $this->input('email', '')));
        $password = (string) $this->input('password', '');

        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8) {
            Session::flash('error', 'Name, valid email, and password of at least 8 characters are required.');
            redirect('superadmin/platform-admin/create');
        }

        try {
            $stmt = db()->prepare(
                'INSERT INTO platform_admins (full_name, email, password_hash, is_active)
                 VALUES (:name, :email, :hash, 1)'
            );
            $stmt->execute([
                'name' => $name,
                'email' => $email,
                'hash' => password_hash($password, PASSWORD_DEFAULT),
            ]);
            $id = (int) db()->lastInsertId();
            AuditLog::recordPlatform('created', 'Created platform admin ' . $email, 'PlatformAdmin', $id);
            Session::flash('success', 'Platform admin created.');
        } catch (Throwable $e) {
            Session::flash('error', 'Platform admin could not be created: ' . $e->getMessage());
            redirect('superadmin/platform-admin/create');
        }

        redirect('superadmin/platform-admin/index');
    }

    public function edit(string $id = ''): void
    {
        require_superadmin();
        $admin = $this->findOrFail((int) $id);

        $this->renderSuperAdmin('superadmin/platform_admins/edit', [
            'title' => 'Edit Platform Admin',
            'admin' => $admin,
            'csrf' => Session::csrfToken(),
            'flash' => Session::flash('error'),
            'success' => Session::flash('success'),
        ]);
    }

    public function update(): void
    {
        require_superadmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('superadmin/platform-admin/index'); }
        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid token.');
            redirect('superadmin/platform-admin/index');
        }

        $id = (int) $this->input('id', 0);
        $admin = $this->findOrFail($id);
        $name = trim((string) $this->input('full_name', ''));
        $email = strtolower(trim((string) $this->input('email', '')));
        $password = (string) $this->input('password', '');
        $isActive = (int) $this->input('is_active', 0) === 1 ? 1 : 0;

        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Session::flash('error', 'Name and valid email are required.');
            redirect('superadmin/platform-admin/edit/' . $id);
        }

        if ($password !== '' && strlen($password) < 8) {
            Session::flash('error', 'New password must be at least 8 characters.');
            redirect('superadmin/platform-admin/edit/' . $id);
        }

        $fields = 'full_name = :name, email = :email, is_active = :active';
        $params = ['name' => $name, 'email' => $email, 'active' => $isActive, 'id' => $id];
        if ($password !== '') {
            $fields .= ', password_hash = :hash';
            $params['hash'] = password_hash($password, PASSWORD_DEFAULT);
        }

        try {
            db()->prepare("UPDATE platform_admins SET {$fields} WHERE id = :id")->execute($params);
            AuditLog::recordPlatform('updated', 'Updated platform admin ' . (string) $admin['email'], 'PlatformAdmin', $id);
            Session::flash('success', 'Platform admin updated.');
        } catch (Throwable $e) {
            Session::flash('error', 'Platform admin could not be updated: ' . $e->getMessage());
            redirect('superadmin/platform-admin/edit/' . $id);
        }

        redirect('superadmin/platform-admin/index');
    }

    private function findOrFail(int $id): array
    {
        $stmt = db()->prepare('SELECT * FROM platform_admins WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        if (!$row) { http_response_code(404); exit('Platform admin not found.'); }

        return $row;
    }
}
