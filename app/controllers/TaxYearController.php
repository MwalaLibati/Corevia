<?php

declare(strict_types=1);

class TaxYearController extends Controller
{
    public function index(): void
    {
        require_auth();
        require_role(['Super Admin', 'Finance Officer']);

        $model = new PayrollTaxYear();
        $this->render('tax_years/index', [
            'title' => 'Tax Year Configuration',
            'years' => $model->listAll(),
            'csrf' => Session::csrfToken(),
            'flashSuccess' => Session::flash('success'),
            'flashError' => Session::flash('error'),
        ]);
    }

    public function store(): void
    {
        require_auth();
        require_role(['Super Admin', 'Finance Officer']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('tax-year/index');
        }

        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid request token.');
            redirect('tax-year/index');
        }

        $name = trim((string) $this->input('name', ''));
        $startsOn = $this->normalizeDate((string) $this->input('starts_on', ''));
        $endsOn = $this->normalizeDate((string) $this->input('ends_on', ''));

        if ($name === '' || $startsOn === null || $endsOn === null) {
            Session::flash('error', 'Name, start date, and end date are required.');
            redirect('tax-year/index');
        }

        if ($endsOn < $startsOn) {
            Session::flash('error', 'Tax year end date cannot be before the start date.');
            redirect('tax-year/index');
        }

        try {
            (new PayrollTaxYear())->insert([
                'name' => $name,
                'starts_on' => $startsOn,
                'ends_on' => $endsOn,
                'is_active' => (int) $this->input('is_active', 0) === 1 ? 1 : 0,
                'notes' => trim((string) $this->input('notes', '')),
                'created_by' => (int) (current_user()['id'] ?? 0) ?: null,
            ]);
            AuditLog::record('tax_year_create', 'Created payroll tax year ' . $name, 'PayrollTaxYear', null);
            Session::flash('success', 'Tax year created successfully.');
        } catch (Throwable $exception) {
            Session::flash('error', 'Failed to create tax year: ' . $exception->getMessage());
        }

        redirect('tax-year/index');
    }

    public function toggle(string $id): void
    {
        require_auth();
        require_role(['Super Admin', 'Finance Officer']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('tax-year/index');
        }

        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid request token.');
            redirect('tax-year/index');
        }

        $yearId = (int) $id;
        $year = (new PayrollTaxYear())->find($yearId);
        if (!$year) {
            Session::flash('error', 'Tax year not found.');
            redirect('tax-year/index');
        }

        (new PayrollTaxYear())->update($yearId, ['is_active' => (int) ($year['is_active'] ?? 0) === 1 ? 0 : 1]);
        AuditLog::record('tax_year_toggle', 'Changed tax year active status for ' . (string) $year['name'], 'PayrollTaxYear', $yearId);
        Session::flash('success', 'Tax year status updated.');
        redirect('tax-year/index');
    }

    private function normalizeDate(string $value): ?string
    {
        $date = date_create(trim($value));

        return $date === false ? null : $date->format('Y-m-d');
    }
}
