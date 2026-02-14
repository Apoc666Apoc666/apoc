<?php
declare(strict_types=1);

namespace App\Core;

final class MoodRepository
{
    public static function set(int $coupleId, int $userId, string $mood, ?string $note, int $ttlMinutes): void
    {
        $ttlMinutes = max(5, min(1440, $ttlMinutes)); // 5 min .. 24 uur
        $expiresAt = (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))
            ->modify('+' . $ttlMinutes . ' minutes')
            ->format('Y-m-d H:i:s.u');

        $pdo = DB::pdo();
        $st = $pdo->prepare(
            "INSERT INTO mood_status (couple_id, user_id, mood, note, expires_at)
             VALUES (:cid, :uid, :m, :n, :exp)"
        );
        $st->execute([
            ':cid' => $coupleId,
            ':uid' => $userId,
            ':m' => $mood,
            ':n' => $note,
            ':exp' => $expiresAt,
        ]);
    }

    /**
     * Return latest not-expired mood per member in couple.
     */
    public static function activeForCouple(int $coupleId): array
{
    $pdo = DB::pdo();

    // Gebruik 2 verschillende placeholders (PDO/MySQL compat met emulate_prepares=false)
    $st = $pdo->prepare(
        "SELECT ms.*
         FROM mood_status ms
         JOIN (
           SELECT user_id, MAX(id) AS max_id
           FROM mood_status
           WHERE couple_id = :cid1 AND expires_at > CURRENT_TIMESTAMP(6)
           GROUP BY user_id
         ) t ON t.max_id = ms.id
         WHERE ms.couple_id = :cid2
         ORDER BY ms.user_id ASC"
    );

    $st->execute([':cid1' => $coupleId, ':cid2' => $coupleId]);

    $rows = [];
    while ($r = $st->fetch()) {
        $rows[] = $r;
    }
    return $rows;
    }

}
