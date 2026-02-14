<?php
declare(strict_types=1);

namespace App\Modules\Cards;

use App\Core\Auth;
use App\Core\ChaosEngine;
use App\Core\CoupleRepository;
use App\Core\CoupleCardsRepository;
use App\Core\Csrf;
use App\Core\DB;
use App\Core\EventLog;
use App\Core\ProgressService;
use App\Core\View;

final class Controller
{
    public function show(): void
    {
        Auth::requireLogin();
        $uid = (int)Auth::id();

        $coupleId = CoupleRepository::activeCoupleIdForUser($uid);
        if (!$coupleId) {
            View::render('cards', [
                'error' => 'Je zit nog niet in een koppel.',
                'msg' => '',
                'couple_id' => null,
                'counts' => [],
                'current' => null,
                'csrf' => [
                    'init' => Csrf::token('cards_init'),
                    'draw' => Csrf::token('cards_draw'),
                    'return' => Csrf::token('cards_return'),
                    'complete' => Csrf::token('cards_complete'),
                ],
            ], 400);
            return;
        }

        $counts = CoupleCardsRepository::countsByState($coupleId);
        $current = CoupleCardsRepository::getCurrentDrawn($coupleId);

        View::render('cards', [
            'error' => '',
            'msg' => '',
            'couple_id' => $coupleId,
            'counts' => $counts,
            'current' => $current,
            'csrf' => [
                'init' => Csrf::token('cards_init'),
                'draw' => Csrf::token('cards_draw'),
                'return' => Csrf::token('cards_return'),
                'complete' => Csrf::token('cards_complete'),
            ],
        ]);
    }

    public function initDeck(): void
    {
        Auth::requireLogin();
        $uid = (int)Auth::id();

        if (!Csrf::verify((string)($_POST['csrf'] ?? ''), 'cards_init')) {
            $this->renderWithMessage($uid, '', 'Ongeldige sessie (CSRF).', 400);
            return;
        }

        $coupleId = CoupleRepository::activeCoupleIdForUser($uid);
        if (!$coupleId) {
            $this->renderWithMessage($uid, '', 'Je zit nog niet in een koppel.', 400);
            return;
        }

        $inserted = CoupleCardsRepository::ensureDeckInitialized($coupleId);

        EventLog::add($coupleId, $uid, 'cards.deck.init', [
            'inserted' => $inserted,
        ]);

        $this->renderWithMessage($uid, "Deck geïnitialiseerd (+{$inserted}).", '', 200);
    }

