<?php

declare(strict_types=1);

class AffiliateDashboardController extends Controller
{
    public function index(): void
    {
        require_affiliate_password_ready();
        $affiliate = current_affiliate() ?? [];
        $model = new Affiliate();
        $model->ensureSchema();

        $this->renderAffiliate('affiliate/dashboard', [
            'title' => 'Affiliate Dashboard',
            'affiliate' => $model->find((int) ($affiliate['id'] ?? 0)) ?: $affiliate,
            'ready' => $model->tableReady(),
            'dashboard' => $model->dashboard((int) ($affiliate['id'] ?? 0)),
        ]);
    }

    public function companies(): void
    {
        require_affiliate_password_ready();
        $affiliate = current_affiliate() ?? [];
        $model = new Affiliate();
        $model->ensureSchema();
        $this->renderAffiliate('affiliate/companies', [
            'title' => 'My Referred Companies',
            'affiliate' => $affiliate,
            'companies' => $model->companies((int) ($affiliate['id'] ?? 0)),
        ]);
    }

    public function commissions(): void
    {
        require_affiliate_password_ready();
        $affiliate = current_affiliate() ?? [];
        $model = new Affiliate();
        $model->ensureSchema();
        $this->renderAffiliate('affiliate/commissions', [
            'title' => 'Commission Ledger',
            'affiliate' => $affiliate,
            'commissions' => $model->commissions((int) ($affiliate['id'] ?? 0), 500),
        ]);
    }

    public function statement(): void
    {
        require_affiliate_password_ready();
        $affiliate = current_affiliate() ?? [];
        $ops = new AffiliateOperations();
        $statement = $ops->statement((int) ($affiliate['id'] ?? 0));
        if (in_array((string) ($_GET['export'] ?? ''), ['csv','xls'], true)) {
            $format = (string) $_GET['export'];
            header('Content-Type: ' . ($format === 'xls' ? 'application/vnd.ms-excel' : 'text/csv') . '; charset=utf-8');
            header('Content-Disposition: attachment; filename="affiliate-statement-' . date('Ymd-His') . '.' . ($format === 'xls' ? 'xls' : 'csv') . '"');
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Date', 'Company', 'Invoice', 'Payment', 'Rate', 'Commission', 'Status']);
            foreach ($statement['items'] as $item) {
                fputcsv($out, [(string)$item['earned_at'], (string)($item['company_name'] ?? ''), (string)($item['invoice_number'] ?? ''), (string)$item['payment_amount'], (string)$item['commission_rate'], (string)$item['commission_amount'], (string)$item['status']]);
            }
            fclose($out);
            exit;
        }
        $this->renderAffiliate('affiliate/statement', [
            'title' => 'Affiliate Statement',
            'affiliate' => $affiliate,
            'statement' => $statement,
        ]);
    }

    public function agreements(): void
    {
        require_affiliate_password_ready();
        $affiliate = current_affiliate() ?? [];
        $ops = new AffiliateOperations();
        $this->renderAffiliate('affiliate/agreements', [
            'title' => 'Affiliate Agreements',
            'affiliate' => $affiliate,
            'agreements' => $ops->agreements((int) ($affiliate['id'] ?? 0)),
        ]);
    }

    public function support(): void
    {
        require_affiliate_password_ready();
        $affiliate = current_affiliate() ?? [];
        $ops = new AffiliateOperations();
        $this->renderAffiliate('affiliate/support', [
            'title' => 'Support & Messages',
            'affiliate' => $affiliate,
            'messages' => $ops->messages((int) ($affiliate['id'] ?? 0)),
            'tickets' => $ops->tickets((int) ($affiliate['id'] ?? 0)),
            'csrf' => Session::csrfToken(),
            'flash' => Session::flash('success'),
            'flashErr' => Session::flash('error'),
        ]);
    }

