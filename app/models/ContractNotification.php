<?php

declare(strict_types=1);

/**
 * Manages contract email notifications and the notification log.
 */
class ContractNotification extends Model
{
    protected string $table = 'contract_notification_log';

    // ─── Log helpers ─────────────────────────────────────────────────────────

    public function alreadySent(int $contractId, string $type): bool
    {
        $stmt = $this->db->prepare(
            'SELECT id FROM contract_notification_log
             WHERE contract_id = :cid AND notification_type = :type LIMIT 1'
        );
        $stmt->execute(['cid' => $contractId, 'type' => $type]);
        return (bool) $stmt->fetchColumn();
    }

    private function logSent(int $contractId, int $employeeId, string $type, string $emailTo): void
    {
        $this->insert([
            'contract_id'       => $contractId,
            'employee_id'       => $employeeId,
            'notification_type' => $type,
            'email_to'          => $emailTo,
        ]);
    }

    // ─── Settings ────────────────────────────────────────────────────────────

    public function emailSettings(): array
    {
        $cid = class_exists('Tenant') ? Tenant::id() : 0;
        $and = $cid > 0 ? ' AND company_id = :cid' : '';
        $stmt = $this->db->prepare(
            "SELECT setting_key, setting_value FROM settings
             WHERE setting_key IN (
               'smtp_host','smtp_port','smtp_encryption','smtp_username',
               'smtp_password','smtp_from_email','smtp_from_name',
               'smtp_hr_email','email_notifications_enabled'
             )$and"
        );
        $stmt->execute($cid > 0 ? ['cid' => $cid] : []);
        $map = [];
        foreach ($stmt->fetchAll() as $row) {
            $map[$row['setting_key']] = $row['setting_value'];
        }
        if (isset($map['smtp_password'])) {
            $map['smtp_password'] = SecretBox::decryptOrPlain((string) $map['smtp_password']);
        }
        return $map;
    }

    public function buildMailer(): MailService
    {
        return new MailService($this->emailSettings());
    }

    // ─── Queries ─────────────────────────────────────────────────────────────

