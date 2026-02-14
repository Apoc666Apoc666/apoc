<?php
declare(strict_types=1);

namespace App\Core;

final class ProgressService
{
    /**
     * XP model (simpel, later uitbreiden):
     * points = difficulty * 10 (default) of user override (0..1000)
     * level = floor(xp / 100) + 1
     */
    public static function addXp(int $coupleId, int $xpToAdd): array
    {
        $xpToAdd = max(0, min(100000, $xpToAdd));
        $pdo = DB::pdo();

        // Lock row (transaction expected by caller)
        $st = $pdo->prepare("SELECT xp, level FROM couple_progress WHERE couple_id = :cid FOR UPDATE");
        $st->execute([':cid' => $coupleId]);
        $row = $st->fetch();

        if (!$row) {
            // Should exist, but just in case:
            $pdo->prepare("INSERT INTO couple_progress (couple_id, xp, level) VALUES (:cid, 0, 1)")
                ->execute([':cid' => $coupleId]);
            $row = ['xp' => 0, 'level' => 1];
        }

        $oldXp = (int)$row['xp'];
        $oldLevel = (int)$row['level'];

        $newXp = $oldXp + $xpToAdd;
        $newLevel = intdiv($newXp, 100) + 1;

        $up = $pdo->prepare("UPDATE couple_progress SET xp = :xp, level = :lvl WHERE couple_id = :cid");
        $up->execute([':xp' => $newXp, ':lvl' => $newLevel, ':cid' => $coupleId]);

        return [
            'old_xp' => $oldXp,
            'new_xp' => $newXp,
            'old_level' => $oldLevel,
            'new_level' => $newLevel,
            'level_up' => $newLevel > $oldLevel,
        ];
    }
}
