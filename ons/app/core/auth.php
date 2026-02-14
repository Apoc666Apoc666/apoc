<?php
declare(strict_types=1);

namespace App\Core;

final class Auth
{
    public static function id(): ?int
    {
        $v = $_SESSION['user_id'] ?? null;
        if ($v === null) return null;
        $id = (int)$v;
        return $id > 0 ? $id : null;
    }

    public static function check(): bool
    {
        return self::id() !== null;
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            $base = Env::config()['app']['base_path'] ?? '';
            Response::redirect($base . '/login', 302);
        }
    }

    /**
     * Store user id and cache display_name in session for fast UI rendering.
     */
    public static function login(int $userId): void
    {
        Session::regenerate();
        $_SESSION['user_id'] = $userId;

        // Cache name (best-effort; no fatal if DB fails)
        try {
            $pdo = DB::pdo();
            $st = $pdo->prepare("SELECT display_name FROM users WHERE id = :id LIMIT 1");
            $st->execute([':id' => $userId]);
            $row = $st->fetch();
            if ($row && isset($row['display_name'])) {
                $_SESSION['display_name'] = (string)$row['display_name'];
            }
        } catch (\Throwable $e) {
            // ignore
        }
    }

    public static function logout(): void
    {
        Session::regenerate();
        unset($_SESSION['user_id'], $_SESSION['display_name']);
    }

    public static function displayName(): ?string
    {
        if (!self::check()) return null;

        $cached = $_SESSION['display_name'] ?? null;
        if (is_string($cached) && $cached !== '') return $cached;

        // Fallback: fetch once and cache
        try {
            $pdo = DB::pdo();
            $st = $pdo->prepare("SELECT display_name FROM users WHERE id = :id LIMIT 1");
            $st->execute([':id' => self::id()]);
            $row = $st->fetch();
            if ($row && isset($row['display_name'])) {
                $name = (string)$row['display_name'];
                if ($name !== '') {
                    $_SESSION['display_name'] = $name;
                    return $name;
                }
            }
        } catch (\Throwable $e) {
            // ignore
        }

        return null;
    }

    public static function initials(?string $name): string
    {
        $name = trim((string)$name);
        if ($name === '') return 'U';

        $parts = preg_split('/\s+/u', $name) ?: [];
        $first = mb_substr($parts[0] ?? $name, 0, 1, 'UTF-8');
        $second = '';

        if (count($parts) >= 2) {
            $second = mb_substr($parts[1], 0, 1, 'UTF-8');
        } elseif (mb_strlen($name, 'UTF-8') >= 2) {
            $second = mb_substr($name, 1, 1, 'UTF-8');
        }

        $ini = mb_strtoupper($first . $second, 'UTF-8');
        return $ini !== '' ? $ini : 'U';
    }
}
