<?php

declare(strict_types=1);

/**
 * Lightweight SMTP/mail service.
 * Settings are injected as a key-value array loaded from the settings table.
 */
class MailService
{
    private string $lastError = '';

    public function __construct(private readonly array $settings) {}

    public function lastError(): string
    {
        return $this->lastError;
    }

    public function isEnabled(): bool
    {
        return (string) ($this->settings['email_notifications_enabled'] ?? '1') === '1';
    }

    /**
     * Send an HTML email. Returns true on success, false on failure.
     */
    public function send(string $toEmail, string $toName, string $subject, string $htmlBody, array $attachments = []): bool
    {
        $this->lastError = '';

        if (!$this->isEnabled() || $toEmail === '') {
            $this->lastError = !$this->isEnabled() ? 'Email sending is disabled.' : 'Recipient email address is missing.';
            return false;
        }

        $host       = trim((string) ($this->settings['smtp_host']       ?? ''));
        $port       = (int)         ($this->settings['smtp_port']       ?? 25);
        $encryption = strtolower(trim((string) ($this->settings['smtp_encryption'] ?? 'none')));
        $username   = trim((string) ($this->settings['smtp_username']   ?? ''));
        $password   = (string)      ($this->settings['smtp_password']   ?? '');
        $fromEmail  = trim((string) ($this->settings['smtp_from_email'] ?? 'noreply@system.local'));
        $fromName   = trim((string) ($this->settings['smtp_from_name']  ?? 'System'));

        if ($host === '') {
            return $this->sendViaMail($toEmail, $toName, $subject, $htmlBody, $fromEmail, $fromName, $attachments);
        }

        return $this->sendViaSmtp(
            $host, $port, $encryption, $username, $password,
            $fromEmail, $fromName, $toEmail, $toName, $subject, $htmlBody, $attachments
        );
    }

    // ─── SMTP implementation ──────────────────────────────────────────────────

    private function sendViaSmtp(
        string $host, int $port, string $encryption,
        string $username, string $password,
        string $fromEmail, string $fromName,
        string $toEmail, string $toName,
        string $subject, string $htmlBody,
        array $attachments = []
    ): bool {
        if (!$this->validEmail($toEmail) || !$this->validEmail($fromEmail)) {
            $this->lastError = 'Sender or recipient email address is invalid.';
            return false;
        }

        $toName = $this->cleanHeader($toName);
        $fromName = $this->cleanHeader($fromName);
        $subject = $this->cleanHeader($subject);

        $errNo  = 0;
        $errStr = '';
        $timeout = 15;

        $scheme = $encryption === 'ssl' ? 'ssl://' : '';
        $socket = @fsockopen($scheme . $host, $port, $errNo, $errStr, $timeout);

        if (!is_resource($socket)) {
            $this->lastError = "Could not connect to {$host}:{$port}. {$errStr}";
            error_log("MailService: could not connect to {$host}:{$port} — {$errStr}");
            return false;
        }

        stream_set_timeout($socket, $timeout);

        $read = fn() => (string) fgets($socket, 1024);
        $write = fn(string $cmd) => fputs($socket, $cmd . "\r\n");

        $greeting = $read();
        if (!str_starts_with($greeting, '2')) {
            $this->lastError = 'SMTP server rejected the connection.';
            fclose($socket);
            return false;
        }

        $write("EHLO {$host}");
        $this->readMultiline($socket);

        if ($encryption === 'tls') {
            $write('STARTTLS');
            $tls = $read();
            if (!str_starts_with($tls, '220')) {
                $this->lastError = 'SMTP server did not accept STARTTLS.';
                fclose($socket);
                return false;
            }
            if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                $this->lastError = 'Could not start TLS encryption for SMTP.';
                fclose($socket);
                return false;
            }
            $write("EHLO {$host}");
            $this->readMultiline($socket);
        }

        if ($username !== '') {
            $write('AUTH LOGIN');
            $read();
            $write(base64_encode($username));
            $read();
            $write(base64_encode($password));
            $authResp = $read();
            if (!str_starts_with($authResp, '235')) {
                $this->lastError = str_starts_with($authResp, '535')
                    ? 'Gmail rejected the SMTP username/password. Use a valid Gmail App Password for this account.'
                    : 'SMTP authentication failed.';
                error_log("MailService: AUTH failed — {$authResp}");
                fclose($socket);
                return false;
            }
        }

        $write("MAIL FROM:<{$fromEmail}>");
        $read();
        $write("RCPT TO:<{$toEmail}>");
        $rcptResp = $read();
        if (!str_starts_with($rcptResp, '250') && !str_starts_with($rcptResp, '251')) {
            $this->lastError = 'SMTP server rejected the recipient address.';
            error_log("MailService: RCPT failed — {$rcptResp}");
            fclose($socket);
            return false;
        }

