<?php

declare(strict_types=1);

class AnnouncementController extends Controller
{
    public function index(): void
    {
        require_auth();
        require_role(['Super Admin', 'HR Officer', 'Finance Officer', 'Viewer']);

        $model = new Announcement();
        $this->render('announcements/index', [
            'title'        => 'Announcements',
            'announcements'=> $model->listWithDetails(),
            'csrf'         => Session::csrfToken(),
            'flashSuccess' => Session::flash('success'),
            'flashError'   => Session::flash('error'),
        ]);
    }

    public function create(): void
    {
        require_auth();
        require_role(['Super Admin', 'HR Officer']);

        $this->render('announcements/create', [
            'title'      => 'New Announcement',
            'csrf'       => Session::csrfToken(),
            'flashError' => Session::flash('error'),
            'old'        => $_SESSION['_old_announce'] ?? [],
        ]);
        unset($_SESSION['_old_announce']);
    }

    public function store(): void
    {
        require_auth();
        require_role(['Super Admin', 'HR Officer']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('announcement/index'); }
        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid request token.');
            redirect('announcement/create');
        }

        $data = [
            'title'        => trim((string) $this->input('title', '')),
            'body'         => trim((string) $this->input('body', '')),
            'expires_at'   => trim((string) $this->input('expires_at', '')) ?: null,
            'is_published' => (int) (bool) $this->input('is_published', 0),
            'posted_by'    => (int) (current_user()['id'] ?? 0) ?: null,
        ];
        $_SESSION['_old_announce'] = $data;

        if ($data['title'] === '' || $data['body'] === '') {
            Session::flash('error', 'Title and body are required.');
            redirect('announcement/create');
        }

        (new Announcement())->insert($data);
        AuditLog::record('announcement_create', "Announcement created: {$data['title']}.", 'Announcement');
        unset($_SESSION['_old_announce']);
        Session::flash('success', 'Announcement saved.');
        redirect('announcement/index');
    }

    public function edit(string $id): void
    {
        require_auth();
        require_role(['Super Admin', 'HR Officer']);

        $item = (new Announcement())->find((int) $id);
        if (!$item) { Session::flash('error', 'Not found.'); redirect('announcement/index'); }

        $this->render('announcements/edit', [
            'title'        => 'Edit Announcement',
            'item'         => $item,
            'csrf'         => Session::csrfToken(),
            'flashError'   => Session::flash('error'),
        ]);
    }

    public function update(string $id): void
    {
        require_auth();
        require_role(['Super Admin', 'HR Officer']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('announcement/index'); }
        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid token.');
            redirect('announcement/edit/' . $id);
        }

        $data = [
            'title'        => trim((string) $this->input('title', '')),
            'body'         => trim((string) $this->input('body', '')),
            'expires_at'   => trim((string) $this->input('expires_at', '')) ?: null,
            'is_published' => (int) (bool) $this->input('is_published', 0),
        ];

        if ($data['title'] === '' || $data['body'] === '') {
            Session::flash('error', 'Title and body are required.');
            redirect('announcement/edit/' . $id);
        }

        (new Announcement())->update((int) $id, $data);
        AuditLog::record('announcement_update', "Announcement #{$id} updated.", 'Announcement', (int) $id);
        Session::flash('success', 'Announcement updated.');
        redirect('announcement/index');
    }

    public function togglePublish(string $id): void
    {
        require_auth();
        require_role(['Super Admin', 'HR Officer']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('announcement/index'); }
        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid token.');
            redirect('announcement/index');
        }

        $model = new Announcement();
        $item  = $model->find((int) $id);
        if (!$item) { Session::flash('error', 'Not found.'); redirect('announcement/index'); }

        $newState = $item['is_published'] ? 0 : 1;
        $model->update((int) $id, ['is_published' => $newState]);
        Session::flash('success', $newState ? 'Announcement published.' : 'Announcement unpublished.');
        redirect('announcement/index');
    }

    public function delete(string $id): void
    {
        require_auth();
        require_role(['Super Admin', 'HR Officer']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('announcement/index'); }
        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid token.');
            redirect('announcement/index');
        }

        (new Announcement())->delete((int) $id);
        AuditLog::record('announcement_delete', "Announcement #{$id} deleted.", 'Announcement', (int) $id);
        Session::flash('success', 'Announcement deleted.');
        redirect('announcement/index');
    }
}
