<?php
declare(strict_types=1);

namespace App\Core;

final class ChaosRepository
{
    public static function listAllRules(): array
    {
        $pdo = DB::pdo();
        $st = $pdo->query(
            "SELECT id, name, rule_type, config_json, is_active, created_at
             FROM chaos_rules
             ORDER BY is_active DESC, id ASC"
        );

        $rows = [];
        while ($r = $st->fetch()) {
            $r['config'] = self::decodeConfig((string)$r['config_json']);
            $rows[] = $r;
        }
        return $rows;
    }

    public static function enabledRuleIdsForCouple(int $coupleId): array
    {
        $pdo = DB::pdo();
        $st = $pdo->prepare(
            "SELECT rule_id
             FROM couple_chaos
             WHERE couple_id = :cid AND is_enabled = 1"
        );
        $st->execute([':cid' => $coupleId]);
        $ids = [];
        while ($r = $st->fetch()) {
            $ids[] = (int)$r['rule_id'];
        }
        return $ids;
    }

    public static function listEnabledRulesForCouple(int $coupleId): array
    {
        $pdo = DB::pdo();
        $st = $pdo->prepare(
            "SELECT cr.id, cr.name, cr.rule_type, cr.config_json
             FROM couple_chaos cc
             JOIN chaos_rules cr ON cr.id = cc.rule_id
             WHERE cc.couple_id = :cid
               AND cc.is_enabled = 1
               AND cr.is_active = 1
             ORDER BY cr.id ASC"
        );
        $st->execute([':cid' => $coupleId]);

        $rows = [];
        while ($r = $st->fetch()) {
            $r['config'] = self::decodeConfig((string)$r['config_json']);
            $rows[] = $r;
        }
        return $rows;
    }

    public static function toggleCoupleRule(int $coupleId, int $ruleId, bool $enable): void
    {
        $pdo = DB::pdo();

        // Ensure row exists
        $st = $pdo->prepare(
            "INSERT INTO couple_chaos (couple_id, rule_id, is_enabled)
             VALUES (:cid, :rid, :en)
             ON DUPLICATE KEY UPDATE is_enabled = VALUES(is_enabled)"
        );
        $st->execute([
            ':cid' => $coupleId,
            ':rid' => $ruleId,
            ':en'  => $enable ? 1 : 0,
        ]);
    }

    private static function decodeConfig(string $json): array
    {
        $cfg = json_decode($json, true);
        return is_array($cfg) ? $cfg : [];
    }
}
