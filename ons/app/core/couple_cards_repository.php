<?php
declare(strict_types=1);

namespace App\Core;

final class CoupleCardsRepository
{
    public static function countsByState(int $coupleId): array
    {
        $pdo = DB::pdo();
        $st = $pdo->prepare(
            "SELECT state, COUNT(*) AS c
             FROM couple_cards
             WHERE couple_id = :cid
             GROUP BY state"
        );
        $st->execute([':cid' => $coupleId]);
        $out = ['in_deck' => 0, 'drawn' => 0, 'completed' => 0, 'retired' => 0];
        while ($r = $st->fetch()) {
            $out[(string)$r['state']] = (int)$r['c'];
        }
        return $out;
    }

    public static function ensureDeckInitialized(int $coupleId): int
    {
        // Inserts missing couple_cards rows for all active cards
        $pdo = DB::pdo();

        $activeIds = CardRepository::allActiveIds();
        if (empty($activeIds)) return 0;

        // existing
        $st = $pdo->prepare("SELECT card_id FROM couple_cards WHERE couple_id = :cid");
        $st->execute([':cid' => $coupleId]);
        $existing = [];
        while ($r = $st->fetch()) {
            $existing[(int)$r['card_id']] = true;
        }

        $inserted = 0;
        $ins = $pdo->prepare(
            "INSERT INTO couple_cards (couple_id, card_id, state) VALUES (:cid, :card_id, 'in_deck')"
        );

        foreach ($activeIds as $id) {
            if (!isset($existing[$id])) {
                $ins->execute([':cid' => $coupleId, ':card_id' => $id]);
                $inserted++;
            }
        }

        return $inserted;
    }

    public static function getCurrentDrawn(int $coupleId): ?array
    {
        $pdo = DB::pdo();
        $st = $pdo->prepare(
            "SELECT cc.*, c.title, c.body, c.difficulty
             FROM couple_cards cc
             JOIN cards c ON c.id = cc.card_id
             WHERE cc.couple_id = :cid AND cc.state = 'drawn'
             ORDER BY cc.drawn_at DESC, cc.card_id DESC
             LIMIT 1"
        );
        $st->execute([':cid' => $coupleId]);
        $row = $st->fetch();
        return $row ?: null;
    }

    public static function drawRandomFromDeck(int $coupleId, int $drawnByUserId): ?array
    {
        $pdo = DB::pdo();

        // 1) Als er al een drawn kaart is: return die (no-op)
        $current = self::getCurrentDrawn($coupleId);
        if ($current) return $current;

        // 2) Kandidaten uit deck (limiet voor performance)
        $st = $pdo->prepare(
            "SELECT card_id
             FROM couple_cards
             WHERE couple_id = :cid AND state = 'in_deck'
             ORDER BY card_id ASC
             LIMIT 500"
        );
        $st->execute([':cid' => $coupleId]);

        $ids = [];
        while ($r = $st->fetch()) {
            $ids[] = (int)$r['card_id'];
        }
        if (empty($ids)) return null;

        // Pseudo-random keuze in PHP (sneller dan ORDER BY RAND)
        $pick = $ids[random_int(0, count($ids) - 1)];

        // 3) Update naar drawn (transaction safe: caller moet TX doen)
        $up = $pdo->prepare(
            "UPDATE couple_cards
             SET state='drawn', drawn_by_user_id=:uid, drawn_at=CURRENT_TIMESTAMP(6)
             WHERE couple_id=:cid AND card_id=:card_id AND state='in_deck'"
        );
        $up->execute([':uid' => $drawnByUserId, ':cid' => $coupleId, ':card_id' => $pick]);

        if ($up->rowCount() !== 1) {
            // Race condition: iemand anders trok tegelijk
            return self::getCurrentDrawn($coupleId);
        }

        return self::getCurrentDrawn($coupleId);
    }

    public static function returnToDeck(int $coupleId, int $cardId): bool
    {
        $pdo = DB::pdo();
        $st = $pdo->prepare(
            "UPDATE couple_cards
             SET state='in_deck', drawn_by_user_id=NULL, drawn_at=NULL
             WHERE couple_id=:cid AND card_id=:card_id AND state='drawn'"
        );
        $st->execute([':cid' => $coupleId, ':card_id' => $cardId]);
        return $st->rowCount() === 1;
    }

    public static function complete(int $coupleId, int $cardId, int $points): bool
    {
        $points = max(0, min(1000, $points));
        $pdo = DB::pdo();
        $st = $pdo->prepare(
            "UPDATE couple_cards
             SET state='completed', completed_at=CURRENT_TIMESTAMP(6), points_awarded=:p
             WHERE couple_id=:cid AND card_id=:card_id AND state='drawn'"
        );
        $st->execute([':cid' => $coupleId, ':card_id' => $cardId, ':p' => $points]);
        return $st->rowCount() === 1;
    }
}
