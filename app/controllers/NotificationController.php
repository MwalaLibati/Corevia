<?php

declare(strict_types=1);

/**
 * Handles in-app notification mark-read actions.
 */

class NotificationController extends Controller
{
    public function markRead(string $id): void
    {
        require_auth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            http_response_code(405);
            exit('Invalid notification request.');
        }

        $notifId = (int) $id;
        $userId  = (int) ($_SESSION['auth_user']['id'] ?? 0);
        if ($notifId > 0 && $userId > 0) {
            (new Notification())->markRead($notifId, $userId);
        }
        $ref = $_SERVER['HTTP_REFERER'] ?? base_url('dashboard/index');
        header('Location: ' . $ref);
        exit;
    }

    public function markAll(): void
    {
        require_auth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            http_response_code(405);
            exit('Invalid notification request.');
        }

        $userId = (int) ($_SESSION['auth_user']['id'] ?? 0);
        if ($userId > 0) {
            (new Notification())->markAllRead($userId);
        }
        $ref = $_SERVER['HTTP_REFERER'] ?? base_url('dashboard/index');
        header('Location: ' . $ref);
        exit;
    }
}
