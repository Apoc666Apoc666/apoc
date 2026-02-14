<?php
declare(strict_types=1);

namespace App\Core;

final class EventLog
{
    public static function add(?int $coupleId, ?int $actorUserId, string $type, array $data): void
    {
        $pdo = DB::pdo();
        $st = $pdo->prepare(
            "INSERT INTO events (couple_id, actor_user_id, event_type, data_json)
             VALUES (:cid, :uid, :t, :j)"
        );
        $st->execute([
            ':cid' => $coupleId,
            ':uid' => $actorUserId,
            ':t' => $type,
            ':j' => json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        ]);
    }
}
