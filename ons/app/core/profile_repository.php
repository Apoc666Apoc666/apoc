<?php
declare(strict_types=1);

namespace App\Core;

final class ProfileRepository
{
    public static function get(int $userId): array
    {
        $pdo = DB::pdo();

        $st = $pdo->prepare(
            "SELECT u.id, u.email, u.display_name, p.bio, p.timezone, p.locale
             FROM users u
             LEFT JOIN user_profile p ON p.user_id = u.id
             WHERE u.id = :id
             LIMIT 1"
        );
        $st->execute([':id' => $userId]);
        $row = $st->fetch();
        return $row ?: [];
    }

    public static function update(int $userId, string $displayName, ?string $bio): void
    {
        $pdo = DB::pdo();
        $pdo->beginTransaction();
        try {
            $st1 = $pdo->prepare("UPDATE users SET display_name = :dn WHERE id = :id");
            $st1->execute([':dn' => $displayName, ':id' => $userId]);

            $st2 = $pdo->prepare("UPDATE user_profile SET bio = :bio WHERE user_id = :id");
            $st2->execute([':bio' => $bio, ':id' => $userId]);

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
