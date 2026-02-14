<?php
declare(strict_types=1);

namespace App\Core;

final class Env
{
    private static array $config;

    public static function config(): array
    {
        if (!isset(self::$config)) {
            self::$config = require dirname(__DIR__) . '/config/config.php';
        }
        return self::$config;
    }
}
