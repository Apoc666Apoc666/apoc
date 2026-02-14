<?php
declare(strict_types=1);

// TEMP DEBUG (remove after fix)
if (($_SERVER['REMOTE_ADDR'] ?? '') === '87.208.86.80') {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);

    ini_set('log_errors', '1');
    ini_set('error_log', __DIR__ . '/../storage/logs/php-error.log');
}

namespace App\Core;

require_once __DIR__ . '/env.php';
require_once __DIR__ . '/error.php';
require_once __DIR__ . '/response.php';
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/user_repository.php';
require_once __DIR__ . '/auth_attempts.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/view.php';
require_once __DIR__ . '/router.php';
require_once __DIR__ . '/hmac.php';
require_once __DIR__ . '/rate_limit.php';
require_once __DIR__ . '/couple_repository.php';
require_once __DIR__ . '/profile_repository.php';
require_once __DIR__ . '/mood_repository.php';
require_once __DIR__ . '/event_log.php';
require_once __DIR__ . '/couple_invite_repository.php';
require_once __DIR__ . '/card_repository.php';
require_once __DIR__ . '/couple_cards_repository.php';
require_once __DIR__ . '/progress_service.php';
require_once __DIR__ . '/chaos_repository.php';
require_once __DIR__ . '/chaos_engine.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/gallery_repository.php';


// PSR-4 light autoloader (geen Composer)
spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $file = dirname(__DIR__) . '/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($file)) {
        require_once $file;
    }
});

$config = Env::config();
date_default_timezone_set($config['app']['timezone'] ?? 'UTC');

ErrorHandler::init($config['app']['env'] ?? 'prod');

Security::applyHeaders();
Session::start($config);
Csrf::init($config);
DB::init($config);
Response::init();
