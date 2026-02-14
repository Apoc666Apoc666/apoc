<?php
declare(strict_types=1);

namespace App\Core;

final class CoupleRepository
{
    public static function activeCoupleIdForUser(int $userId): ?int
    {
        $pdo = DB::pdo();
        $st = $pdo->prepare(
            "SELECT cm.couple_id
             FROM couple_members cm
             JOIN couples c ON c.id = cm.couple_id
             WHERE cm.user_id = :uid
               AND cm.left_at IS NULL
               AND c.status = 'active'
             ORDER BY cm.joined_at DESC
             LIMIT 1"
        );
        $st->execute([':uid' => $userId]);
        $row = $st->fetch();
        return $row ? (int)$row['couple_id'] : null;
    }

    public static function coupleMemberUserIds(int $coupleId): array
    {
        $pdo = DB::pdo();
        $st = $pdo->prepare(
            "SELECT user_id
             FROM couple_members
             WHERE couple_id = :cid AND left_at IS NULL"
        );
        $st->execute([':cid' => $coupleId]);
        $ids = [];
        while ($r = $st->fetch()) {
            $ids[] = (int)$r['user_id'];
        }
        return $ids;
    }

    /**
     * Create couple + attach 2 users.
     * Let op: deze functie start GEEN transactie.
     * De caller (bijv. acceptSubmit) kan dit in een outer transaction doen.
     */
    public static function createCoupleWithUsers(int $userA, int $userB): int
    {
        $pdo = DB::pdo();

        $pdo->exec("INSERT INTO couples (status) VALUES ('active')");
        $coupleId = (int)$pdo->lastInsertId();

        $st = $pdo->prepare("INSERT INTO couple_members (couple_id, user_id) VALUES (:cid, :uid)");
        $st->execute([':cid' => $coupleId, ':uid' => $userA]);
        $st->execute([':cid' => $coupleId, ':uid' => $userB]);

        // init progress row
        $st2 = $pdo->prepare("INSERT INTO couple_progress (couple_id, xp, level) VALUES (:cid, 0, 1)");
        $st2->execute([':cid' => $coupleId]);

        return $coupleId;
    }

    public static function activeCoupleIdForUser(int $userId): ?int
    {
        $db = Db::pdo();
        $sql = "SELECT cm.couple_id
            FROM couple_members cm
            JOIN couples c ON c.id = cm.couple_id
            WHERE cm.user_id = :uid
              AND cm.left_at IS NULL
              AND c.status = 'active'
            LIMIT 1";
        $st = $db->prepare($sql);
        $st->execute([':uid' => $userId]);
        $cid = $st->fetchColumn();
        return $cid ? (int)$cid : null;
    }

}
