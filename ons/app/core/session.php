<?php
declare(strict_types=1);

namespace App\Core;

final class Session
{
    public static function start(array $config): void
    {
        $sec = $config['security'];

        session_name($sec['session_name'] ?? 'PHPSESSID');

        $params = session_get_cookie_params();
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => $params['path'] ?? '/',
            'domain' => $params['domain'] ?? '',
            'secure' => (bool)($sec['cookie_secure'] ?? true),
            'httponly' => (bool)($sec['cookie_httponly'] ?? true),
            'samesite' => $sec['cookie_samesite'] ?? 'Lax',
        ]);

        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.cookie_httponly', '1');

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        // Basic fixation protection: regen bij eerste request
        if (empty($_SESSION['_init'])) {
            session_regenerate_id(true);
            $_SESSION['_init'] = time();
        }
    }

    public static function regenerate(): void
    {
        session_regenerate_id(true);
    }
}
