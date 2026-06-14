<?php

declare(strict_types=1);

/**
 * Base controller with rendering and request helpers.
 */

abstract class Controller
{
    protected function render(string $view, array $data = []): void
    {
        if (!isset($data['notifCount']) && isset($_SESSION['auth_user'])) {
            try {
                $notifModel            = new Notification();
                $uid                   = (int) ($_SESSION['auth_user']['id'] ?? 0);
                $data['notifCount']    = $notifModel->unreadCountForUser($uid);
                $data['notifRecent']   = $notifModel->recentForUser($uid, 12);
            } catch (Throwable) {
                $data['notifCount']  = 0;
                $data['notifRecent'] = [];
            }
        }

        extract($data, EXTR_SKIP);

        $baseViewPath = BASE_PATH . '/app/views/';
        $viewFile = $baseViewPath . $view . '.php';

        if (!is_file($viewFile)) {
            http_response_code(404);
            exit('View not found: ' . $view);
        }

        require $baseViewPath . 'layouts/header.php';
        require $baseViewPath . 'layouts/sidebar.php';
        echo '<main class="main-wrapper"><div class="container-fluid"><div class="inner-contents">';
        require $viewFile;
        echo '</div></div></main>';
        require $baseViewPath . 'layouts/footer.php';
    }

    protected function renderPortal(string $view, array $data = []): void
    {
        extract($data, EXTR_SKIP);

        $baseViewPath = BASE_PATH . '/app/views/';
        $viewFile     = $baseViewPath . $view . '.php';

        if (!is_file($viewFile)) {
            http_response_code(404);
            exit('View not found: ' . $view);
        }

        require $baseViewPath . 'layouts/portal-header.php';
        require $baseViewPath . 'layouts/portal-sidebar.php';
        echo '<main class="main-wrapper"><div class="container-fluid"><div class="inner-contents">';
        require $viewFile;
        echo '</div></div></main>';
        require $baseViewPath . 'layouts/portal-footer.php';
    }

    protected function renderSuperAdmin(string $view, array $data = []): void
    {
        extract($data, EXTR_SKIP);

        $baseViewPath = BASE_PATH . '/app/views/';
        $viewFile     = $baseViewPath . $view . '.php';

        if (!is_file($viewFile)) {
            http_response_code(404);
            exit('View not found: ' . $view);
        }

        require $baseViewPath . 'layouts/superadmin-header.php';
        require $baseViewPath . 'layouts/superadmin-sidebar.php';
        require $viewFile;
        require $baseViewPath . 'layouts/superadmin-footer.php';
    }

    protected function renderAffiliate(string $view, array $data = []): void
    {
        extract($data, EXTR_SKIP);

        $baseViewPath = BASE_PATH . '/app/views/';
        $viewFile     = $baseViewPath . $view . '.php';

        if (!is_file($viewFile)) {
            http_response_code(404);
            exit('View not found: ' . $view);
        }

        require $baseViewPath . 'layouts/affiliate-header.php';
        require $baseViewPath . 'layouts/affiliate-sidebar.php';
        require $viewFile;
        require $baseViewPath . 'layouts/affiliate-footer.php';
    }

    protected function renderAuth(string $view, array $data = []): void
    {
        extract($data, EXTR_SKIP);

        $baseViewPath = BASE_PATH . '/app/views/';
        $viewFile = $baseViewPath . $view . '.php';

        if (!is_file($viewFile)) {
            http_response_code(404);
            exit('View not found: ' . $view);
        }

        require $viewFile;
    }

    protected function input(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }
}
