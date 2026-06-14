<?php

declare(strict_types=1);

class AffiliateAuthController extends Controller
{
    public function login(): void
    {
        if (is_affiliate_logged_in()) {
            redirect('affiliate/dashboard/index');
        }

        $this->renderAuth('affiliate/login', [
            'csrf' => Session::csrfToken(),
            'flashErr' => Session::flash('error'),
            'flash' => Session::flash('success'),
        ]);
    }

    public function loginStore(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('affiliate/auth/login'); }
        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid token.');
            redirect('affiliate/auth/login');
        }

        $email = strtolower(trim((string) $this->input('email', '')));
        $password = (string) $this->input('password', '');
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $limiter = new LoginAttempt();
        if ($limiter->isLocked('affiliate', $email, $ip, 5, 15)) {
            Session::flash('error', 'Too many failed attempts. Try again in ' . $limiter->remainingLockMinutes('affiliate', $email, $ip, 15) . ' minute(s).');
            redirect('affiliate/auth/login');
        }

        $ops = new AffiliateOperations();
        $ops->ensureSchema();
        $affiliate = (new Affiliate())->findByEmail($email);
        if (!$affiliate || (int) ($affiliate['is_active'] ?? 0) !== 1 || !password_verify($password, (string) ($affiliate['password_hash'] ?? ''))) {
            $limiter->record('affiliate', $email, false, $ip);
            $ops->logAffiliateLogin($affiliate ? (int) $affiliate['id'] : null, $email, false);
            Session::flash('error', 'Invalid affiliate email or password.');
            redirect('affiliate/auth/login');
        }

        $limiter->record('affiliate', $email, true, $ip);
        $limiter->clearFailures('affiliate', $email, $ip);
        $ops->logAffiliateLogin((int) $affiliate['id'], $email, true);
        Session::regenerate();
        $_SESSION['affiliate_user'] = [
            'id' => (int) $affiliate['id'],
            'full_name' => (string) $affiliate['full_name'],
            'email' => (string) $affiliate['email'],
            'affiliate_code' => (string) $affiliate['affiliate_code'],
            'must_change_password' => (int) ($affiliate['must_change_password'] ?? 0),
        ];
        db()->prepare('UPDATE affiliates SET last_login_at = NOW() WHERE id = :id')->execute(['id' => (int) $affiliate['id']]);

        if (!empty($_SESSION['affiliate_user']['must_change_password'])) {
            redirect('affiliate/auth/changePassword');
        }

        redirect('affiliate/dashboard/index');
    }

    public function changePassword(): void
    {
        require_affiliate_auth();

        $this->renderAuth('affiliate/change-password', [
            'csrf' => Session::csrfToken(),
            'flashErr' => Session::flash('error'),
            'affiliate' => current_affiliate(),
        ]);
    }

    public function changePasswordStore(): void
    {
        require_affiliate_auth();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('affiliate/auth/changePassword'); }
        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid token.');
            redirect('affiliate/auth/changePassword');
        }

        $password = (string) $this->input('password', '');
        $confirm = (string) $this->input('password_confirmation', '');
        if ($password !== $confirm) {
            Session::flash('error', 'Passwords do not match.');
            redirect('affiliate/auth/changePassword');
        }

        $policyError = $this->passwordPolicyError($password);
        if ($policyError !== null) {
            Session::flash('error', $policyError);
            redirect('affiliate/auth/changePassword');
        }

        $affiliateId = (int) (current_affiliate()['id'] ?? 0);
        db()->prepare('UPDATE affiliates SET password_hash = :hash, must_change_password = 0 WHERE id = :id')
            ->execute(['hash' => password_hash($password, PASSWORD_DEFAULT), 'id' => $affiliateId]);
        $_SESSION['affiliate_user']['must_change_password'] = 0;
        Session::flash('success', 'Password changed successfully.');
        redirect('affiliate/dashboard/index');
    }

    public function logout(): void
    {
        unset($_SESSION['affiliate_user']);
        Session::flash('success', 'You have been logged out.');
        redirect('affiliate/auth/login');
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
}