    private function contractsExpiringUnnotified(int $days): array
    {
        $sql = "SELECT ec.*, e.full_name AS employee_name, e.email AS employee_email,
                       e.employee_number
                FROM employee_contracts ec
                JOIN employees e ON e.id = ec.employee_id
                WHERE ec.status = 'Active'
                  AND ec.end_date IS NOT NULL
                  AND ec.end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL :days DAY)
                  AND NOT EXISTS (
                      SELECT 1 FROM contract_notification_log cnl
                      WHERE cnl.contract_id = ec.id
                        AND cnl.notification_type = 'expiring_soon'
                  )
                ORDER BY ec.end_date ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['days' => $days]);
        return $stmt->fetchAll();
    }

    private function contractsJustExpiredUnnotified(): array
    {
        $sql = "SELECT ec.*, e.full_name AS employee_name, e.email AS employee_email,
                       e.employee_number
                FROM employee_contracts ec
                JOIN employees e ON e.id = ec.employee_id
                WHERE ec.status = 'Expired'
                  AND NOT EXISTS (
                      SELECT 1 FROM contract_notification_log cnl
                      WHERE cnl.contract_id = ec.id
                        AND cnl.notification_type = 'expired'
                  )
                ORDER BY ec.end_date ASC";
        return $this->db->query($sql)->fetchAll();
    }

    // ─── Email body builders ─────────────────────────────────────────────────

    private function buildExpiringSoonHtml(array $contract): string
    {
        $name   = htmlspecialchars((string) $contract['employee_name'], ENT_QUOTES);
        $number = htmlspecialchars((string) $contract['employee_number'], ENT_QUOTES);
        $cno    = htmlspecialchars((string) $contract['contract_number'], ENT_QUOTES);
        $type   = htmlspecialchars((string) $contract['contract_type'], ENT_QUOTES);
        $end    = htmlspecialchars((string) $contract['end_date'], ENT_QUOTES);
        $app    = htmlspecialchars(app_product_name(), ENT_QUOTES);
        $daysLeft = (int) ceil((strtotime($contract['end_date']) - time()) / 86400);
        return <<<HTML
        <html><body style="font-family:Arial,sans-serif;color:#222;padding:20px">
        <h2 style="color:#b45309">&#9888; Contract Expiry Reminder</h2>
        <p>This is an automated reminder from <strong>{$app}</strong>.</p>
        <table style="border-collapse:collapse;width:100%;max-width:520px">
          <tr><td style="padding:6px 10px;font-weight:bold;background:#fef3c7">Employee</td><td style="padding:6px 10px;background:#fffbeb">{$name} ({$number})</td></tr>
          <tr><td style="padding:6px 10px;font-weight:bold;background:#fef3c7">Contract No.</td><td style="padding:6px 10px;background:#fffbeb">{$cno}</td></tr>
          <tr><td style="padding:6px 10px;font-weight:bold;background:#fef3c7">Contract Type</td><td style="padding:6px 10px;background:#fffbeb">{$type}</td></tr>
          <tr><td style="padding:6px 10px;font-weight:bold;background:#fef3c7">Expiry Date</td><td style="padding:6px 10px;background:#fffbeb"><strong style="color:#b45309">{$end}</strong> ({$daysLeft} day(s) remaining)</td></tr>
        </table>
        <p style="margin-top:16px">Please take appropriate action to renew or end this contract before the expiry date.</p>
        <p style="color:#888;font-size:11px;margin-top:20px">Sent automatically by {$app} - do not reply to this message.</p>
        </body></html>
        HTML;
    }

    private function buildExpiredHtml(array $contract): string
    {
        $name   = htmlspecialchars((string) $contract['employee_name'], ENT_QUOTES);
        $number = htmlspecialchars((string) $contract['employee_number'], ENT_QUOTES);
        $cno    = htmlspecialchars((string) $contract['contract_number'], ENT_QUOTES);
        $type   = htmlspecialchars((string) $contract['contract_type'], ENT_QUOTES);
        $end    = htmlspecialchars((string) $contract['end_date'], ENT_QUOTES);
        $app    = htmlspecialchars(app_product_name(), ENT_QUOTES);
        return <<<HTML
        <html><body style="font-family:Arial,sans-serif;color:#222;padding:20px">
        <h2 style="color:#b91c1c">&#10006; Contract Expired</h2>
        <p>This is an automated notification from <strong>{$app}</strong>.</p>
        <table style="border-collapse:collapse;width:100%;max-width:520px">
          <tr><td style="padding:6px 10px;font-weight:bold;background:#fee2e2">Employee</td><td style="padding:6px 10px;background:#fef2f2">{$name} ({$number})</td></tr>
          <tr><td style="padding:6px 10px;font-weight:bold;background:#fee2e2">Contract No.</td><td style="padding:6px 10px;background:#fef2f2">{$cno}</td></tr>
          <tr><td style="padding:6px 10px;font-weight:bold;background:#fee2e2">Contract Type</td><td style="padding:6px 10px;background:#fef2f2">{$type}</td></tr>
          <tr><td style="padding:6px 10px;font-weight:bold;background:#fee2e2">Expired On</td><td style="padding:6px 10px;background:#fef2f2"><strong style="color:#b91c1c">{$end}</strong></td></tr>
        </table>
        <p style="margin-top:16px">This employee's contract has expired and was not renewed. Please update employment status as appropriate.</p>
        <p style="color:#888;font-size:11px;margin-top:20px">Sent automatically by {$app} - do not reply to this message.</p>
        </body></html>
        HTML;
    }

    private function buildRenewedHtml(array $contract, string $employeeName, string $employeeNumber): string
    {
        $name   = htmlspecialchars($employeeName, ENT_QUOTES);
        $number = htmlspecialchars($employeeNumber, ENT_QUOTES);
        $cno    = htmlspecialchars((string) $contract['contract_number'], ENT_QUOTES);
        $type   = htmlspecialchars((string) $contract['contract_type'], ENT_QUOTES);
        $start  = htmlspecialchars((string) $contract['start_date'], ENT_QUOTES);
        $end    = $contract['end_date'] ? htmlspecialchars((string) $contract['end_date'], ENT_QUOTES) : 'No fixed expiry';
        $app    = htmlspecialchars(app_product_name(), ENT_QUOTES);
        return <<<HTML
        <html><body style="font-family:Arial,sans-serif;color:#222;padding:20px">
        <h2 style="color:#15803d">&#10003; Contract Renewed</h2>
        <p>This is an automated notification from <strong>{$app}</strong>.</p>
        <table style="border-collapse:collapse;width:100%;max-width:520px">
          <tr><td style="padding:6px 10px;font-weight:bold;background:#dcfce7">Employee</td><td style="padding:6px 10px;background:#f0fdf4">{$name} ({$number})</td></tr>
          <tr><td style="padding:6px 10px;font-weight:bold;background:#dcfce7">New Contract No.</td><td style="padding:6px 10px;background:#f0fdf4">{$cno}</td></tr>
          <tr><td style="padding:6px 10px;font-weight:bold;background:#dcfce7">Contract Type</td><td style="padding:6px 10px;background:#f0fdf4">{$type}</td></tr>
          <tr><td style="padding:6px 10px;font-weight:bold;background:#dcfce7">Start Date</td><td style="padding:6px 10px;background:#f0fdf4">{$start}</td></tr>
          <tr><td style="padding:6px 10px;font-weight:bold;background:#dcfce7">End Date</td><td style="padding:6px 10px;background:#f0fdf4">{$end}</td></tr>
        </table>
        <p style="margin-top:16px">The employment contract for this staff member has been successfully renewed.</p>
        <p style="color:#888;font-size:11px;margin-top:20px">Sent automatically by {$app} - do not reply to this message.</p>
        </body></html>
        HTML;
    }

    // ─── Dispatch ─────────────────────────────────────────────────────────────

    /**
     * Run all pending expiry/expired notifications.
     * Returns a summary array ['sent'=>int,'skipped'=>int,'errors'=>string[]].
     */
    public function dispatchAll(): array
    {
        $mailer  = $this->buildMailer();
        $hrEmail = $mailer->hrEmail();
        $sent    = 0;
        $skipped = 0;
        $errors  = [];

        foreach ($this->contractsExpiringUnnotified(30) as $c) {
            $contractId = (int) $c['id'];
            $employeeId = (int) $c['employee_id'];
            $empEmail   = trim((string) ($c['employee_email'] ?? ''));
            $recipients = array_filter(array_unique([$hrEmail, $empEmail]));

            $subject = 'Contract Expiry Reminder — ' . $c['employee_name'];
            $html    = $this->buildExpiringSoonHtml($c);
            $allOk   = true;

            foreach ($recipients as $addr) {
                $ok = $mailer->send($addr, '', $subject, $html);
                if (!$ok) {
                    $allOk = false;
                    $errors[] = "expiring_soon: failed sending to {$addr} for contract {$c['contract_number']}";
                }
            }

            if ($allOk && $recipients !== []) {
                $this->logSent($contractId, $employeeId, 'expiring_soon', implode(',', $recipients));
                $sent++;
            } else {
                $skipped++;
            }
        }

        foreach ($this->contractsJustExpiredUnnotified() as $c) {
            $contractId = (int) $c['id'];
            $employeeId = (int) $c['employee_id'];
            $recipients = array_filter([$hrEmail]);

            $subject = 'Contract Expired — ' . $c['employee_name'];
            $html    = $this->buildExpiredHtml($c);
            $allOk   = true;

            foreach ($recipients as $addr) {
                $ok = $mailer->send($addr, '', $subject, $html);
                if (!$ok) {
                    $allOk = false;
                    $errors[] = "expired: failed sending to {$addr} for contract {$c['contract_number']}";
                }
            }

            if ($allOk && $recipients !== []) {
                $this->logSent($contractId, $employeeId, 'expired', implode(',', $recipients));
                $sent++;
            } else {
                $skipped++;
            }
        }

        return ['sent' => $sent, 'skipped' => $skipped, 'errors' => $errors];
    }

    /**
     * Send a "contract renewed" notification immediately.
     * Called right after a renewal is committed.
     */
    public function dispatchRenewal(int $newContractId, string $employeeName, string $employeeNumber, string $employeeEmail): void
    {
        if ($this->alreadySent($newContractId, 'renewed')) {
            return;
        }

        $mailer  = $this->buildMailer();
        $hrEmail = $mailer->hrEmail();

        $contract = $this->find($newContractId);
        if (!$contract) {
            $stmt = $this->db->prepare('SELECT * FROM employee_contracts WHERE id = :id LIMIT 1');
            $stmt->execute(['id' => $newContractId]);
            $contract = $stmt->fetch() ?: null;
        }
        if (!$contract) {
            return;
        }

        $subject    = 'Contract Renewed — ' . $employeeName;
        $html       = $this->buildRenewedHtml($contract, $employeeName, $employeeNumber);
        $recipients = array_filter(array_unique([$hrEmail, trim($employeeEmail)]));
        $employeeId = (int) $contract['employee_id'];

        foreach ($recipients as $addr) {
            $mailer->send($addr, '', $subject, $html);
        }

        if ($recipients !== []) {
            $this->logSent($newContractId, $employeeId, 'renewed', implode(',', $recipients));
        }
    }
}
