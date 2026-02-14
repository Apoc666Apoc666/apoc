<?php
declare(strict_types=1);

namespace App\Core;

final class UserRepository
{
    public static function normalizeEmail(string $email): string
    {
        $email = trim($email);
        $email = mb_strtolower($email, 'UTF-8');
        return $email;
    }

    public static function findByEmailNorm(string $emailNorm): ?array
    {
        $pdo = DB::pdo();
        $st = $pdo->prepare('SELECT * FROM users WHERE email_norm = :e LIMIT 1');
        $st->execute([':e' => $emailNorm]);
        $row = $st->fetch();
        return $row ?: null;
    }

    public static function findById(int $id): ?array
    {
        $pdo = DB::pdo();
        $st = $pdo->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $st->execute([':id' => $id]);
        $row = $st->fetch();
        return $row ?: null;
    }

    public static function create(string $email, string $password, string $displayName): int
    {
        $pdo = DB::pdo();

        $emailNorm = self::normalizeEmail($email);
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $st = $pdo->prepare(
            'INSERT INTO users (email, email_norm, password_hash, display_name)
             VALUES (:email, :email_norm, :ph, :dn)'
        );
        $st->execute([
            ':email' => $email,
            ':email_norm' => $emailNorm,
            ':ph' => $hash,
            ':dn' => $displayName,
        ]);

        $userId = (int)$pdo->lastInsertId();

        // init profile
        $st2 = $pdo->prepare('INSERT INTO user_profile (user_id) VALUES (:uid)');
        $st2->execute([':uid' => $userId]);

        return $userId;
    }

    public static function updateLastLogin(int $userId): void
    {
        $pdo = DB::pdo();
        $st = $pdo->prepare('UPDATE users SET last_login_at = CURRENT_TIMESTAMP(6) WHERE id = :id');
        $st->execute([':id' => $userId]);
    }
}