    public function ticketStore(): void
    {
        require_affiliate_password_ready();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('affiliate/dashboard/support'); }
        if (!Session::verifyCsrf((string) ($_POST['_csrf'] ?? ''))) {
            Session::flash('error', 'Invalid token.');
            redirect('affiliate/dashboard/support');
        }
        $subject = trim((string) ($_POST['subject'] ?? ''));
        $message = trim((string) ($_POST['message'] ?? ''));
        if ($subject === '' || $message === '') {
            Session::flash('error', 'Subject and message are required.');
            redirect('affiliate/dashboard/support');
        }
        $affiliate = current_affiliate() ?? [];
        (new AffiliateOperations())->createTicket((int) ($affiliate['id'] ?? 0), $subject, $message, (string) ($_POST['priority'] ?? 'Normal'));
        Session::flash('success', 'Support ticket submitted.');
        redirect('affiliate/dashboard/support');
    }

    public function leads(): void
    {
        require_affiliate_password_ready();
        $affiliate = current_affiliate() ?? [];
        $model = new Affiliate();
        $model->ensureSchema();

        $this->renderAffiliate('affiliate/leads', [
            'title' => 'Referral Leads',
            'affiliate' => $model->find((int) ($affiliate['id'] ?? 0)) ?: $affiliate,
            'leads' => $model->leads((int) ($affiliate['id'] ?? 0), 300),
            'csrf' => Session::csrfToken(),
            'flash' => Session::flash('success'),
            'flashErr' => Session::flash('error'),
        ]);
    }

    public function leadStore(): void
    {
        require_affiliate_password_ready();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('affiliate/dashboard/leads'); }
        if (!Session::verifyCsrf((string) ($_POST['_csrf'] ?? ''))) {
            Session::flash('error', 'Invalid token.');
            redirect('affiliate/dashboard/leads');
        }

        $companyName = trim((string) ($_POST['company_name'] ?? ''));
        $email = trim((string) ($_POST['contact_email'] ?? ''));
        if ($companyName === '' || ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL))) {
            Session::flash('error', 'Company name is required and email must be valid.');
            redirect('affiliate/dashboard/leads');
        }

        $affiliate = current_affiliate() ?? [];
        $model = new Affiliate();
        $model->ensureSchema();
        try {
            $model->createLead((int) ($affiliate['id'] ?? 0), $_POST);
            Session::flash('success', 'Referral lead submitted.');
        } catch (Throwable $e) {
            Session::flash('error', $e->getMessage());
        }
        redirect('affiliate/dashboard/leads');
    }

    public function profile(): void
    {
        require_affiliate_password_ready();
        $affiliate = current_affiliate() ?? [];
        $model = new Affiliate();
        $model->ensureSchema();

        $this->renderAffiliate('affiliate/profile', [
            'title' => 'My Affiliate Profile',
            'affiliate' => $model->find((int) ($affiliate['id'] ?? 0)) ?: $affiliate,
            'documents' => $model->documents((int) ($affiliate['id'] ?? 0)),
            'csrf' => Session::csrfToken(),
            'flash' => Session::flash('success'),
            'flashErr' => Session::flash('error'),
        ]);
    }

    public function profileUpdate(): void
    {
        require_affiliate_password_ready();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('affiliate/dashboard/profile'); }
        if (!Session::verifyCsrf((string) ($_POST['_csrf'] ?? ''))) {
            Session::flash('error', 'Invalid token.');
            redirect('affiliate/dashboard/profile');
        }

        $affiliate = current_affiliate() ?? [];
        $email = trim((string) ($_POST['alternate_email'] ?? ''));
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Session::flash('error', 'Alternate email must be valid.');
            redirect('affiliate/dashboard/profile');
        }

        $model = new Affiliate();
        $model->ensureSchema();
        db()->prepare(
            "UPDATE affiliates
             SET alternate_email = :alternate_email,
                 alternate_phone = :alternate_phone,
                 trading_name = :trading_name,
                 address = :address,
                 city = :city,
                 province = :province,
                 payout_method = :payout_method,
                 bank_name = :bank_name,
                 bank_account_name = :bank_account_name,
                 bank_account_number = :bank_account_number,
                 mobile_money_number = :mobile_money_number,
                 payout_details = :payout_details,
                 kyc_status = CASE WHEN kyc_status = 'Approved' THEN kyc_status ELSE 'Pending Review' END
             WHERE id = :id"
        )->execute([
            'alternate_email' => $email ?: null,
            'alternate_phone' => trim((string) ($_POST['alternate_phone'] ?? '')) ?: null,
            'trading_name' => trim((string) ($_POST['trading_name'] ?? '')) ?: null,
            'address' => trim((string) ($_POST['address'] ?? '')) ?: null,
            'city' => trim((string) ($_POST['city'] ?? '')) ?: null,
            'province' => trim((string) ($_POST['province'] ?? '')) ?: null,
            'payout_method' => trim((string) ($_POST['payout_method'] ?? '')) ?: null,
            'bank_name' => trim((string) ($_POST['bank_name'] ?? '')) ?: null,
            'bank_account_name' => trim((string) ($_POST['bank_account_name'] ?? '')) ?: null,
            'bank_account_number' => trim((string) ($_POST['bank_account_number'] ?? '')) ?: null,
            'mobile_money_number' => trim((string) ($_POST['mobile_money_number'] ?? '')) ?: null,
            'payout_details' => trim((string) ($_POST['payout_details'] ?? '')) ?: null,
            'id' => (int) ($affiliate['id'] ?? 0),
        ]);

        Session::flash('success', 'Profile updated and submitted for review.');
        redirect('affiliate/dashboard/profile');
    }

    public function documentUpload(): void
    {
        require_affiliate_password_ready();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('affiliate/dashboard/profile'); }
        if (!Session::verifyCsrf((string) ($_POST['_csrf'] ?? ''))) {
            Session::flash('error', 'Invalid token.');
            redirect('affiliate/dashboard/profile');
        }

        $affiliate = current_affiliate() ?? [];
        $file = $_FILES['document_file'] ?? null;
        if (!is_array($file)) {
            Session::flash('error', 'Please choose a document.');
            redirect('affiliate/dashboard/profile');
        }

        try {
            $mime = UploadedFileGuard::validate($file, UploadedFileGuard::DOCUMENT_MIMES, 5 * 1024 * 1024);
            $safeName = UploadedFileGuard::safeStoredName('affiliate_' . (int) ($affiliate['id'] ?? 0), $mime, UploadedFileGuard::DOCUMENT_MIMES);
            $dir = BASE_PATH . '/uploads/affiliate_docs/';
            if (!is_dir($dir)) { mkdir($dir, 0755, true); }
            if (!move_uploaded_file((string) $file['tmp_name'], $dir . $safeName)) {
                throw new RuntimeException('Failed to save uploaded document.');
            }

            $type = (string) ($_POST['document_type'] ?? 'Other');
            if (!in_array($type, ['NRC','TPIN','Affiliate Agreement','Bank Proof','Proof of Address','Other'], true)) {
                $type = 'Other';
            }

            $model = new Affiliate();
            $model->ensureSchema();
            db()->prepare(
                "INSERT INTO affiliate_documents (affiliate_id, document_type, file_name, file_path, file_size, mime_type, notes, uploaded_by)
                 VALUES (:affiliate_id, :document_type, :file_name, :file_path, :file_size, :mime_type, :notes, NULL)"
            )->execute([
                'affiliate_id' => (int) ($affiliate['id'] ?? 0),
                'document_type' => $type,
                'file_name' => (string) ($file['name'] ?? 'document'),
                'file_path' => 'uploads/affiliate_docs/' . $safeName,
                'file_size' => (int) ($file['size'] ?? 0),
                'mime_type' => $mime,
                'notes' => trim((string) ($_POST['notes'] ?? '')) ?: null,
            ]);

            db()->prepare("UPDATE affiliates SET kyc_status = CASE WHEN kyc_status = 'Approved' THEN kyc_status ELSE 'Pending Review' END WHERE id = :id")
                ->execute(['id' => (int) ($affiliate['id'] ?? 0)]);
            Session::flash('success', 'Document uploaded for review.');
        } catch (Throwable $e) {
            Session::flash('error', $e->getMessage());
        }

        redirect('affiliate/dashboard/profile');
    }
}
