<?php

declare(strict_types=1);

class SuperadminAuthController extends Controller
{
    public function login(): void
    {
        if (is_superadmin_logged_in()) {
            redirect('superadmin/dashboard/index');
        }

        $this->renderAuth('superadmin/login', [
            'csrf'         => Session::csrfToken(),
            'flashError'   => Session::flash('error'),
            'flashSuccess' => Session::flash('success'),
        ]);
    }

    public function loginStore(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('superadmin/auth/login');
        }

        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid request token.');
            redirect('superadmin/auth/login');
        }

        $email    = trim((string) $this->input('email', ''));
        $password = (string) $this->input('password', '');
        $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown');

        if ($email === '' || $password === '') {
            Session::flash('error', 'Email and password are required.');
            redirect('superadmin/auth/login');
        }

        if ($this->isLockedOut($email, $ip)) {
            AuditLog::recordPlatform('login_locked', 'Platform admin login temporarily locked for ' . $email, 'PlatformAdmin');
            Session::flash('error', 'Too many failed login attempts. Please wait 15 minutes and try again.');
            redirect('superadmin/auth/login');
        }

        $stmt = db()->prepare(
            "SELECT * FROM platform_admins WHERE email = :email AND is_active = 1 LIMIT 1"
        );
        $stmt->execute(['email' => $email]);
        $admin = $stmt->fetch();

        if (!$admin || !password_verify($password, (string) $admin['password_hash'])) {
            $this->recordLoginAttempt($email, $ip, false);
            AuditLog::recordPlatform('login_failed', 'Failed platform admin login for ' . $email, 'PlatformAdmin');
            Session::flash('error', 'Invalid credentials.');
            redirect('superadmin/auth/login');
        }

        $this->recordLoginAttempt($email, $ip, true);
        $this->clearFailedAttempts($email, $ip);

        db()->prepare("UPDATE platform_admins SET last_login_at = NOW() WHERE id = :id")
            ->execute(['id' => $admin['id']]);

        Session::regenerate();

        $_SESSION['superadmin_user'] = [
            'id'        => (int) $admin['id'],
            'full_name' => (string) $admin['full_name'],
            'email'     => (string) $admin['email'],
        ];

        AuditLog::recordPlatform('login_success', 'Platform admin logged in.', 'PlatformAdmin', (int) $admin['id']);
        redirect('superadmin/dashboard/index');
    }

    public function logout(): void
    {
        unset($_SESSION['superadmin_user']);
        Session::regenerate();
        Session::flash('success', 'Logged out.');
        redirect('superadmin/auth/login');
    }

    private function isLockedOut(string $email, string $ip): bool
    {
        $stmt = db()->prepare(
            "SELECT COUNT(*) FROM platform_login_attempts
             WHERE email = :email AND ip_address = :ip AND success = 0
               AND attempted_at >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)"
        );
        $stmt->execute(['email' => strtolower($email), 'ip' => $ip]);

        return (int) $stmt->fetchColumn() >= 5;
    }

    private function recordLoginAttempt(string $email, string $ip, bool $success): void
    {
        try {
            db()->prepare(
                'INSERT INTO platform_login_attempts (email, ip_address, success) VALUES (:email, :ip, :success)'
            )->execute(['email' => strtolower($email), 'ip' => $ip, 'success' => $success ? 1 : 0]);
        } catch (Throwable) {}
    }

    private function clearFailedAttempts(string $email, string $ip): void
    {
        try {
            db()->prepare(
                'DELETE FROM platform_login_attempts WHERE email = :email AND ip_address = :ip AND success = 0'
            )->execute(['email' => strtolower($email), 'ip' => $ip]);
        } catch (Throwable) {}
    }
}
