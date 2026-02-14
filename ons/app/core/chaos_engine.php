<?php
declare(strict_types=1);

namespace App\Core;

final class ChaosEngine
{
    /**
     * Apply chaos before draw.
     * Returns:
     *  - allowed: bool
     *  - reason: string (if blocked)
     *  - reroll_once: bool (randomizer)
     */
    public static function beforeDraw(int $coupleId): array
    {
        $rules = ChaosRepository::listEnabledRulesForCouple($coupleId);

        // Defaults
        $result = [
            'allowed' => true,
            'reason' => '',
            'reroll_once' => false,
        ];

        foreach ($rules as $r) {
            $type = (string)$r['rule_type'];
            $cfg  = $r['config'] ?? [];

            if ($type === 'cooldown') {
                $hours = (int)($cfg['hours'] ?? 0);
                if ($hours > 0) {
                    $blocked = self::cooldownBlocksDraw($coupleId, $hours);
                    if ($blocked) {
                        $result['allowed'] = false;
                        $result['reason'] = "Cooldown actief: wacht {$hours} uur na afronden.";
                        return $result;
                    }
                }
            }

            if ($type === 'randomizer') {
                $chance = (float)($cfg['chance'] ?? 0.0); // 0..1
                $chance = max(0.0, min(1.0, $chance));
                if ($chance > 0) {
                    // fast RNG
                    $roll = random_int(0, 1000000) / 1000000;
                    if ($roll < $chance) {
                        $result['reroll_once'] = true;
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Apply chaos to points/xp on completion.
     * Input $basePoints (e.g. difficulty*10 or user points)
     * Returns:
     *  - points_final
     *  - adjustments: array of strings
     */
    public static function onComplete(int $coupleId, int $basePoints): array
    {
        $rules = ChaosRepository::listEnabledRulesForCouple($coupleId);

        $points = max(0, min(1000, $basePoints));
        $notes = [];

        foreach ($rules as $r) {
            $type = (string)$r['rule_type'];
            $cfg  = $r['config'] ?? [];

            if ($type === 'bonus') {
                // Either add points or multiply
                $add = (int)($cfg['add_points'] ?? 0);
                $mult = (float)($cfg['multiplier'] ?? 1.0);

                if ($mult > 0 && abs($mult - 1.0) > 0.0001) {
                    $new = (int)round($points * $mult);
                    $notes[] = $r['name'] . " (x" . rtrim(rtrim(sprintf('%.2f', $mult), '0'), '.') . ")";
                    $points = $new;
                }
                if ($add !== 0) {
                    $notes[] = $r['name'] . " (" . ($add > 0 ? '+' : '') . $add . ")";
                    $points += $add;
                }
            }

            if ($type === 'penalty') {
                $sub = (int)($cfg['sub_points'] ?? 0);
                $mult = (float)($cfg['multiplier'] ?? 1.0);

                if ($mult > 0 && abs($mult - 1.0) > 0.0001) {
                    $new = (int)round($points * $mult);
                    $notes[] = $r['name'] . " (x" . rtrim(rtrim(sprintf('%.2f', $mult), '0'), '.') . ")";
                    $points = $new;
                }
                if ($sub !== 0) {
                    $notes[] = $r['name'] . " (-" . abs($sub) . ")";
                    $points -= abs($sub);
                }
            }
        }

        $points = max(0, min(1000, $points));

        return [
            'points_final' => $points,
            'adjustments' => $notes,
        ];
    }

    private static function cooldownBlocksDraw(int $coupleId, int $hours): bool
    {
        $pdo = DB::pdo();

        // We use couple_cards completed_at as truth
        $st = $pdo->prepare(
            "SELECT MAX(completed_at) AS last_completed
             FROM couple_cards
             WHERE couple_id = :cid AND completed_at IS NOT NULL"
        );
        $st->execute([':cid' => $coupleId]);
        $row = $st->fetch();

        $last = $row ? (string)($row['last_completed'] ?? '') : '';
        if ($last === '') return false;

        // MySQL datetime(6) string parse
        $lastTs = strtotime(substr($last, 0, 19) . ' UTC');
        if ($lastTs === false) return false;

        $cooldownUntil = $lastTs + ($hours * 3600);
        return time() < $cooldownUntil;
    }
}
