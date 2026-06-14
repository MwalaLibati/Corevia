<?php

declare(strict_types=1);

class LoginAttempt extends Model
{
    protected string $table = 'login_attempts';

    public function isLocked(string $area, string $identifier, ?string $ipAddress = null, int $maxFailures = 5, int $windowMinutes = 15): bool
    {
        return $this->recentFailures($area, $identifier, $ipAddress, $windowMinutes) >= $maxFailures;
    }

    public function remainingLockMinutes(string $area, string $identifier, ?string $ipAddress = null, int $windowMinutes = 15): int
    {
        $ipSql = $ipAddress === null ? '' : ' AND ip_address = :ip_address';
        $stmt = $this->db->prepare(
            'SELECT attempted_at
             FROM login_attempts
             WHERE login_area = :area
               AND identifier = :identifier
               AND success = 0
               AND attempted_at >= DATE_SUB(NOW(), INTERVAL :minutes MINUTE)
               ' . $ipSql . '
             ORDER BY attempted_at ASC
             LIMIT 1'
        );
        $stmt->bindValue(':area', $area);
        $stmt->bindValue(':identifier', $identifier);
        if ($ipAddress !== null) {
            $stmt->bindValue(':ip_address', $ipAddress);
        }
        $stmt->bindValue(':minutes', $windowMinutes, PDO::PARAM_INT);
        $stmt->execute();
        $first = $stmt->fetchColumn();

        if (!$first) {
            return 0;
        }

        $unlockAt = strtotime((string) $first . " +{$windowMinutes} minutes");
        return max(1, (int) ceil(($unlockAt - time()) / 60));
    }

    public function record(string $area, string $identifier, bool $success, ?string $ipAddress = null): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO login_attempts (login_area, identifier, ip_address, success, attempted_at)
             VALUES (:area, :identifier, :ip_address, :success, NOW())'
        );
        $stmt->execute([
            'area' => $area,
            'identifier' => $identifier,
            'ip_address' => $ipAddress,
            'success' => $success ? 1 : 0,
        ]);
    }

    public function clearFailures(string $area, string $identifier, ?string $ipAddress = null): void
    {
        $sql = 'DELETE FROM login_attempts WHERE login_area = :area AND identifier = :identifier AND success = 0';
        $params = ['area' => $area, 'identifier' => $identifier];

        if ($ipAddress !== null) {
            $sql .= ' AND ip_address = :ip_address';
            $params['ip_address'] = $ipAddress;
        }

        $this->db->prepare($sql)->execute($params);
    }

    private function recentFailures(string $area, string $identifier, ?string $ipAddress, int $windowMinutes): int
    {
        $ipSql = $ipAddress === null ? '' : ' AND ip_address = :ip_address';
        $stmt = $this->db->prepare(
            'SELECT COUNT(*)
             FROM login_attempts
             WHERE login_area = :area
               AND identifier = :identifier
               AND success = 0
               AND attempted_at >= DATE_SUB(NOW(), INTERVAL :minutes MINUTE)
               ' . $ipSql
        );
        $stmt->bindValue(':area', $area);
        $stmt->bindValue(':identifier', $identifier);
        if ($ipAddress !== null) {
            $stmt->bindValue(':ip_address', $ipAddress);
        }
        $stmt->bindValue(':minutes', $windowMinutes, PDO::PARAM_INT);
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }
}
