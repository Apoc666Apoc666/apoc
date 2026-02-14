<?php
declare(strict_types=1);

namespace App\Modules\Mood;

use App\Core\Auth;
use App\Core\CoupleRepository;
use App\Core\Csrf;
use App\Core\EventLog;
use App\Core\MoodRepository;
use App\Core\View;

final class Controller
{
    public function show(): void
    {
        Auth::requireLogin();
        $uid = (int)Auth::id();

        $coupleId = CoupleRepository::activeCoupleIdForUser($uid);

        $items = [];
        if ($coupleId) {
            $items = MoodRepository::activeForCouple($coupleId);
        }

        View::render('mood', [
            'couple_id' => $coupleId,
            'items' => $items,
            'csrf' => Csrf::token('mood_set'),
            'msg' => '',
            'error' => '',
        ]);
    }

    public function set(): void
    {
        Auth::requireLogin();
        $uid = (int)Auth::id();

        if (!Csrf::verify((string)($_POST['csrf'] ?? ''), 'mood_set')) {
            $this->renderError('Ongeldige sessie (CSRF).', 400);
            return;
        }

        $coupleId = CoupleRepository::activeCoupleIdForUser($uid);
        if (!$coupleId) {
            $this->renderError('Je zit nog niet in een koppel.', 400);
            return;
        }

        $mood = (string)($_POST['mood'] ?? '');
        if (!in_array($mood, ['yes', 'maybe', 'no'], true)) {
            $this->renderError('Ongeldige mood.', 400);
            return;
        }

        $note = trim((string)($_POST['note'] ?? ''));
        $note = $note === '' ? null : $note;
        if ($note !== null && mb_strlen($note, 'UTF-8') > 200) {
            $this->renderError('Note max 200 tekens.', 400);
            return;
        }

        $ttl = (int)($_POST['ttl_minutes'] ?? 120);
        MoodRepository::set($coupleId, $uid, $mood, $note, $ttl);

        EventLog::add($coupleId, $uid, 'mood.set', [
            'mood' => $mood,
            'ttl_minutes' => $ttl,
            'has_note' => $note !== null,
        ]);

        // Render updated
        $items = MoodRepository::activeForCouple($coupleId);
        View::render('mood', [
            'couple_id' => $coupleId,
            'items' => $items,
            'csrf' => Csrf::token('mood_set'),
            'msg' => 'Mood opgeslagen.',
            'error' => '',
        ]);
    }

    private function renderError(string $error, int $code): void
    {
        $uid = (int)Auth::id();
        $coupleId = CoupleRepository::activeCoupleIdForUser($uid);
        $items = $coupleId ? MoodRepository::activeForCouple($coupleId) : [];

        View::render('mood', [
            'couple_id' => $coupleId,
            'items' => $items,
            'csrf' => Csrf::token('mood_set'),
            'msg' => '',
            'error' => $error,
        ], $code);
    }
}
