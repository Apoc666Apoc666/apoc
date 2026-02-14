<?php
declare(strict_types=1);

namespace App\Core;

final class CardRepository
{
    public static function allActiveIds(): array
    {
        $pdo = DB::pdo();
        $st = $pdo->query("SELECT id FROM cards WHERE is_active = 1 ORDER BY id ASC");
        $ids = [];
        while ($r = $st->fetch()) {
            $ids[] = (int)$r['id'];
        }
        return $ids;
    }

    public static function getById(int $cardId): ?array
    {
        $pdo = DB::pdo();
        $st = $pdo->prepare("SELECT * FROM cards WHERE id = :id AND is_active = 1 LIMIT 1");
        $st->execute([':id' => $cardId]);
        $row = $st->fetch();
        return $row ?: null;
    }
}
