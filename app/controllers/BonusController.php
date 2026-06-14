<?php

declare(strict_types=1);

/**
 * Bonus and overtime controller scaffold.
 */

class BonusController extends Controller
{
    public function index(): void
    {
        require_auth();
        require_role(['Super Admin', 'Finance Officer', 'HR Officer']);

        $model = new BonusOvertime();
        $search = trim((string) $this->input('search', ''));
        $items = $search === '' ? $model->listWithEmployee() : $model->search($search);

        $this->render('bonuses/index', [
            'title' => 'Bonuses & Overtime',
            'items' => $items,
            'search' => $search,
        ]);
    }
}
