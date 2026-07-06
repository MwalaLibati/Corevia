<?php

declare(strict_types=1);

/**
 * Authentication and authorization helper functions.
 */

/* ---------------------------------------------------------------
 * COMPANY ADMIN AUTH
 * --------------------------------------------------------------- */

function is_logged_in(): bool
{
    return !empty($_SESSION['auth_user']);
}

function current_user(): ?array
{
    return $_SESSION['auth_user'] ?? null;
}

function require_auth(): void
{
    if (!is_logged_in()) {
        redirect('auth/login');
    }

    if (!empty($_SESSION['auth_user']['must_change_password'])) {
        $route = trim((string) ($_GET['url'] ?? ''), '/');
        $allowedRoutes = ['auth/forcePasswordChange', 'auth/forcePasswordChangeStore', 'auth/logout'];
        if (!in_array($route, $allowedRoutes, true)) {
            redirect('auth/forcePasswordChange');
        }
    }

    if (empty($_SESSION['company_memberships'])) {
        try {
            $_SESSION['company_memberships'] = (new User())->activeMemberships((int) ($_SESSION['auth_user']['id'] ?? 0));
        } catch (Throwable) {
            $_SESSION['company_memberships'] = [];
        }
    }

    // If tenant is not resolved, try to restore from the logged-in user's company
    if (!Tenant::resolved()) {
        $cid = (int) ($_SESSION['auth_user']['company_id'] ?? 0);
        if ($cid > 0) {
            try {
                $stmt = db()->prepare("SELECT * FROM companies WHERE id = :id AND is_active = 1 LIMIT 1");
                $stmt->execute(['id' => $cid]);
                $company = $stmt->fetch();
                if ($company) {
                    Tenant::set($company);
                }
            } catch (Throwable) {}
        }
    }
}

function require_role(array $allowedRoles): void
{
    $user = current_user();

    if (!$user) {
        redirect('auth/login');
    }

    $role = (string) ($user['role'] ?? '');
    $accessLevel = (string) ($user['access_level'] ?? '');

    if ($accessLevel === '' && !empty($user['role_id'])) {
        try {
            $stmt = db()->prepare('SELECT COALESCE(access_level, name) FROM roles WHERE id = :id LIMIT 1');
            $stmt->execute(['id' => (int) $user['role_id']]);
            $accessLevel = (string) ($stmt->fetchColumn() ?: '');
            $_SESSION['auth_user']['access_level'] = $accessLevel;
        } catch (Throwable) {
            $accessLevel = '';
        }
    }

    if (!in_array($role, $allowedRoles, true) && !in_array($accessLevel, $allowedRoles, true)) {
        http_response_code(403);
        exit('403 Forbidden');
    }
}

