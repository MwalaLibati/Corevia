<?php

declare(strict_types=1);

/**
 * Lightweight URL router and controller dispatcher.
 */

class App
{
    private string $defaultController = 'DashboardController';
    private string $defaultMethod = 'index';

    public function run(): void
    {
        $segments = $this->segments();

        // Superadmin portal: routes starting with 'superadmin'
        if (($segments[0] ?? '') === 'superadmin') {
            $this->runSuperAdmin(array_slice($segments, 1));
            return;
        }

        if (($segments[0] ?? '') === 'affiliate') {
            $this->runAffiliate(array_slice($segments, 1));
            return;
        }

        $controllerName = $this->resolveControllerName($segments[0] ?? null);
        $methodName = $segments[1] ?? $this->defaultMethod;
        $params = array_slice($segments, 2);

        if (function_exists('require_module_access')) {
            require_module_access($segments[0] ?? 'dashboard', $methodName);
        }

        $controllerFile = BASE_PATH . '/app/controllers/' . $controllerName . '.php';

        if (!is_file($controllerFile)) {
            http_response_code(404);
            exit('Controller not found');
        }

        require_once $controllerFile;
        $controller = new $controllerName();

        if (!method_exists($controller, $methodName)) {
            http_response_code(404);
            exit('Method not found');
        }

        call_user_func_array([$controller, $methodName], $params);
    }

    private function runSuperAdmin(array $segments): void
    {
        $rawName    = $segments[0] ?? 'dashboard';
        $methodName = $segments[1] ?? $this->defaultMethod;
        $params     = array_slice($segments, 2);

        $controllerName = 'Superadmin' . $this->resolveControllerName($rawName);
        $controllerFile = BASE_PATH . '/app/controllers/superadmin/' . $controllerName . '.php';

        if (!is_file($controllerFile)) {
            http_response_code(404);
            exit('Superadmin controller not found: ' . $controllerName);
        }

        require_once $controllerFile;
        $controller = new $controllerName();

        if (!method_exists($controller, $methodName)) {
            http_response_code(404);
            exit('Method not found');
        }

        call_user_func_array([$controller, $methodName], $params);
    }

    private function runAffiliate(array $segments): void
    {
        $rawName    = $segments[0] ?? 'dashboard';
        $methodName = $segments[1] ?? $this->defaultMethod;
        $params     = array_slice($segments, 2);

        $controllerName = 'Affiliate' . $this->resolveControllerName($rawName);
        $controllerFile = BASE_PATH . '/app/controllers/affiliate/' . $controllerName . '.php';

        if (!is_file($controllerFile)) {
            http_response_code(404);
            exit('Affiliate controller not found: ' . $controllerName);
        }

        require_once $controllerFile;
        $controller = new $controllerName();

        if (!method_exists($controller, $methodName)) {
            http_response_code(404);
            exit('Method not found');
        }

        call_user_func_array([$controller, $methodName], $params);
    }

    private function segments(): array
    {
        $path = $_GET['url'] ?? '';
        $path = trim((string) $path, '/');

        if ($path === '') {
            return [];
        }

        $parts = explode('/', $path);

        return array_values(array_filter($parts, static fn(string $value): bool => $value !== ''));
    }

    private function resolveControllerName(?string $segment): string
    {
        if (!$segment) {
            return $this->defaultController;
        }

        $cleanSegment = preg_replace('/[^A-Za-z0-9_-]/', '', $segment) ?? '';
        $parts = preg_split('/[-_]+/', $cleanSegment) ?: [];

        $normalized = '';
        foreach ($parts as $part) {
            if ($part === '') {
                continue;
            }

            $normalized .= ucfirst(strtolower($part));
        }

        $normalized .= 'Controller';

        return preg_replace('/[^A-Za-z0-9_]/', '', $normalized) ?: $this->defaultController;
    }
}
