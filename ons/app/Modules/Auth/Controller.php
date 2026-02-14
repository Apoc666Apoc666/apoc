<?php
declare(strict_types=1);

namespace App\Modules\Auth;

use App\Core\Auth;
use App\Core\AuthAttempts;
use App\Core\Csrf;
use App\Core\Env;
use App\Core\Response;
use App\Core\UserRepository;
use App\Core\View;

final class Controller
{
    public function loginForm(): void
    {
        View::render('auth_login', [
            'csrf' => Csrf::token('login'),
            'error' => '',
        ]);
    }

    public function loginSubmit(): void
    {
        $cfg = Env::config();
        $base = $cfg['app']['base_path'] ?? '';

        $email = trim((string)($_POST['email'] ?? ''));
        $pass  = (string)($_POST['password'] ?? '');

        $emailNorm = $email !== '' ? UserRepository::normalizeEmail($email) : null;

        // CSRF
        if (!Csrf::verify((string)($_POST['csrf'] ?? ''), 'login')) {
            View::render('auth_login', [
                'csrf' => Csrf::token('login'),
                'error' => 'Ongeldige sessie (CSRF). Ververs en probeer opnieuw.',
            ], 400);
            return;
        }

        // Brute force protection (DB-backed)
        $max = (int)($cfg['security']['rate_limit']['login_per_ip_per_15m'] ?? 20);
        if (!AuthAttempts::allowed($max, 900, $emailNorm)) {
            View::render('auth_login', [
                'csrf' => Csrf::token('login'),
                'error' => 'Te veel pogingen. Probeer later opnieuw.',
            ], 429);
            return;
        }

        // Validate inputs
        if ($email === '' || $pass === '') {
            AuthAttempts::log($emailNorm, false);
            View::render('auth_login', [
                'csrf' => Csrf::token('login'),
                'error' => 'Vul email en wachtwoord in.',
            ], 400);
            return;
        }

        $user = UserRepository::findByEmailNorm((string)$emailNorm);
        if (!$user || empty($user['password_hash']) || !password_verify($pass, (string)$user['password_hash'])) {
            AuthAttempts::log($emailNorm, false);
            View::render('auth_login', [
                'csrf' => Csrf::token('login'),
                'error' => 'Onjuiste inloggegevens.',
            ], 401);
            return;
        }

        if ((int)($user['is_active'] ?? 0) !== 1) {
            AuthAttempts::log($emailNorm, false);
            View::render('auth_login', [
                'csrf' => Csrf::token('login'),
                'error' => 'Account is uitgeschakeld.',
            ], 403);
            return;
        }

        AuthAttempts::log($emailNorm, true);
        Auth::login((int)$user['id']);
        UserRepository::updateLastLogin((int)$user['id']);

        Response::redirect($base . '/', 302);
    }

    public function registerForm(): void
    {
        View::render('auth_register', [
            'csrf' => Csrf::token('register'),
            'error' => '',
        ]);
    }

    public function registerSubmit(): void
    {
        $cfg = Env::config();
        $base = $cfg['app']['base_path'] ?? '';

        if (!Csrf::verify((string)($_POST['csrf'] ?? ''), 'register')) {
            View::render('auth_register', [
                'csrf' => Csrf::token('register'),
                'error' => 'Ongeldige sessie (CSRF). Ververs en probeer opnieuw.',
            ], 400);
            return;
        }

        $display = trim((string)($_POST['display_name'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $pass  = (string)($_POST['password'] ?? '');

        if ($display === '' || $email === '' || $pass === '') {
            View::render('auth_register', [
                'csrf' => Csrf::token('register'),
                'error' => 'Vul alle velden in.',
            ], 400);
            return;
        }

        if (mb_strlen($display, 'UTF-8') > 80) {
            View::render('auth_register', [
                'csrf' => Csrf::token('register'),
                'error' => 'Naam is te lang.',
            ], 400);
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            View::render('auth_register', [
                'csrf' => Csrf::token('register'),
                'error' => 'Ongeldig emailadres.',
            ], 400);
            return;
        }

        if (strlen($pass) < 10) {
            View::render('auth_register', [
                'csrf' => Csrf::token('register'),
                'error' => 'Wachtwoord moet minimaal 10 tekens zijn.',
            ], 400);
            return;
        }

        $emailNorm = UserRepository::normalizeEmail($email);

        if (UserRepository::findByEmailNorm($emailNorm)) {
            View::render('auth_register', [
                'csrf' => Csrf::token('register'),
                'error' => 'Dit emailadres is al geregistreerd.',
            ], 409);
            return;
        }

        $userId = UserRepository::create($email, $pass, $display);

        Auth::login($userId);
        Response::redirect($base . '/', 302);
    }

    public function logout(): void
    {
        $cfg = Env::config();
        $base = $cfg['app']['base_path'] ?? '';

        // CSRF op logout POST (simpel: token "logout")
        if (!\App\Core\Csrf::verify((string)($_POST['csrf'] ?? ''), 'logout')) {
            Response::redirect($base . '/', 302);
            return;
        }

        Auth::logout();
        Response::redirect($base . '/login', 302);
    }
}
