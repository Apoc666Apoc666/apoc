<?php
declare(strict_types=1);

namespace App\Modules\Profile;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\ProfileRepository;
use App\Core\View;

final class Controller
{
    public function show(): void
    {
        Auth::requireLogin();
        $uid = (int)Auth::id();

        $profile = ProfileRepository::get($uid);

        View::render('profile', [
            'profile' => $profile,
            'csrf' => Csrf::token('profile_update'),
            'msg' => '',
            'error' => '',
        ]);
    }

    public function update(): void
    {
        Auth::requireLogin();
        $uid = (int)Auth::id();

        if (!Csrf::verify((string)($_POST['csrf'] ?? ''), 'profile_update')) {
            $profile = ProfileRepository::get($uid);
            View::render('profile', [
                'profile' => $profile,
                'csrf' => Csrf::token('profile_update'),
                'msg' => '',
                'error' => 'Ongeldige sessie (CSRF).',
            ], 400);
            return;
        }

        $display = trim((string)($_POST['display_name'] ?? ''));
        $bio = trim((string)($_POST['bio'] ?? ''));
        $bio = $bio === '' ? null : $bio;

        if ($display === '' || mb_strlen($display, 'UTF-8') > 80) {
            $profile = ProfileRepository::get($uid);
            View::render('profile', [
                'profile' => $profile,
                'csrf' => Csrf::token('profile_update'),
                'msg' => '',
                'error' => 'Naam is verplicht en max 80 tekens.',
            ], 400);
            return;
        }

        if ($bio !== null && mb_strlen($bio, 'UTF-8') > 280) {
            $profile = ProfileRepository::get($uid);
            View::render('profile', [
                'profile' => $profile,
                'csrf' => Csrf::token('profile_update'),
                'msg' => '',
                'error' => 'Bio max 280 tekens.',
            ], 400);
            return;
        }

        ProfileRepository::update($uid, $display, $bio);

        // ✅ Update cached name for navbar immediately
        $_SESSION['display_name'] = $display;

        $profile = ProfileRepository::get($uid);
        View::render('profile', [
            'profile' => $profile,
            'csrf' => Csrf::token('profile_update'),
            'msg' => 'Opgeslagen.',
            'error' => '',
        ]);
    }
}