    public function draw(): void
    {
        Auth::requireLogin();
        $uid = (int)Auth::id();

        if (!Csrf::verify((string)($_POST['csrf'] ?? ''), 'cards_draw')) {
            $this->renderWithMessage($uid, '', 'Ongeldige sessie (CSRF).', 400);
            return;
        }

        $coupleId = CoupleRepository::activeCoupleIdForUser($uid);
        if (!$coupleId) {
            $this->renderWithMessage($uid, '', 'Je zit nog niet in een koppel.', 400);
            return;
        }

        $pdo = DB::pdo();
        $pdo->beginTransaction();
        try {
            // Zorg dat deck bestaat
            CoupleCardsRepository::ensureDeckInitialized($coupleId);

            // Chaos: before draw (cooldown/randomizer)
            $chaos = ChaosEngine::beforeDraw($coupleId);
            if (!$chaos['allowed']) {
                EventLog::add($coupleId, $uid, 'chaos.block.draw', [
                    'reason' => $chaos['reason'],
                ]);
                $pdo->commit();
                $this->renderWithMessage($uid, '', $chaos['reason'], 409);
                return;
            }

            $card = CoupleCardsRepository::drawRandomFromDeck($coupleId, $uid);
            if (!$card) {
                $pdo->commit();
                $this->renderWithMessage($uid, '', 'Geen kaarten meer in deck.', 200);
                return;
            }

            // Chaos: randomizer reroll once (best-effort, max 1 extra draw)
            if (!empty($chaos['reroll_once'])) {
                $firstId = (int)$card['card_id'];

                // probeer terugleggen en opnieuw trekken
                CoupleCardsRepository::returnToDeck($coupleId, $firstId);
                $card2 = CoupleCardsRepository::drawRandomFromDeck($coupleId, $uid);

                if ($card2 && (int)$card2['card_id'] !== $firstId) {
                    EventLog::add($coupleId, $uid, 'chaos.randomizer.reroll', [
                        'from_card_id' => $firstId,
                        'to_card_id' => (int)$card2['card_id'],
                    ]);
                    $card = $card2;
                }
            }

            EventLog::add($coupleId, $uid, 'card.draw', [
                'card_id' => (int)$card['card_id'],
            ]);

            $pdo->commit();
            $this->renderWithMessage($uid, 'Kaart getrokken.', '', 200);
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function returnToDeck(): void
    {
        Auth::requireLogin();
        $uid = (int)Auth::id();

        if (!Csrf::verify((string)($_POST['csrf'] ?? ''), 'cards_return')) {
            $this->renderWithMessage($uid, '', 'Ongeldige sessie (CSRF).', 400);
            return;
        }

        $coupleId = CoupleRepository::activeCoupleIdForUser($uid);
        if (!$coupleId) {
            $this->renderWithMessage($uid, '', 'Je zit nog niet in een koppel.', 400);
            return;
        }

        $current = CoupleCardsRepository::getCurrentDrawn($coupleId);
        if (!$current) {
            $this->renderWithMessage($uid, '', 'Geen getrokken kaart om terug te leggen.', 400);
            return;
        }

        $pdo = DB::pdo();
        $pdo->beginTransaction();
        try {
            $ok = CoupleCardsRepository::returnToDeck($coupleId, (int)$current['card_id']);
            if ($ok) {
                EventLog::add($coupleId, $uid, 'card.return', [
                    'card_id' => (int)$current['card_id'],
                ]);
            }
            $pdo->commit();
            $this->renderWithMessage($uid, 'Kaart teruggelegd.', '', 200);
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function complete(): void
    {
        Auth::requireLogin();
        $uid = (int)Auth::id();

        if (!Csrf::verify((string)($_POST['csrf'] ?? ''), 'cards_complete')) {
            $this->renderWithMessage($uid, '', 'Ongeldige sessie (CSRF).', 400);
            return;
        }

        $coupleId = CoupleRepository::activeCoupleIdForUser($uid);
        if (!$coupleId) {
            $this->renderWithMessage($uid, '', 'Je zit nog niet in een koppel.', 400);
            return;
        }

        $current = CoupleCardsRepository::getCurrentDrawn($coupleId);
        if (!$current) {
            $this->renderWithMessage($uid, '', 'Geen getrokken kaart om af te ronden.', 400);
            return;
        }

        // Points model: default difficulty*10, maar user mag override doen (optioneel)
        $difficulty = (int)($current['difficulty'] ?? 1);
        $defaultPoints = max(1, min(1000, $difficulty * 10));
        $points = isset($_POST['points']) && $_POST['points'] !== ''
            ? (int)$_POST['points']
            : $defaultPoints;
        $points = max(0, min(1000, $points));

        $pdo = DB::pdo();
        $pdo->beginTransaction();
        try {
            $pointsBase = $points;

            // Chaos: apply modifiers (bonus/penalty)
            $chaosOut = ChaosEngine::onComplete($coupleId, $pointsBase);
            $pointsFinal = (int)$chaosOut['points_final'];
            $adjustments = $chaosOut['adjustments'];

            $ok = CoupleCardsRepository::complete($coupleId, (int)$current['card_id'], $pointsFinal);
            if (!$ok) {
                $pdo->commit();
                $this->renderWithMessage($uid, '', 'Kon kaart niet afronden (state mismatch).', 409);
                return;
            }

            $progress = ProgressService::addXp($coupleId, $pointsFinal);

            EventLog::add($coupleId, $uid, 'card.complete', [
                'card_id' => (int)$current['card_id'],
                'points_base' => $pointsBase,
                'points_final' => $pointsFinal,
                'chaos_adjustments' => $adjustments,
                'xp_new' => $progress['new_xp'],
                'level_new' => $progress['new_level'],
                'level_up' => $progress['level_up'],
            ]);

            if ($progress['level_up']) {
                EventLog::add($coupleId, $uid, 'progress.levelup', [
                    'old_level' => $progress['old_level'],
                    'new_level' => $progress['new_level'],
                ]);
            }

            $pdo->commit();
            $this->renderWithMessage($uid, "Kaart afgerond (+{$pointsFinal} XP).", '', 200);
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    private function renderWithMessage(int $uid, string $msg, string $error, int $code): void
    {
        $coupleId = \App\Core\CoupleRepository::activeCoupleIdForUser($uid);

        $counts = $coupleId ? \App\Core\CoupleCardsRepository::countsByState($coupleId) : [];
        $current = $coupleId ? \App\Core\CoupleCardsRepository::getCurrentDrawn($coupleId) : null;

        View::render('cards', [
            'error' => $error,
            'msg' => $msg,
            'couple_id' => $coupleId,
            'counts' => $counts,
            'current' => $current,
            'csrf' => [
                'init' => Csrf::token('cards_init'),
                'draw' => Csrf::token('cards_draw'),
                'return' => Csrf::token('cards_return'),
                'complete' => Csrf::token('cards_complete'),
            ],
        ], $code);
    }
}
