<?php

declare(strict_types=1);

/**
 * Handles authentication pages and session login flow.
 */

class AuthController extends Controller
{
    public function login(): void
    {
        if (!empty($_SESSION['pending_auth_user_id']) && $_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('auth/chooseCompany');
        }

        if (is_logged_in() && $_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('dashboard/index');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->attemptLogin();
            return;
        }

        $this->renderAuth('auth/login', [
            'csrf' => Session::csrfToken(),
            'flashError' => Session::flash('error'),
            'flashSuccess' => Session::flash('success'),
        ]);
    }

    public function logout(): void
    {
        unset($_SESSION['auth_user']);
        unset($_SESSION['pending_auth_user_id'], $_SESSION['company_memberships']);
        Tenant::clear();
        Session::regenerate();
        Session::flash('success', 'Logged out successfully.');
        redirect('auth/login');
    }

    public function chooseCompany(): void
    {
        $userId = (int) ($_SESSION['pending_auth_user_id'] ?? $_SESSION['auth_user']['id'] ?? 0);
        if ($userId <= 0) {
            redirect('auth/login');
        }

        $user = (new User())->find($userId);
        if (!$user || (int) ($user['is_active'] ?? 0) !== 1) {
            unset($_SESSION['pending_auth_user_id'], $_SESSION['auth_user'], $_SESSION['company_memberships']);
            Session::flash('error', 'Please sign in again.');
            redirect('auth/login');
        }

        $memberships = (new User())->activeMemberships($userId);
        if (count($memberships) === 1) {
            $this->establishCompanySession($user, $memberships[0]);
            if ($this->mustChangePassword()) {
                redirect('auth/forcePasswordChange');
            }
            redirect('dashboard/index');
        }

        $this->renderAuth('auth/choose-company', [
            'user' => $user,
            'memberships' => $memberships,
            'csrf' => Session::csrfToken(),
            'flashError' => Session::flash('error'),
        ]);
    }