        $write('DATA');
        $read();

        $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
        $toHeader       = $toName !== '' ? "{$toName} <{$toEmail}>" : $toEmail;
        $fromHeader     = $fromName !== '' ? "{$fromName} <{$fromEmail}>" : $fromEmail;
        $msgId          = '<' . uniqid('', true) . '@libarti>';

        $msg = $this->buildMimeMessage($fromHeader, $toHeader, $encodedSubject, $msgId, $htmlBody, $attachments);
        $msg .= "\r\n.\r\n";

        fputs($socket, $msg);
        $dataResp = $read();

        $write('QUIT');
        fclose($socket);

        if (!str_starts_with($dataResp, '250')) {
            $this->lastError = 'SMTP server rejected the email content.';
            return false;
        }

        return true;
    }

    private function readMultiline(mixed $socket): string
    {
        $response = '';
        while ($line = fgets($socket, 1024)) {
            $response .= $line;
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }
        return $response;
    }

    private function sendViaMail(
        string $toEmail, string $toName,
        string $subject, string $htmlBody,
        string $fromEmail, string $fromName,
        array $attachments = []
    ): bool {
        if (!$this->validEmail($toEmail) || !$this->validEmail($fromEmail)) {
            $this->lastError = 'Sender or recipient email address is invalid.';
            return false;
        }

        $toName = $this->cleanHeader($toName);
        $fromName = $this->cleanHeader($fromName);
        $subject = $this->cleanHeader($subject);

        $toHeader   = $toName !== '' ? "{$toName} <{$toEmail}>" : $toEmail;
        $fromHeader = $fromName !== '' ? "{$fromName} <{$fromEmail}>" : $fromEmail;
        $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';

        if ($attachments === []) {
            $headers = "From: {$fromHeader}\r\nContent-Type: text/html; charset=UTF-8\r\nMIME-Version: 1.0\r\n";
            return mail($toEmail, $encodedSubject, $htmlBody, $headers);
        }

        $boundary = '=_Corevia_' . bin2hex(random_bytes(12));
        $headers = "From: {$fromHeader}\r\nMIME-Version: 1.0\r\nContent-Type: multipart/mixed; boundary=\"{$boundary}\"\r\n";
        $message = $this->buildMultipartBody($boundary, $htmlBody, $attachments);
        return mail($toEmail, $encodedSubject, $message, $headers);
    }

    public function hrEmail(): string
    {
        return trim((string) ($this->settings['smtp_hr_email'] ?? ''));
    }

    private function validEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    private function cleanHeader(string $value): string
    {
        return trim(str_replace(["\r", "\n"], ' ', $value));
    }

    private function buildMimeMessage(string $fromHeader, string $toHeader, string $encodedSubject, string $msgId, string $htmlBody, array $attachments): string
    {
        $msg  = "From: {$fromHeader}\r\n";
        $msg .= "To: {$toHeader}\r\n";
        $msg .= "Subject: {$encodedSubject}\r\n";
        $msg .= "Message-ID: {$msgId}\r\n";
        $msg .= "MIME-Version: 1.0\r\n";

        if ($attachments === []) {
            $msg .= "Content-Type: text/html; charset=UTF-8\r\n";
            $msg .= "Content-Transfer-Encoding: base64\r\n\r\n";
            $msg .= chunk_split(base64_encode($htmlBody));
            return $msg;
        }

        $boundary = '=_Corevia_' . bin2hex(random_bytes(12));
        $msg .= "Content-Type: multipart/mixed; boundary=\"{$boundary}\"\r\n\r\n";
        $msg .= $this->buildMultipartBody($boundary, $htmlBody, $attachments);

        return $msg;
    }

    private function buildMultipartBody(string $boundary, string $htmlBody, array $attachments): string
    {
        $msg = "--{$boundary}\r\n";
        $msg .= "Content-Type: text/html; charset=UTF-8\r\n";
        $msg .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $msg .= chunk_split(base64_encode($htmlBody)) . "\r\n";

        foreach ($attachments as $attachment) {
            $filename = $this->cleanHeader((string) ($attachment['filename'] ?? 'document.html'));
            $mime = $this->cleanHeader((string) ($attachment['mime'] ?? 'application/octet-stream'));
            $content = (string) ($attachment['content'] ?? '');
            if ($filename === '' || $content === '') {
                continue;
            }

            $msg .= "--{$boundary}\r\n";
            $msg .= "Content-Type: {$mime}; name=\"{$filename}\"\r\n";
            $msg .= "Content-Transfer-Encoding: base64\r\n";
            $msg .= "Content-Disposition: attachment; filename=\"{$filename}\"\r\n\r\n";
            $msg .= chunk_split(base64_encode($content)) . "\r\n";
        }

        $msg .= "--{$boundary}--\r\n";
        return $msg;
    }
}
