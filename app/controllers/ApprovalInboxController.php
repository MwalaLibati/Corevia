<?php

declare(strict_types=1);

class ApprovalInboxController extends Controller
{
    public function index(): void
    {
        require_auth();

        $model = new ApprovalInbox();
        $items = $model->pendingForCurrentUser(200);

        $this->render('approvals/index', [
            'title' => 'My Approvals',
            'items' => $items,
            'summary' => $model->summaryForItems($items),
        ]);
    }
}
