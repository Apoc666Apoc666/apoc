<?php
declare(strict_types=1);

return [
    'app' => [
        'env' => 'dev', // 'dev' of 'prod'
        'base_path' => '/ons', // jouw webroot pad
        'name' => 'Ons',
        'timezone' => 'Europe/Amsterdam',
    ],

    'db' => [
        'host' => 'database-5019711839.webspace-host.com',
        'name' => 'dbs15320414',
        'user' => 'dbu3758463',
        'pass' => '!Aa0646143636',
        'charset' => 'utf8mb4',
    ],

    'security' => [
        'session_name' => 'ONSSESSID',
        'cookie_secure' => true,     // true op https
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',  // 'Strict' kan, maar Lax is vaak praktischer
        'csrf_ttl' => 7200,

        // Voor AI HMAC signing (delen met AI-service)
        'ai_hmac_secret' => 'CHANGE_ME_TO_RANDOM_64_BYTES_BASE64',
        'ai_base_url' => 'https://<thuisdomein>/ai',

        // Brute force / rate limiting (later uitbreiden met DB-backed)
        'rate_limit' => [
            'login_per_ip_per_15m' => 20,
        ],
    ],

    'paths' => [
        'root' => dirname(__DIR__, 2),
        'storage' => dirname(__DIR__, 2) . '/app/storage',
        'logs' => dirname(__DIR__, 2) . '/app/storage/logs',
    ],
];
