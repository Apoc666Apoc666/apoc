<?php
declare(strict_types=1);

namespace App\Core;

final class AuthAttempts
{
    private static function ipBin(): string
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $bin = @inet_pton($ip);
        if ($bin === false) {
            // fallback 16 bytes
            return str_repeat("\0", 16);
        }
        // inet_pton can be 4 bytes for IPv4; pad to 16 for consistent VARBINARY(16)
        return str_pad($bin, 16, "\0", STR_PAD_LEFT);
    }

    public static function log(?string $emailNorm, bool $success): void
    {
        $pdo = DB::pdo();
        $st = $pdo->prepare(
            'INSERT INTO auth_attempts (ip, email_norm, success) VALUES (:ip, :e, :s)'
        );
        $st->execute([
            ':ip' => self::ipBin(),
            ':e' => $emailNorm,
            ':s' => $success ? 1 : 0,
        ]);
    }

    /**
     * True = allowed, False = blocked
     */
    public static function allowed(int $maxAttempts, int $windowSeconds, ?string $emailNorm): bool
    {
        $pdo = DB::pdo();

        $st = $pdo->prepare(
            'SELECT COUNT(*) AS c
             FROM auth_attempts
             WHERE ip = :ip
               AND created_at >= (CURRENT_TIMESTAMP(6) - INTERVAL :sec SECOND)
               AND success = 0'
        );
        // MySQL doesn't allow binding interval directly in all configs; use integer interpolation safely:
        // We keep it safe by casting to int and injecting as literal.
        $sec = (int)$windowSeconds;

        $sql = str_replace(':sec', (string)$sec, $st->queryString);
        $st2 = $pdo->prepare($sql);
        $st2->execute([':ip' => self::ipBin()]);
        $row = $st2->fetch();
        $count = (int)($row['c'] ?? 0);

        if ($count >= $maxAttempts) return false;

        // Optional extra: email-based throttling if provided
        if ($emailNorm !== null && $emailNorm !== '') {
            $stE = $pdo->prepare(
                'SELECT COUNT(*) AS c
                 FROM auth_attempts
                 WHERE email_norm = :e
                   AND created_at >= (CURRENT_TIMESTAMP(6) - INTERVAL ' . $sec . ' SECOND)
                   AND success = 0'
            );
            $stE->execute([':e' => $emailNorm]);
            $rowE = $stE->fetch();
            $countE = (int)($rowE['c'] ?? 0);
            if ($countE >= $maxAttempts) return false;
        }

        return true;
    }
}
