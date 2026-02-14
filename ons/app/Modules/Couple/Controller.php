<?php
declare(strict_types=1);

namespace App\Modules\Couple;

use App\Core\Auth;
use App\Core\CoupleInviteRepository;
use App\Core\CoupleRepository;
use App\Core\Csrf;
use App\Core\Env;
use App\Core\EventLog;
use App\Core\Response;
use App\Core\UserRepository;
use App\Core\View;

final class Controller
{
    public function inviteForm(): void
    {
        Auth::requireLogin();
        $uid = (int)Auth::id();

        // Als je al in een active couple zit: blok
        $cid = CoupleRepository::activeCoupleIdForUser($uid);
        if ($cid) {
            View::render('couple_invite', [
                'csrf' => Csrf::token('couple_invite'),
                'error' => 'Je zit al in een koppel.',
                'msg' => '',
                'invite_url' => '',
            ], 400);
            return;
        }

        View::render('couple_invite', [
            'csrf' => Csrf::token('couple_invite'),
            'error' => '',
            'msg' => '',
            'invite_url' => '',
        ]);
    }

    public function inviteSubmit(): void
    {
        Auth::requireLogin();
        $uid = (int)Auth::id();
        $cfg = Env::config();
        $base = $cfg['app']['base_path'] ?? '';

        if (!Csrf::verify((string)($_POST['csrf'] ?? ''), 'couple_invite')) {
            View::render('couple_invite', [
                'csrf' => Csrf::token('couple_invite'),
                'error' => 'Ongeldige sessie (CSRF).',
                'msg' => '',
                'invite_url' => '',
            ], 400);
            return;
        }

        // Geen couple hebben
        $cid = CoupleRepository::activeCoupleIdForUser($uid);
        if ($cid) {
            View::render('couple_invite', [
                'csrf' => Csrf::token('couple_invite'),
                'error' => 'Je zit al in een koppel.',
                'msg' => '',
                'invite_url' => '',
            ], 400);
            return;
        }

        $email = trim((string)($_POST['email'] ?? ''));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            View::render('couple_invite', [
                'csrf' => Csrf::token('couple_invite'),
                'error' => 'Ongeldig emailadres.',
                'msg' => '',
                'invite_url' => '',
            ], 400);
            return;
        }

        $inviteeNorm = UserRepository::normalizeEmail($email);

        // Self-invite blokkeren
        $me = UserRepository::findById($uid);
        $meNorm = $me ? (string)$me['email_norm'] : '';
        if ($meNorm !== '' && $inviteeNorm === $meNorm) {
            View::render('couple_invite', [
                'csrf' => Csrf::token('couple_invite'),
                'error' => 'Je kunt jezelf niet uitnodigen.',
                'msg' => '',
                'invite_url' => '',
            ], 400);
            return;
        }

        $created = CoupleInviteRepository::create($uid, $inviteeNorm, 48);

        $token = $created['token'];
        $inviteUrl = $base . '/couple/accept?token=' . rawurlencode($token);

        EventLog::add(null, $uid, 'couple.invite.created', [
            'invite_id' => (int)$created['id'],
            'invitee_email_norm' => $inviteeNorm,
            'expires_at' => $created['expires_at'],
        ]);

        View::render('couple_invite', [
            'csrf' => Csrf::token('couple_invite'),
            'error' => '',
            'msg' => 'Invite aangemaakt. Stuur deze link naar je partner:',
            'invite_url' => $inviteUrl,
        ]);
    }

    public function acceptForm(): void
    {
        Auth::requireLogin();
        $uid = (int)Auth::id();

        $token = (string)($_GET['token'] ?? '');
        $invite = CoupleInviteRepository::findValidByToken($token);

        if (!$invite) {
            View::render('couple_accept', [
                'csrf' => Csrf::token('couple_accept'),
                'error' => 'Invite ongeldig of verlopen.',
                'msg' => '',
                'token' => '',
            ], 404);
            return;
        }

        // Invitee moet matchen met ingelogde user email_norm
        $me = UserRepository::findById($uid);
        $meNorm = $me ? (string)$me['email_norm'] : '';
        if ($meNorm === '' || $meNorm !== (string)$invite['invitee_email_norm']) {
            View::render('couple_accept', [
                'csrf' => Csrf::token('couple_accept'),
                'error' => 'Deze invite is niet voor jouw account.',
                'msg' => '',
                'token' => '',
            ], 403);
            return;
        }

        // Als je al in een couple zit: niet accepteren
        $cid = CoupleRepository::activeCoupleIdForUser($uid);
        if ($cid) {
            View::render('couple_accept', [
                'csrf' => Csrf::token('couple_accept'),
                'error' => 'Je zit al in een koppel.',
                'msg' => '',
                'token' => '',
            ], 400);
            return;
        }

        View::render('couple_accept', [
            'csrf' => Csrf::token('couple_accept'),
            'error' => '',
            'msg' => '',
            'token' => $token,
        ]);
    }

    public function acceptSubmit(): void
    {
        Auth::requireLogin();
        $uid = (int)Auth::id();
        $cfg = Env::config();
        $base = $cfg['app']['base_path'] ?? '';

        if (!Csrf::verify((string)($_POST['csrf'] ?? ''), 'couple_accept')) {
            View::render('couple_accept', [
                'csrf' => Csrf::token('couple_accept'),
                'error' => 'Ongeldige sessie (CSRF).',
                'msg' => '',
                'token' => '',
            ], 400);
            return;
        }

        $token = (string)($_POST['token'] ?? '');
        $invite = CoupleInviteRepository::findValidByToken($token);
        if (!$invite) {
            View::render('couple_accept', [
                'csrf' => Csrf::token('couple_accept'),
                'error' => 'Invite ongeldig of verlopen.',
                'msg' => '',
                'token' => '',
            ], 404);
            return;
        }

        // Email match check
        $me = UserRepository::findById($uid);
        $meNorm = $me ? (string)$me['email_norm'] : '';
        if ($meNorm === '' || $meNorm !== (string)$invite['invitee_email_norm']) {
            View::render('couple_accept', [
                'csrf' => Csrf::token('couple_accept'),
                'error' => 'Deze invite is niet voor jouw account.',
                'msg' => '',
                'token' => '',
            ], 403);
            return;
        }

        // Beide users mogen niet al in active couple zitten
        $inviterId = (int)$invite['inviter_user_id'];
        if (CoupleRepository::activeCoupleIdForUser($uid) !== null) {
            View::render('couple_accept', [
                'csrf' => Csrf::token('couple_accept'),
                'error' => 'Je zit al in een koppel.',
                'msg' => '',
                'token' => '',
            ], 400);
            return;
        }
        if (CoupleRepository::activeCoupleIdForUser($inviterId) !== null) {
            View::render('couple_accept', [
                'csrf' => Csrf::token('couple_accept'),
                'error' => 'De inviter zit al in een koppel.',
                'msg' => '',
                'token' => '',
            ], 400);
            return;
        }

        // Create couple + accept invite atomically
        $pdo = \App\Core\DB::pdo();
        $pdo->beginTransaction();
        try {
            $coupleId = \App\Core\CoupleRepository::createCoupleWithUsers($inviterId, $uid);
            \App\Core\CoupleInviteRepository::accept((int)$invite['id']);

            $pdo->commit();

            EventLog::add($coupleId, $uid, 'couple.invite.accepted', [
                'invite_id' => (int)$invite['id'],
                'inviter_user_id' => $inviterId,
            ]);

            Response::redirect($base . '/mood', 302);
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