function module_catalog(): array
{
    return [
        'dashboard' => ['label' => 'Dashboard', 'icon' => 'bi-grid-1x2', 'url' => 'dashboard/index', 'section' => 'Overview', 'routes' => ['dashboard']],
        'employees' => ['label' => 'Employees', 'icon' => 'bi-people', 'url' => 'employee/index', 'section' => 'Employee Management', 'routes' => ['employee']],
        'onboarding' => ['label' => 'Onboarding Links', 'icon' => 'bi-person-plus', 'url' => 'onboarding/index', 'section' => 'Employee Management', 'routes' => ['onboarding']],
        'branches' => ['label' => 'Branches', 'icon' => 'bi-geo-alt', 'url' => 'branch/index', 'section' => 'Employee Management', 'routes' => ['branch']],
        'departments' => ['label' => 'Departments', 'icon' => 'bi-diagram-3', 'url' => 'department/index', 'section' => 'Employee Management', 'routes' => ['department']],
        'employment_types' => ['label' => 'Employment Types', 'icon' => 'bi-person-badge', 'url' => 'employment-type/index', 'section' => 'Employee Management', 'routes' => ['employment-type']],
        'letter_templates' => ['label' => 'Letter Templates', 'icon' => 'bi-envelope-paper', 'url' => 'employee-letter-template/index', 'section' => 'Employee Management', 'routes' => ['employee-letter-template']],
        'contracts' => ['label' => 'Contracts', 'icon' => 'bi-file-earmark-text', 'url' => 'contract/index', 'section' => 'Contracts', 'routes' => ['contract']],
        'contract_templates' => ['label' => 'Contract Templates', 'icon' => 'bi-file-earmark-code', 'url' => 'contract_template/index', 'section' => 'Contracts', 'routes' => ['contract-template', 'contract_template']],
        'attendance' => ['label' => 'Attendance', 'icon' => 'bi-calendar2-check', 'url' => 'attendance/index', 'section' => 'Leave & Attendance', 'routes' => ['attendance']],
        'leave' => ['label' => 'Leave Management', 'icon' => 'bi-calendar-heart', 'url' => 'leave/index', 'section' => 'Leave & Attendance', 'routes' => ['leave']],
        'leave_types' => ['label' => 'Leave Types', 'icon' => 'bi-calendar2-week', 'url' => 'leave-type/index', 'section' => 'Leave & Attendance', 'routes' => ['leave-type']],
        'salary' => ['label' => 'Salary Structures', 'icon' => 'bi-cash-stack', 'url' => 'salary/index', 'section' => 'Payroll', 'routes' => ['salary', 'salary-change']],
        'payroll' => ['label' => 'Payroll Runs', 'icon' => 'bi-receipt', 'url' => 'payroll/index', 'section' => 'Payroll', 'routes' => ['payroll']],
        'bonuses' => ['label' => 'Bonuses & Overtime', 'icon' => 'bi-graph-up-arrow', 'url' => 'bonus/index', 'section' => 'Payroll', 'routes' => ['bonus']],
        'deductions' => ['label' => 'Deductions & Tax', 'icon' => 'bi-percent', 'url' => 'deduction/index', 'section' => 'Payroll', 'routes' => ['deduction', 'tax-year']],
        'salary_advances' => ['label' => 'Salary Advances', 'icon' => 'bi-cash-coin', 'url' => 'salary-advance/index', 'section' => 'Payroll', 'routes' => ['salary-advance']],
        'reports' => ['label' => 'Reports', 'icon' => 'bi-bar-chart-line', 'url' => 'report/index', 'section' => 'Reporting & Admin', 'routes' => ['report']],
        'settings' => ['label' => 'System Settings', 'icon' => 'bi-gear', 'url' => 'settings/index', 'section' => 'Reporting & Admin', 'routes' => ['settings']],
        'workflow_settings' => ['label' => 'Workflow Settings', 'icon' => 'bi-diagram-2', 'url' => 'workflow/index', 'section' => 'Reporting & Admin', 'routes' => ['workflow']],
        'email_alerts' => ['label' => 'Email & Alerts', 'icon' => 'bi-envelope-fill', 'url' => 'settings/email', 'section' => 'Reporting & Admin', 'routes' => ['settings:email', 'settings:updateEmail', 'settings:updateEmailTemplates', 'settings:testEmail']],
        'roles' => ['label' => 'Roles', 'icon' => 'bi-key-fill', 'url' => 'role/index', 'section' => 'Reporting & Admin', 'routes' => ['role']],
        'users' => ['label' => 'Users & Roles', 'icon' => 'bi-shield-lock', 'url' => 'user-management/index', 'section' => 'Reporting & Admin', 'routes' => ['user-management']],
        'announcements' => ['label' => 'Announcements', 'icon' => 'bi-megaphone', 'url' => 'announcement/index', 'section' => 'Reporting & Admin', 'routes' => ['announcement']],
        'audit' => ['label' => 'Audit Log', 'icon' => 'bi-journal-text', 'url' => 'audit/index', 'section' => 'Reporting & Admin', 'routes' => ['audit']],
    ];
}

function normalize_module_key(string $moduleKey): string
{
    static $aliases = [
        'salary_structures' => 'salary',
        'payroll_runs' => 'payroll',
    ];

    return $aliases[$moduleKey] ?? $moduleKey;
}

function module_key_for_route(string $route, string $method = ''): ?string
{
    $route = trim($route, '/');
    if ($route === '') {
        $route = 'dashboard';
    }

    $specific = $route . ':' . $method;
    foreach (module_catalog() as $key => $module) {
        foreach (($module['routes'] ?? []) as $prefix) {
            if ($prefix === $specific) {
                return $key;
            }
        }
    }

    foreach (module_catalog() as $key => $module) {
        foreach (($module['routes'] ?? []) as $prefix) {
            if (!str_contains($prefix, ':') && $prefix === $route) {
                return $key;
            }
        }
    }

    foreach (module_catalog() as $key => $module) {
        foreach (($module['routes'] ?? []) as $prefix) {
            if (!str_contains($prefix, ':') && str_starts_with($route, $prefix)) {
                return $key;
            }
        }
    }

    return null;
}

function user_allowed_modules(?array $user = null): array
{
    $user = $user ?? current_user();
    if (!$user) {
        return [];
    }

    $catalogKeys = array_keys(module_catalog());
    $accessLevel = (string) ($user['access_level'] ?? '');
    $role = (string) ($user['role'] ?? '');

    if (in_array($accessLevel, ['Super Admin', 'Admin'], true) || in_array($role, ['Super Admin', 'Admin'], true)) {
        return $catalogKeys;
    }

    $roleId = (int) ($user['role_id'] ?? 0);
    $roleModules = $catalogKeys;

    if ($roleId > 0) {
        try {
            $stmt = db()->prepare('SELECT module_key FROM role_module_permissions WHERE role_id = :rid');
            $stmt->execute(['rid' => $roleId]);
            $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);

            $roleModules = $rows !== []
                ? array_values(array_unique(array_map(static fn ($key) => normalize_module_key((string) $key), $rows)))
                : $catalogKeys;
        } catch (Throwable) {
            $roleModules = $catalogKeys;
        }
    }

    return array_values(array_intersect($roleModules, company_subscription_modules()));
}

