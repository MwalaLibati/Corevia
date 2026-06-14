<?php

declare(strict_types=1);

class SuperadminCompanyController extends Controller
{
    public function index(): void
    {
        require_superadmin();
        new ClientEntity();
        new Branch();
        $this->ensureDeletionSchema();
        $companies = db()->query(
            "SELECT c.*,
                    ce.name AS client_entity_name,
                    (SELECT COUNT(*) FROM employees e WHERE e.company_id = c.id) AS employee_count,
                    (SELECT COUNT(*) FROM branches b WHERE b.company_id = c.id) AS branch_count,
                    (SELECT COUNT(*) FROM company_user_memberships m WHERE m.company_id = c.id AND m.is_active = 1) AS user_count,
                    s.plan AS sub_plan, s.ends_at AS sub_ends_at, s.status AS sub_status
             FROM companies c
             LEFT JOIN client_entities ce ON ce.id = c.client_entity_id
             LEFT JOIN subscriptions s ON s.company_id = c.id AND s.status = 'Active'
             WHERE c.deleted_at IS NULL
             ORDER BY c.name ASC"
        )->fetchAll();

        $this->renderSuperAdmin('superadmin/companies/index', [
            'title'     => 'Manage Companies',
            'companies' => $companies,
            'csrf'      => Session::csrfToken(),
            'flash'     => Session::flash('success'),
            'flashErr'  => Session::flash('error'),
        ]);
    }

    public function create(): void
    {
        require_superadmin();
        $this->renderSuperAdmin('superadmin/companies/create', [
            'title' => 'Create Company',
            'csrf'  => Session::csrfToken(),
            'flash' => Session::flash('error'),
            'old'   => $this->consumeCreateOldInput(),
            'plans' => $this->activeSubscriptionPlans(),
            'clientEntities' => (new ClientEntity())->activeOptions(),
            'selectedClientEntityId' => (int) $this->input('client_entity_id', 0),
        ]);
    }

