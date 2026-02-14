<?php
declare(strict_types=1);

namespace App\Core;

final class Security
{
    public static function applyHeaders(): void
    {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Referrer-Policy: no-referrer');
        header('Permissions-Policy: geolocation=(), microphone=(), camera=()');

        // CSP later aanscherpen als we exact weten welke assets nodig zijn
        header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self' 'unsafe-inline'; script-src 'self'; base-uri 'self'; frame-ancestors 'none'");
    }

    public static function timingSafeEquals(string $a, string $b): bool
    {
        return hash_equals($a, $b);
    }
}
