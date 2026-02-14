<?php
declare(strict_types=1);

namespace App\Core;

final class Csrf
{
    private static int $ttl = 7200;

    public static function init(array $config): void
    {
        self::$ttl = (int)($config['security']['csrf_ttl'] ?? 7200);
        if (!isset($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = [];
        }
    }

    public static function token(string $key = 'default'): string
    {
        $now = time();
        self::gc($now);

        $token = bin2hex(random_bytes(32));
        $_SESSION['_csrf'][$key] = ['t' => $token, 'ts' => $now];
        return $token;
    }

    public static function verify(string $token, string $key = 'default'): bool
    {
        if (!isset($_SESSION['_csrf'][$key]['t'], $_SESSION['_csrf'][$key]['ts'])) {
            return false;
        }
        $stored = $_SESSION['_csrf'][$key]['t'];
        $ts = (int)$_SESSION['_csrf'][$key]['ts'];

        if (time() - $ts > self::$ttl) {
            unset($_SESSION['_csrf'][$key]);
            return false;
        }

        $ok = hash_equals($stored, $token);
        if ($ok) {
            unset($_SESSION['_csrf'][$key]); // one-time
        }
        return $ok;
    }

    private static function gc(int $now): void
    {
        foreach ($_SESSION['_csrf'] as $k => $v) {
            if (!isset($v['ts']) || ($now - (int)$v['ts']) > self::$ttl) {
                unset($_SESSION['_csrf'][$k]);
            }
        }
    }
}