function company_subscription_modules(): array
{
    $catalogKeys = array_keys(module_catalog());
    $companyId = Tenant::id();
    if ($companyId <= 0) {
        $companyId = (int) ($_SESSION['auth_user']['company_id'] ?? 0);
    }

    if ($companyId <= 0) {
        return $catalogKeys;
    }

    try {
        $stmt = db()->prepare(
            "SELECT spm.module_key
             FROM companies c
             JOIN subscription_plans sp ON sp.name = c.subscription_plan AND sp.is_active = 1
             JOIN subscription_plan_modules spm ON spm.plan_id = sp.id
             WHERE c.id = :cid"
        );
        $stmt->execute(['cid' => $companyId]);
        $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);

        return $rows !== []
            ? array_values(array_intersect(array_unique(array_map(static fn ($key) => normalize_module_key((string) $key), $rows)), $catalogKeys))
            : $catalogKeys;
    } catch (Throwable) {
        return $catalogKeys;
    }
}

function can_access_module(string $moduleKey, ?array $user = null): bool
{
    $user = $user ?? current_user();
    $accessLevel = (string) ($user['access_level'] ?? '');
    $role = (string) ($user['role'] ?? '');

    if (in_array($accessLevel, ['Super Admin', 'Admin'], true) || in_array($role, ['Super Admin', 'Admin'], true)) {
        return true;
    }

    if ($moduleKey === 'dashboard') {
        return true;
    }

    return in_array($moduleKey, user_allowed_modules($user), true);
}

function require_module_access(string $route, string $method = ''): void
{
    if (!is_logged_in()) {
        return;
    }

    $moduleKey = module_key_for_route($route, $method);
    if ($moduleKey !== null && !can_access_module($moduleKey)) {
        http_response_code(403);
        exit('403 Forbidden');
    }
}

/* ---------------------------------------------------------------
 * EMPLOYEE PORTAL AUTH
 * --------------------------------------------------------------- */

function is_employee_logged_in(): bool
{
    return !empty($_SESSION['emp_user']);
}

function current_employee(): ?array
{
    return $_SESSION['emp_user'] ?? null;
}

function require_employee_auth(): void
{
    if (!is_employee_logged_in()) {
        redirect('portal/login');
    }

    // Restore tenant from employee's company_id if not already set
    if (!Tenant::resolved()) {
        $cid = (int) ($_SESSION['emp_user']['company_id'] ?? 0);
        if ($cid > 0) {
            try {
                $stmt = db()->prepare("SELECT * FROM companies WHERE id = :id AND is_active = 1 LIMIT 1");
                $stmt->execute(['id' => $cid]);
                $company = $stmt->fetch();
                if ($company) {
                    Tenant::set($company);
                }
            } catch (Throwable) {}
        }
    }

    if (!empty($_SESSION['emp_user']['portal_must_change_password'])) {
        $path = trim((string) ($_GET['url'] ?? ''), '/');
        $allowed = ['portal/changePassword', 'portal/changePasswordStore', 'portal/logout'];
        if (!in_array($path, $allowed, true)) {
            redirect('portal/changePassword');
        }
    }
}

/* ---------------------------------------------------------------
 * SUPERADMIN (PLATFORM OWNER) AUTH
 * --------------------------------------------------------------- */

function is_superadmin_logged_in(): bool
{
    return !empty($_SESSION['superadmin_user']);
}

function current_superadmin(): ?array
{
    return $_SESSION['superadmin_user'] ?? null;
}

function require_superadmin(): void
{
    if (!is_superadmin_logged_in()) {
        redirect('superadmin/auth/login');
    }
}

/* ---------------------------------------------------------------
 * AFFILIATE PORTAL AUTH
 * --------------------------------------------------------------- */

function is_affiliate_logged_in(): bool
{
    return !empty($_SESSION['affiliate_user']);
}

function current_affiliate(): ?array
{
    return $_SESSION['affiliate_user'] ?? null;
}

function require_affiliate_auth(): void
{
    if (!is_affiliate_logged_in()) {
        redirect('affiliate/auth/login');
    }
}

function require_affiliate_password_ready(): void
{
    require_affiliate_auth();

    if (!empty($_SESSION['affiliate_user']['must_change_password'])) {
        redirect('affiliate/auth/changePassword');
    }
}

/* ---------------------------------------------------------------
 * TENANT HELPERS
 * --------------------------------------------------------------- */

function current_company(): ?array
{
    return Tenant::current();
}

function company_id(): int
{
    return Tenant::id();
}