    public function store(): void
    {
        require_superadmin();
        $this->ensureDeletionSchema();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('superadmin/company/index'); }
        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid token.');
            redirect('superadmin/company/create');
        }

        $name  = trim((string) $this->input('name', ''));
        $slug  = strtolower(trim((string) $this->input('slug', '')));
        $email = trim((string) $this->input('email', ''));
        $phone = trim((string) $this->input('phone', ''));
        $plan  = (string) $this->input('subscription_plan', 'Trial');
        $billingModel = (string) $this->input('billing_model', 'per_user');
        $billingCycle = (string) $this->input('billing_cycle', 'Annual');
        $monthlyRateInput = trim((string) $this->input('monthly_rate', ''));
        $adminName = trim((string) $this->input('admin_full_name', ''));
        $adminEmail = strtolower(trim((string) $this->input('admin_email', '')));
        $oneTimePassword = trim((string) $this->input('one_time_password', ''));
        $clientEntityId = (int) $this->input('client_entity_id', 0);
        $newClientEntityName = trim((string) $this->input('new_client_entity_name', ''));

        $oldInput = [
            'name' => $name,
            'slug' => $slug,
            'email' => $email,
            'phone' => $phone,
            'subscription_plan' => $plan,
            'billing_model' => $billingModel,
            'billing_cycle' => $billingCycle,
            'monthly_rate' => $monthlyRateInput,
            'admin_full_name' => $adminName,
            'admin_email' => $adminEmail,
            'client_entity_id' => $clientEntityId,
            'new_client_entity_name' => $newClientEntityName,
        ];

        if ($name === '' || $slug === '' || $adminName === '' || $adminEmail === '') {
            $this->flashCreateOldInput($oldInput);
            Session::flash('error', 'Company name, slug, admin name, and admin email are required.');
            redirect('superadmin/company/create');
        }

        if ($oneTimePassword !== '' && !$this->isStrongTemporaryPassword($oneTimePassword)) {
            $this->flashCreateOldInput($oldInput);
            Session::flash('error', 'One-time password must be at least 10 characters and include uppercase, lowercase, a number, and a special character.');
            redirect('superadmin/company/create');
        }

        $slug = preg_replace('/[^a-z0-9\-]/', '', $slug);
        $oldInput['slug'] = $slug;

        if ($slug === '') {
            $this->flashCreateOldInput($oldInput);
            Session::flash('error', 'Please enter a valid company slug.');
            redirect('superadmin/company/create');
        }

        if (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
            $this->flashCreateOldInput($oldInput);
            Session::flash('error', 'Please enter a valid admin email address.');
            redirect('superadmin/company/create');
        }

        $exists = db()->prepare("SELECT id FROM companies WHERE slug = :s AND deleted_at IS NULL LIMIT 1");
        $exists->execute(['s' => $slug]);
        if ($exists->fetch()) {
            $this->flashCreateOldInput($oldInput);
            Session::flash('error', 'That slug is already taken. Please choose a different one.');
            redirect('superadmin/company/create');
        }

        $userExists = db()->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $userExists->execute(['email' => $adminEmail]);
        if ($userExists->fetch()) {
            $this->flashCreateOldInput($oldInput);
            Session::flash('error', 'A user with that admin email already exists. Use a new email, or grant the existing user access after creating the company.');
            redirect('superadmin/company/create');
        }

        $planRow = $this->findActiveSubscriptionPlan($plan);
        if (!$planRow) {
            $this->flashCreateOldInput($oldInput);
            Session::flash('error', 'Please select a valid active subscription plan.');
            redirect('superadmin/company/create');
        }

        $billingModel = $billingModel === 'flat' ? 'flat' : 'per_user';
        $billingCycle = $billingCycle === 'Monthly' ? 'Monthly' : 'Annual';
        $monthlyRate = $monthlyRateInput !== '' ? $this->money($monthlyRateInput) : (float) $planRow['default_monthly_rate'];

        $db = db();
        $tempPassword = $oneTimePassword !== '' ? $oneTimePassword : $this->generateTemporaryPassword();

        try {
            $clientEntityId = $this->resolveClientEntityId($clientEntityId, $newClientEntityName, $name, $email, $phone);

            $db->beginTransaction();

            $stmt = $db->prepare(
                "INSERT INTO companies (client_entity_id, name, slug, email, phone, subscription_plan, is_active)
                 VALUES (:client_entity_id, :name, :slug, :email, :phone, :plan, 1)"
            );
            $stmt->execute(['client_entity_id' => $clientEntityId, 'name' => $name, 'slug' => $slug, 'email' => $email, 'phone' => $phone, 'plan' => $plan]);
            $companyId = (int) $db->lastInsertId();

            $db->prepare(
                "INSERT INTO subscriptions (company_id, plan, billing_model, price, employee_count, monthly_rate, currency, billing_cycle, starts_at, ends_at, status, notes)
                 VALUES (:cid, :plan, :billing_model, 0, 0, :rate, :currency, :cycle, CURDATE(), :end, 'Active', :notes)"
            )->execute([
                'cid' => $companyId,
                'plan' => $plan,
                'billing_model' => $billingModel,
                'rate' => $monthlyRate,
                'currency' => (string) $planRow['currency'],
                'cycle' => $billingCycle,
                'end' => date('Y-m-d', strtotime($billingCycle === 'Monthly' ? '+1 month' : '+1 year')),
                'notes' => $monthlyRateInput !== '' ? 'Negotiated onboarding rate.' : null,
            ]);

            $roleId = $this->ensureCompanySuperAdminRole($companyId);

            $userStmt = $db->prepare(
                'INSERT INTO users (company_id, full_name, email, password_hash, must_change_password, is_active)
                 VALUES (:company_id, :full_name, :email, :password_hash, 1, 1)'
            );
            $userStmt->execute([
                'company_id' => $companyId,
                'full_name' => $adminName,
                'email' => $adminEmail,
                'password_hash' => password_hash($tempPassword, PASSWORD_DEFAULT),
            ]);
            $userId = (int) $db->lastInsertId();

            $db->prepare('INSERT INTO user_roles (user_id, role_id) VALUES (:uid, :rid)')
                ->execute(['uid' => $userId, 'rid' => $roleId]);

            $db->prepare(
                'INSERT INTO company_user_memberships (company_id, user_id, role_id, is_default, is_active)
                 VALUES (:cid, :uid, :rid, 1, 1)'
            )->execute(['cid' => $companyId, 'uid' => $userId, 'rid' => $roleId]);

            if ($db->inTransaction()) {
                $db->commit();
            }
            AuditLog::recordPlatform('created', 'Created company ' . $name, 'Company', $companyId);
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $this->flashCreateOldInput($oldInput);
            Session::flash('error', 'Company could not be created: ' . $e->getMessage());
            redirect('superadmin/company/create');
        }

        $emailResult = $this->sendCompanyWelcomeEmail($name, $adminName, $adminEmail, $tempPassword);

        $_SESSION['_new_company_admin_password'] = [
            'company_id' => $companyId,
            'email' => $adminEmail,
            'password' => $tempPassword,
            'email_sent' => $emailResult['sent'],
            'email_error' => $emailResult['error'],
        ];

        $message = "Company '{$name}' created successfully. The admin must change the one-time password after signing in.";
        if ($emailResult['sent']) {
            $message .= ' Login instructions were emailed to ' . $adminEmail . '.';
        } else {
            $message .= ' The welcome email could not be sent, so share the password securely.';
            if ($emailResult['error'] !== '') {
                $message .= ' Mail error: ' . $emailResult['error'];
            }
        }
        Session::flash('success', $message);
        redirect('superadmin/company/edit/' . $companyId);
    }

    public function edit(string $id = ''): void
    {
        require_superadmin();
        $this->ensureDeletionSchema();
        $company = $this->findOrFail((int) $id);
        $roleStmt = db()->prepare('SELECT id, name FROM roles WHERE company_id IS NULL OR company_id = :cid ORDER BY name ASC');
        $roleStmt->execute(['cid' => (int) $id]);

        $this->renderSuperAdmin('superadmin/companies/edit', [
            'title'   => 'Edit Company',
            'company' => $company,
            'roles'   => $roleStmt->fetchAll(),
            'memberships' => $this->companyMemberships((int) $id),
            'csrf'    => Session::csrfToken(),
            'flash'   => Session::flash('error'),
            'success' => Session::flash('success'),
            'newAdminPassword' => $this->consumeNewAdminPassword((int) $id),
            'plans' => $this->activeSubscriptionPlans(),
            'clientEntities' => (new ClientEntity())->activeOptions(),
        ]);
    }

    public function update(): void
    {
        require_superadmin();
        $this->ensureDeletionSchema();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('superadmin/company/index'); }
        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid token.');
            redirect('superadmin/company/index');
        }

        $id    = (int) $this->input('id', 0);
        $name  = trim((string) $this->input('name', ''));
        $email = trim((string) $this->input('email', ''));
        $phone = trim((string) $this->input('phone', ''));
        $plan  = (string) $this->input('subscription_plan', 'Trial');
        $addr  = trim((string) $this->input('address', ''));
        $clientEntityId = (int) $this->input('client_entity_id', 0);
        $newClientEntityName = trim((string) $this->input('new_client_entity_name', ''));
        $company = $this->findOrFail($id);
        $logoPath = (string) ($company['logo_path'] ?? '');

        try {
            $clientEntityId = $this->resolveClientEntityId($clientEntityId, $newClientEntityName, $name, $email, $phone);
            if (isset($_FILES['company_logo']) && ($_FILES['company_logo']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                $logoPath = $this->storeCompanyLogo($id, $_FILES['company_logo']);
                $this->deleteStoredLogo((string) ($company['logo_path'] ?? ''));
            }

            db()->prepare(
                "UPDATE companies SET client_entity_id=:client_entity_id, name=:name, email=:email, phone=:phone, subscription_plan=:plan, address=:addr, logo_path=:logo WHERE id=:id"
            )->execute(['client_entity_id' => $clientEntityId, 'name' => $name, 'email' => $email, 'phone' => $phone, 'plan' => $plan, 'addr' => $addr, 'logo' => $logoPath ?: null, 'id' => $id]);

            AuditLog::recordPlatform('updated', 'Updated company ' . $name, 'Company', $id);
            Session::flash('success', 'Company updated.');
        } catch (Throwable $e) {
            Session::flash('error', $e->getMessage());
            redirect('superadmin/company/edit/' . $id);
        }
        redirect('superadmin/company/index');
    }

    public function grantAccess(string $id = ''): void
    {
        require_superadmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('superadmin/company/index'); }
        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid token.');
            redirect('superadmin/company/edit/' . (int) $id);
        }

        $company = $this->findOrFail((int) $id);
        $email = trim((string) $this->input('email', ''));
        $roleId = (int) $this->input('role_id', 0);

        if ($email === '' || $roleId <= 0) {
            Session::flash('error', 'User email and role are required.');
            redirect('superadmin/company/edit/' . (int) $company['id']);
        }

        $userStmt = db()->prepare('SELECT * FROM users WHERE email = :email AND is_active = 1 LIMIT 1');
        $userStmt->execute(['email' => $email]);
        $user = $userStmt->fetch();

        if (!$user) {
            Session::flash('error', 'No active user account exists with that email.');
            redirect('superadmin/company/edit/' . (int) $company['id']);
        }

        $roleStmt = db()->prepare('SELECT id FROM roles WHERE id = :id AND (company_id IS NULL OR company_id = :cid) LIMIT 1');
        $roleStmt->execute(['id' => $roleId, 'cid' => (int) $company['id']]);
        if (!$roleStmt->fetch()) {
            Session::flash('error', 'Invalid role selected.');
            redirect('superadmin/company/edit/' . (int) $company['id']);
        }

        db()->prepare(
            'INSERT INTO company_user_memberships (company_id, user_id, role_id, is_default, is_active)
             VALUES (:cid, :uid, :rid, 0, 1)
             ON DUPLICATE KEY UPDATE role_id = VALUES(role_id), is_active = 1'
        )->execute(['cid' => (int) $company['id'], 'uid' => (int) $user['id'], 'rid' => $roleId]);

        AuditLog::recordPlatform('granted_access', 'Granted company access to ' . $email, 'Company', (int) $company['id']);
        Session::flash('success', 'Company access granted to ' . $email . '.');
        redirect('superadmin/company/edit/' . (int) $company['id']);
    }

    public function revokeAccess(string $membershipId = ''): void
    {
        require_superadmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('superadmin/company/index'); }
        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid token.');
            redirect('superadmin/company/index');
        }

        $stmt = db()->prepare('SELECT * FROM company_user_memberships WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => (int) $membershipId]);
        $membership = $stmt->fetch();
        if (!$membership) {
            Session::flash('error', 'Access record not found.');
            redirect('superadmin/company/index');
        }

        db()->prepare('UPDATE company_user_memberships SET is_active = 0 WHERE id = :id')
            ->execute(['id' => (int) $membershipId]);

        AuditLog::recordPlatform('revoked_access', 'Revoked company access membership ' . (string) $membershipId, 'Company', (int) $membership['company_id']);
        Session::flash('success', 'Company access revoked.');
        redirect('superadmin/company/edit/' . (int) $membership['company_id']);
    }

    public function removeLogo(string $id = ''): void
    {
        require_superadmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('superadmin/company/index'); }
        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid token.');
            redirect('superadmin/company/index');
        }

        $company = $this->findOrFail((int) $id);
        $this->deleteStoredLogo((string) ($company['logo_path'] ?? ''));
        db()->prepare('UPDATE companies SET logo_path = NULL WHERE id = :id')
            ->execute(['id' => (int) $id]);

        AuditLog::recordPlatform('removed_logo', 'Removed company logo for ' . (string) $company['name'], 'Company', (int) $id);
        Session::flash('success', 'Company logo removed.');
        redirect('superadmin/company/edit/' . (int) $id);
    }

    public function toggle(string $id = ''): void
    {
        require_superadmin();
        $this->ensureDeletionSchema();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('superadmin/company/index'); }
        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid token.');
            redirect('superadmin/company/index');
        }

        $company = $this->findOrFail((int) $id);
        $newState = $company['is_active'] ? 0 : 1;
        if ($newState === 1) {
            db()->prepare(
                "UPDATE companies
                 SET is_active = 1, account_status = 'Active', reactivated_at = NOW(), reactivated_by = :by
                 WHERE id = :id"
            )->execute(['by' => (int) ($_SESSION['superadmin_user']['id'] ?? 0) ?: null, 'id' => (int) $id]);
        } else {
            db()->prepare(
                "UPDATE companies
                 SET is_active = 0, account_status = 'Suspended', suspended_at = NOW(), suspended_by = :by
                 WHERE id = :id"
            )->execute(['by' => (int) ($_SESSION['superadmin_user']['id'] ?? 0) ?: null, 'id' => (int) $id]);
        }
        $label = $newState ? 'activated' : 'deactivated';
        AuditLog::recordPlatform($newState ? 'activated' : 'deactivated', ucfirst($label) . ' company ' . (string) $company['name'], 'Company', (int) $id);
        Session::flash('success', "Company '{$company['name']}' has been {$label}.");
        redirect('superadmin/company/index');
    }

    public function delete(string $id = ''): void
    {
        require_superadmin();
        $this->ensureDeletionSchema();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('superadmin/company/index');
        }
        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid token.');
            redirect('superadmin/company/index');
        }

        $company = $this->findOrFail((int) $id);
        $confirmation = trim((string) $this->input('confirm_name', ''));
        if ($confirmation !== (string) $company['name']) {
            Session::flash('error', 'Company was not deleted. Type the company name exactly to confirm deletion.');
            redirect('superadmin/company/view/' . (int) $company['id']);
        }

        $reason = trim((string) $this->input('deletion_reason', 'Deleted from platform admin.'));
        $adminId = (int) ($_SESSION['superadmin_user']['id'] ?? 0) ?: null;
        $db = db();

        try {
            $db->beginTransaction();

            $db->prepare(
                "UPDATE companies
                 SET is_active = 0,
                     account_status = 'Deleted',
                     deleted_at = NOW(),
                     deleted_by = :deleted_by,
                     deletion_reason = :reason,
                     suspended_at = COALESCE(suspended_at, NOW()),
                     suspended_by = COALESCE(suspended_by, :suspended_by),
                     suspension_reason = COALESCE(suspension_reason, 'Company deleted by platform admin')
                 WHERE id = :id AND deleted_at IS NULL"
            )->execute([
                'deleted_by' => $adminId,
                'suspended_by' => $adminId,
                'reason' => $reason !== '' ? $reason : 'Deleted from platform admin.',
                'id' => (int) $company['id'],
            ]);

            $db->prepare('UPDATE company_user_memberships SET is_active = 0 WHERE company_id = :cid')
                ->execute(['cid' => (int) $company['id']]);

            $db->prepare("UPDATE subscriptions SET status = 'Cancelled' WHERE company_id = :cid AND status = 'Active'")
                ->execute(['cid' => (int) $company['id']]);

            $db->commit();
            AuditLog::recordPlatform('deleted', 'Deleted company ' . (string) $company['name'], 'Company', (int) $company['id'], ['reason' => $reason]);
            Session::flash('success', "Company '{$company['name']}' has been deleted from the active platform list.");
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            Session::flash('error', 'Company could not be deleted: ' . $e->getMessage());
            redirect('superadmin/company/view/' . (int) $company['id']);
        }

        redirect('superadmin/company/index');
    }

    public function view(string $id = ''): void
    {
        require_superadmin();
        $this->ensureDeletionSchema();
        new Branch();
        $company = $this->findOrFail((int) $id);

        $employees = (int) db()->prepare("SELECT COUNT(*) FROM employees WHERE company_id=:id")->execute(['id'=>(int)$id]) ? 0 : 0;
        $stmt = db()->prepare("SELECT COUNT(*) FROM employees WHERE company_id=:id"); $stmt->execute(['id'=>(int)$id]);
        $employees = (int) $stmt->fetchColumn();

        $stmt2 = db()->prepare("SELECT COUNT(*) FROM company_user_memberships WHERE company_id=:id AND is_active = 1"); $stmt2->execute(['id'=>(int)$id]);
        $users = (int) $stmt2->fetchColumn();

        $stmt3 = db()->prepare("SELECT COUNT(*) FROM payroll_runs WHERE company_id=:id"); $stmt3->execute(['id'=>(int)$id]);
        $payrollRuns = (int) $stmt3->fetchColumn();

        $stmt4 = db()->prepare("SELECT COUNT(*) FROM branches WHERE company_id=:id"); $stmt4->execute(['id'=>(int)$id]);
        $branches = (int) $stmt4->fetchColumn();

        $subs = db()->prepare("SELECT * FROM subscriptions WHERE company_id=:id ORDER BY created_at DESC");
        $subs->execute(['id'=>(int)$id]);
        $subscriptions = $subs->fetchAll();

        $this->renderSuperAdmin('superadmin/companies/view', [
            'title'         => $company['name'],
            'company'       => $company,
            'employees'     => $employees,
            'users'         => $users,
            'branches'      => $branches,
            'payrollRuns'   => $payrollRuns,
            'subscriptions' => $subscriptions,
            'csrf'          => Session::csrfToken(),
        ]);
    }

    private function findOrFail(int $id): array
    {
        new ClientEntity();
        $this->ensureDeletionSchema();
        $stmt = db()->prepare("SELECT c.*, ce.name AS client_entity_name FROM companies c LEFT JOIN client_entities ce ON ce.id = c.client_entity_id WHERE c.id = :id AND c.deleted_at IS NULL LIMIT 1");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        if (!$row) { http_response_code(404); exit('Company not found.'); }
        return $row;
    }

    private function companyMemberships(int $companyId): array
    {
        $stmt = db()->prepare(
            'SELECT m.*, u.full_name, u.email, r.name AS role_name
             FROM company_user_memberships m
             JOIN users u ON u.id = m.user_id
             JOIN roles r ON r.id = m.role_id
             WHERE m.company_id = :cid AND m.is_active = 1
             ORDER BY u.full_name ASC'
        );
        $stmt->execute(['cid' => $companyId]);

        return $stmt->fetchAll();
    }

    private function ensureCompanySuperAdminRole(int $companyId): int
    {
        $stmt = db()->prepare(
            "SELECT id FROM roles WHERE company_id = :cid AND name = 'Super Admin' LIMIT 1"
        );
        $stmt->execute(['cid' => $companyId]);
        $roleId = (int) ($stmt->fetchColumn() ?: 0);

        if ($roleId > 0) {
            return $roleId;
        }

        $insert = db()->prepare(
            "INSERT INTO roles (company_id, name, description, access_level)
             VALUES (:cid, 'Super Admin', 'Full company administration access.', 'Super Admin')"
        );
        $insert->execute(['cid' => $companyId]);

        return (int) db()->lastInsertId();
    }

    private function generateTemporaryPassword(): string
    {
        return 'Stonesoft@' . random_int(100000, 999999) . strtoupper(substr(bin2hex(random_bytes(3)), 0, 4));
    }

    private function isStrongTemporaryPassword(string $password): bool
    {
        return strlen($password) >= 10
            && preg_match('/[A-Z]/', $password) === 1
            && preg_match('/[a-z]/', $password) === 1
            && preg_match('/\d/', $password) === 1
            && preg_match('/[^A-Za-z0-9]/', $password) === 1;
    }

    private function sendCompanyWelcomeEmail(string $companyName, string $adminName, string $adminEmail, string $temporaryPassword): array
    {
        if (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
            return ['sent' => false, 'error' => 'Invalid admin email address.'];
        }

        $loginUrl = base_url('auth/login');
        $subject = 'Your ' . app_product_name() . ' account has been created';
        $html = $this->companyWelcomeEmailHtml($companyName, $adminName, $adminEmail, $temporaryPassword, $loginUrl);
        $mailer = new MailService($this->platformEmailSettings());

        try {
            if ($mailer->send($adminEmail, $adminName, $subject, $html)) {
                AuditLog::recordPlatform('company_welcome_email_sent', 'Sent company welcome email to ' . $adminEmail, 'Company');
                return ['sent' => true, 'error' => ''];
            }

            return ['sent' => false, 'error' => $mailer->lastError()];
        } catch (Throwable $e) {
            return ['sent' => false, 'error' => $e->getMessage()];
        }
    }

    private function companyWelcomeEmailHtml(string $companyName, string $adminName, string $adminEmail, string $temporaryPassword, string $loginUrl): string
    {
        $company = htmlspecialchars($companyName, ENT_QUOTES, 'UTF-8');
        $name = htmlspecialchars($adminName, ENT_QUOTES, 'UTF-8');
        $email = htmlspecialchars($adminEmail, ENT_QUOTES, 'UTF-8');
        $password = htmlspecialchars($temporaryPassword, ENT_QUOTES, 'UTF-8');
        $url = htmlspecialchars($loginUrl, ENT_QUOTES, 'UTF-8');
        $product = htmlspecialchars(app_product_name(), ENT_QUOTES, 'UTF-8');
        $vendor = htmlspecialchars(app_vendor_name(), ENT_QUOTES, 'UTF-8');

        return <<<HTML
        <html>
        <body style="margin:0;background:#f3f6fb;font-family:Arial,sans-serif;color:#0f172a">
            <div style="max-width:640px;margin:0 auto;padding:28px 18px">
                <div style="background:#ffffff;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden">
                    <div style="background:#0f172a;color:#ffffff;padding:22px 26px">
                        <h1 style="font-size:22px;line-height:1.3;margin:0">Welcome to {$product}</h1>
                        <p style="margin:8px 0 0;color:#cbd5e1">Your company account is ready.</p>
                    </div>
                    <div style="padding:26px">
                        <p style="margin-top:0">Hello {$name},</p>
                        <p>Your administrator account for <strong>{$company}</strong> has been created on {$product}.</p>
                        <table style="border-collapse:collapse;width:100%;margin:18px 0;background:#f8fafc;border:1px solid #e2e8f0">
                            <tr>
                                <td style="padding:10px 12px;font-weight:bold;width:160px">Login email</td>
                                <td style="padding:10px 12px">{$email}</td>
                            </tr>
                            <tr>
                                <td style="padding:10px 12px;font-weight:bold">One-time password</td>
                                <td style="padding:10px 12px;font-family:Consolas,monospace;font-size:16px">{$password}</td>
                            </tr>
                        </table>
                        <p>Use the button below to sign in. For security, the system will ask you to create a new password before continuing.</p>
                        <p style="margin:24px 0">
                            <a href="{$url}" style="display:inline-block;background:#2563eb;color:#ffffff;text-decoration:none;padding:12px 18px;border-radius:8px;font-weight:bold">Log in to Corevia</a>
                        </p>
                        <p style="font-size:13px;color:#64748b">If the button does not open, copy this link into your browser:<br>{$url}</p>
                        <p style="margin-bottom:0">Kind regards,<br>{$vendor}</p>
                    </div>
                </div>
            </div>
        </body>
        </html>
        HTML;
    }

    private function platformEmailSettings(): array
    {
        $defaults = [
            'email_notifications_enabled' => '1',
            'smtp_from_email' => 'info@stonesoftzambia.com',
            'smtp_from_name' => app_vendor_name(),
        ];

        try {
            $stmt = db()->query(
                "SELECT setting_key, setting_value FROM settings
                 WHERE (company_id IS NULL OR company_id = 0)
                   AND setting_key IN (
                       'email_notifications_enabled','smtp_host','smtp_port','smtp_encryption',
                       'smtp_username','smtp_password','smtp_from_email','smtp_from_name','smtp_hr_email'
                   )"
            );

            foreach ($stmt->fetchAll() as $row) {
                $defaults[(string) $row['setting_key']] = (string) $row['setting_value'];
            }
        } catch (Throwable) {
            return $defaults;
        }

        if (isset($defaults['smtp_password'])) {
            $defaults['smtp_password'] = SecretBox::decryptOrPlain((string) $defaults['smtp_password']);
        }

        $serverConfigFile = BASE_PATH . '/config/server.php';
        if (is_file($serverConfigFile)) {
            $serverConfig = require $serverConfigFile;
            $mailConfig = is_array($serverConfig) ? ($serverConfig['mail'] ?? []) : [];
            if (is_array($mailConfig)) {
                foreach ($mailConfig as $key => $value) {
                    $defaults[(string) $key] = (string) $value;
                }
            }
        }

        return $defaults;
    }

    private function consumeNewAdminPassword(int $companyId): ?array
    {
        $payload = $_SESSION['_new_company_admin_password'] ?? null;
        if (!is_array($payload) || (int) ($payload['company_id'] ?? 0) !== $companyId) {
            return null;
        }

        unset($_SESSION['_new_company_admin_password']);

        return $payload;
    }

    private function flashCreateOldInput(array $input): void
    {
        $_SESSION['_old_company_create'] = $input;
    }

    private function consumeCreateOldInput(): array
    {
        $old = $_SESSION['_old_company_create'] ?? [];
        unset($_SESSION['_old_company_create']);

        return is_array($old) ? $old : [];
    }

    private function activeSubscriptionPlans(): array
    {
        try {
            return db()->query(
                'SELECT * FROM subscription_plans WHERE is_active = 1 ORDER BY sort_order ASC, name ASC'
            )->fetchAll();
        } catch (Throwable) {
            return [
                ['name' => 'Trial', 'default_monthly_rate' => 0, 'default_billing_cycle' => 'Annual', 'currency' => 'ZMW'],
                ['name' => 'Basic', 'default_monthly_rate' => 35, 'default_billing_cycle' => 'Annual', 'currency' => 'ZMW'],
                ['name' => 'Standard', 'default_monthly_rate' => 50, 'default_billing_cycle' => 'Annual', 'currency' => 'ZMW'],
                ['name' => 'Premium', 'default_monthly_rate' => 75, 'default_billing_cycle' => 'Annual', 'currency' => 'ZMW'],
            ];
        }
    }

    private function findActiveSubscriptionPlan(string $name): ?array
    {
        $stmt = db()->prepare('SELECT * FROM subscription_plans WHERE name = :name AND is_active = 1 LIMIT 1');
        $stmt->execute(['name' => $name]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    private function money(string $value): float
    {
        return round(max(0, (float) preg_replace('/[^\d.]/', '', $value)), 2);
    }

    private function resolveClientEntityId(int $clientEntityId, string $newName, string $companyName, string $email = '', string $phone = ''): int
    {
        $model = new ClientEntity();

        if ($newName !== '') {
            return $model->createFromName($newName);
        }

        if ($clientEntityId > 0) {
            $existing = $model->find($clientEntityId);
            if ($existing) {
                return $clientEntityId;
            }
        }

        $existing = $model->findByName($companyName);
        if ($existing) {
            return (int) $existing['id'];
        }

        return $model->insert([
            'name' => $companyName,
            'code' => $model->generateNextCode(),
            'entity_type' => 'Single Company',
            'email' => $email !== '' ? $email : null,
            'phone' => $phone !== '' ? $phone : null,
            'is_active' => 1,
        ]);
    }

    private function storeCompanyLogo(int $companyId, ?array $file): string
    {
        if (!$file) {
            throw new RuntimeException('Please choose a logo file to upload.');
        }

        $mime = UploadedFileGuard::validate($file, UploadedFileGuard::IMAGE_MIMES, 2 * 1024 * 1024);

        $dir = BASE_PATH . '/public/uploads/company_logos';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $filename = UploadedFileGuard::safeStoredName('company_' . $companyId, $mime, UploadedFileGuard::IMAGE_MIMES);
        $dest = $dir . '/' . $filename;

        if (!move_uploaded_file((string) $file['tmp_name'], $dest)) {
            throw new RuntimeException('Failed to save company logo.');
        }

        return 'public/uploads/company_logos/' . $filename;
    }

    private function deleteStoredLogo(string $logoPath): void
    {
        if ($logoPath === '' || !str_starts_with($logoPath, 'public/uploads/company_logos/')) {
            return;
        }

        $path = BASE_PATH . '/' . $logoPath;
        if (is_file($path)) {
            @unlink($path);
        }
    }

    private function ensureDeletionSchema(): void
    {
        $this->addCompanyColumnIfMissing('deleted_at', 'DATETIME NULL AFTER account_status');
        $this->addCompanyColumnIfMissing('deleted_by', 'BIGINT UNSIGNED NULL AFTER deleted_at');
        $this->addCompanyColumnIfMissing('deletion_reason', 'TEXT NULL AFTER deleted_by');
    }

    private function addCompanyColumnIfMissing(string $column, string $definition): void
    {
        $stmt = db()->prepare(
            "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'companies' AND COLUMN_NAME = :column"
        );
        $stmt->execute(['column' => $column]);
        if ((int) $stmt->fetchColumn() === 0) {
            db()->exec("ALTER TABLE companies ADD COLUMN {$column} {$definition}");
        }
    }
}
