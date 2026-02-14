<?php
declare(strict_types=1);

namespace App\Core;

final class RateLimit
{
    public static function hit(string $key, int $limit, int $windowSeconds): bool
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $now = time();
        $bucketKey = "_rl:$key:$ip";

        if (!isset($_SESSION[$bucketKey])) {
            $_SESSION[$bucketKey] = ['ts' => $now, 'c' => 0];
        }

        $ts = (int)$_SESSION[$bucketKey]['ts'];
        $c  = (int)$_SESSION[$bucketKey]['c'];

        if ($now - $ts >= $windowSeconds) {
            $_SESSION[$bucketKey] = ['ts' => $now, 'c' => 1];
            return true;
        }

        if ($c >= $limit) {
            return false;
        }

        $_SESSION[$bucketKey]['c'] = $c + 1;
        return true;
    }
}
