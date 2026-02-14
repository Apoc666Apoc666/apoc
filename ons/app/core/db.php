<?php
declare(strict_types=1);

namespace App\Core;

use PDO;

final class DB
{
    private static ?PDO $pdo = null;

    public static function init(array $config): void
    {
        if (self::$pdo) return;

        $db = $config['db'];
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            $db['host'],
            $db['name'],
            $db['charset'] ?? 'utf8mb4'
        );

        self::$pdo = new PDO($dsn, $db['user'], $db['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }

    public static function pdo(): PDO
    {
        if (!self::$pdo) {
            throw new \RuntimeException('DB not initialized');
        }
        return self::$pdo;
    }
}