    public function selectCompany(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('auth/chooseCompany');
        }

        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid request token.');
            redirect('auth/chooseCompany');
        }

        $userId = (int) ($_SESSION['pending_auth_user_id'] ?? $_SESSION['auth_user']['id'] ?? 0);
        $companyId = (int) $this->input('company_id', 0);

        if ($userId <= 0 || $companyId <= 0) {
            Session::flash('error', 'Please choose a company.');
            redirect('auth/chooseCompany');
        }

        $userModel = new User();
        $user = $userModel->find($userId);
        $membership = $userModel->membershipForCompany($userId, $companyId);

        if (!$user || !$membership) {
            Session::flash('error', 'You do not have access to that company.');
            redirect('auth/chooseCompany');
        }

        $this->establishCompanySession($user, $membership);
        Session::flash('success', 'Company switched to ' . (string) $membership['company_name'] . '.');
        if ($this->mustChangePassword()) {
            redirect('auth/forcePasswordChange');
        }
        redirect('dashboard/index');
    }

    public function forcePasswordChange(): void
    {
        if (!is_logged_in()) {
            redirect('auth/login');
        }

        if (!$this->mustChangePassword()) {
            redirect('dashboard/index');
        }

        $this->renderAuth('auth/force-password-change', [
            'csrf' => Session::csrfToken(),
            'flashError' => Session::flash('error'),
        ]);
    }

    public function forcePasswordChangeStore(): void
    {
        if (!is_logged_in()) {
            redirect('auth/login');
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('auth/forcePasswordChange');
        }

        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid request token.');
            redirect('auth/forcePasswordChange');
        }

        $password = (string) $this->input('password', '');
        $confirm = (string) $this->input('password_confirm', '');

        $passwordError = $this->passwordPolicyError($password);
        if ($passwordError !== null) {
            Session::flash('error', $passwordError);
            redirect('auth/forcePasswordChange');
        }

        if ($password !== $confirm) {
            Session::flash('error', 'Passwords do not match.');
            redirect('auth/forcePasswordChange');
        }

        $userId = (int) ($_SESSION['auth_user']['id'] ?? 0);
        db()->prepare('UPDATE users SET password_hash = :hash, must_change_password = 0 WHERE id = :id')
            ->execute(['hash' => password_hash($password, PASSWORD_DEFAULT), 'id' => $userId]);

        $_SESSION['auth_user']['must_change_password'] = 0;
        Session::flash('success', 'Password changed successfully.');
        redirect('dashboard/index');
    }

    public function forgotPassword(): void
    {
        if (is_logged_in()) { redirect('dashboard/index'); }
        $this->renderAuth('auth/forgot-password', [
            'csrf'         => Session::csrfToken(),
            'flashError'   => Session::flash('error'),
            'flashSuccess' => Session::flash('success'),
        ]);
    }

    public function forgotPasswordStore(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('auth/forgotPassword'); }
        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid request token.');
            redirect('auth/forgotPassword');
        }

        $email = trim((string) $this->input('email', ''));
        if ($email === '') {
            Session::flash('error', 'Email address is required.');
            redirect('auth/forgotPassword');
        }

        $userModel = new User();
        $user = $userModel->findActiveByEmail($email);

        if ($user) {
            $token   = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            $db      = db();
            $db->prepare('DELETE FROM password_reset_tokens WHERE user_id = :uid')->execute(['uid' => $user['id']]);
            $db->prepare('INSERT INTO password_reset_tokens (user_id, token, expires_at) VALUES (:uid, :token, :expires)')
               ->execute(['uid' => $user['id'], 'token' => $token, 'expires' => $expires]);

            $resetLink = public_url('auth/resetPassword/' . $token);
            try {
                $mail = (new ContractNotification())->buildMailer();
                $name = e((string) $user['full_name']);
                $link = e($resetLink);
                $mail->send(
                    (string) $user['email'],
                    (string) $user['full_name'],
                    'Password Reset Request - ' . app_product_name(),
                    "<p>Hello {$name},</p><p>Click the link below to reset your password. This link is valid for 1 hour.</p><p><a href=\"{$link}\">Reset password</a></p><p>If you did not request this, please ignore this email.</p>"
                );
            } catch (Throwable $ex) {
                error_log('Password reset email failed: ' . $ex->getMessage());
            }
        }

        Session::flash('success', 'If that email is registered you will receive a reset link shortly.');
        redirect('auth/forgotPassword');
    }

    public function resetPassword(string $token = ''): void
    {
        if (is_logged_in()) { redirect('dashboard/index'); }

        $stmt = db()->prepare('SELECT * FROM password_reset_tokens WHERE token = :t AND used_at IS NULL AND expires_at > NOW() LIMIT 1');
        $stmt->execute(['t' => $token]);
        if (!$stmt->fetch()) {
            Session::flash('error', 'This password reset link is invalid or has expired.');
            redirect('auth/forgotPassword');
        }

        $this->renderAuth('auth/reset-password', [
            'token'      => $token,
            'csrf'       => Session::csrfToken(),
            'flashError' => Session::flash('error'),
        ]);
    }

    public function resetPasswordStore(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('auth/login'); }
        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid request token.');
            redirect('auth/login');
        }

        $token    = trim((string) $this->input('token', ''));
        $password = (string) $this->input('password', '');
        $confirm  = (string) $this->input('password_confirm', '');

        $passwordError = $this->passwordPolicyError($password);
        if ($passwordError !== null) {
            Session::flash('error', $passwordError);
            redirect('auth/resetPassword/' . $token);
        }
        if ($password !== $confirm) {
            Session::flash('error', 'Passwords do not match.');
            redirect('auth/resetPassword/' . $token);
        }

        $db   = db();
        $stmt = $db->prepare('SELECT * FROM password_reset_tokens WHERE token = :t AND used_at IS NULL AND expires_at > NOW() LIMIT 1');
        $stmt->execute(['t' => $token]);
        $record = $stmt->fetch();

        if (!$record) {
            Session::flash('error', 'This password reset link is invalid or has expired.');
            redirect('auth/forgotPassword');
        }

        $db->prepare('UPDATE users SET password_hash = :h, must_change_password = 0 WHERE id = :id')->execute(['h' => password_hash($password, PASSWORD_DEFAULT), 'id' => $record['user_id']]);
        $db->prepare('UPDATE password_reset_tokens SET used_at = NOW() WHERE id = :id')->execute(['id' => $record['id']]);

        Session::flash('success', 'Password updated successfully. Please log in.');
        redirect('auth/login');
    }

    private function attemptLogin(): void
    {
        $token = (string) $this->input('_csrf', '');

        if (!Session::verifyCsrf($token)) {
            Session::flash('error', 'Invalid request token.');
            redirect('auth/login');
        }

        $email = trim((string) $this->input('email', ''));
        $password = (string) $this->input('password', '');
        $ipAddress = $this->clientIp();
        $limiter = new LoginAttempt();

        if ($email === '' || $password === '') {
            Session::flash('error', 'Email and password are required.');
            redirect('auth/login');
        }

        if ($limiter->isLocked('admin', strtolower($email), $ipAddress)) {
            $minutes = $limiter->remainingLockMinutes('admin', strtolower($email), $ipAddress);
            Session::flash('error', "Too many failed login attempts. Please try again in {$minutes} minute(s).");
            redirect('auth/login');
        }

        $userModel = new User();
        $user = $userModel->findActiveByEmail($email);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $limiter->record('admin', strtolower($email), false, $ipAddress);
            Session::flash('error', 'Invalid login credentials.');
            redirect('auth/login');
        }

        $limiter->record('admin', strtolower($email), true, $ipAddress);
        $limiter->clearFailures('admin', strtolower($email), $ipAddress);

        $userModel->updateLastLogin((int) $user['id']);

        $memberships = $userModel->activeMemberships((int) $user['id']);

        if ($memberships === []) {
            Session::flash('error', 'Your account is not linked to an active company.');
            redirect('auth/login');
        }

        if (count($memberships) > 1) {
            $_SESSION['pending_auth_user_id'] = (int) $user['id'];
            $_SESSION['company_memberships'] = $memberships;
            redirect('auth/chooseCompany');
        }

        $this->establishCompanySession($user, $memberships[0]);

        Session::flash('success', 'Login successful.');

        if ($this->mustChangePassword()) {
            redirect('auth/forcePasswordChange');
        }

        redirect('dashboard/index');
    }

    private function establishCompanySession(array $user, array $membership): void
    {
        $companyId = (int) $membership['company_id'];
        $compStmt = db()->prepare("SELECT * FROM companies WHERE id = :id AND is_active = 1 LIMIT 1");
        $compStmt->execute(['id' => $companyId]);
        $company = $compStmt->fetch();

        if (!$company) {
            throw new RuntimeException('Selected company is not available.');
        }

        Tenant::set($company);
        Session::regenerate();

        $_SESSION['auth_user'] = [
            'id'         => (int) $user['id'],
            'name'       => (string) $user['full_name'],
            'email'      => (string) $user['email'],
            'role'       => (string) ($membership['role_name'] ?? 'Viewer'),
            'role_id'    => (int) ($membership['role_id'] ?? 0),
            'access_level' => (string) ($membership['access_level'] ?? $membership['role_name'] ?? 'Viewer'),
            'company_id' => $companyId,
            'must_change_password' => (int) ($user['must_change_password'] ?? 0),
        ];
        $_SESSION['company_memberships'] = (new User())->activeMemberships((int) $user['id']);
        unset($_SESSION['pending_auth_user_id']);
    }

    private function mustChangePassword(): bool
    {
        return !empty($_SESSION['auth_user']['must_change_password']);
    }

    private function passwordPolicyError(string $password): ?string
    {
        if (strlen($password) < 10) {
            return 'Password must be at least 10 characters.';
        }

        if (!preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/\d/', $password)) {
            return 'Password must include uppercase, lowercase, and a number.';
        }

        return null;
    }

    private function clientIp(): string
    {
        $forwarded = trim((string) ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? ''));
        if ($forwarded !== '') {
            return trim(explode(',', $forwarded)[0]);
        }

        return (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    }
}
