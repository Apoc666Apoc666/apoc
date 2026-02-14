<?php
declare(strict_types=1);

namespace App\Modules\Chaos;

use App\Core\Auth;
use App\Core\ChaosRepository;
use App\Core\CoupleRepository;
use App\Core\Csrf;
use App\Core\EventLog;
use App\Core\View;

final class Controller
{
    public function show(): void
    {
        Auth::requireLogin();
        $uid = (int)Auth::id();

        $coupleId = CoupleRepository::activeCoupleIdForUser($uid);
        if (!$coupleId) {
            View::render('chaos', [
                'error' => 'Je zit nog niet in een koppel.',
                'msg' => '',
                'couple_id' => null,
                'rules' => [],
                'enabled' => [],
                'csrf' => Csrf::token('chaos_toggle'),
            ], 400);
            return;
        }

        $rules = ChaosRepository::listAllRules();
        $enabled = ChaosRepository::enabledRuleIdsForCouple($coupleId);

        View::render('chaos', [
            'error' => '',
            'msg' => '',
            'couple_id' => $coupleId,
            'rules' => $rules,
            'enabled' => $enabled,
            'csrf' => Csrf::token('chaos_toggle'),
        ]);
    }

    public function toggle(): void
    {
        Auth::requireLogin();
        $uid = (int)Auth::id();

        if (!Csrf::verify((string)($_POST['csrf'] ?? ''), 'chaos_toggle')) {
            $this->render('Ongeldige sessie (CSRF).', '', 400);
            return;
        }

        $coupleId = CoupleRepository::activeCoupleIdForUser($uid);
        if (!$coupleId) {
            $this->render('Je zit nog niet in een koppel.', '', 400);
            return;
        }

        $ruleId = (int)($_POST['rule_id'] ?? 0);
        $enable = (string)($_POST['enable'] ?? '') === '1';

        if ($ruleId <= 0) {
            $this->render('Ongeldige rule.', '', 400);
            return;
        }

        ChaosRepository::toggleCoupleRule($coupleId, $ruleId, $enable);

        EventLog::add($coupleId, $uid, 'chaos.toggle', [
            'rule_id' => $ruleId,
            'enabled' => $enable,
        ]);

        $this->render('', 'Chaos bijgewerkt.', 200);
    }

    private function render(string $error, string $msg, int $code): void
    {
        $uid = (int)Auth::id();
        $coupleId = \App\Core\CoupleRepository::activeCoupleIdForUser($uid);

        $rules = $coupleId ? \App\Core\ChaosRepository::listAllRules() : [];
        $enabled = $coupleId ? \App\Core\ChaosRepository::enabledRuleIdsForCouple($coupleId) : [];

        View::render('chaos', [
            'error' => $error,
            'msg' => $msg,
            'couple_id' => $coupleId,
            'rules' => $rules,
            'enabled' => $enabled,
            'csrf' => \App\Core\Csrf::token('chaos_toggle'),
        ], $code);
    }
}
