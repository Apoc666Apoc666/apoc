<?php
declare(strict_types=1);

namespace App\Core;

final class Hmac
{
    public static function sign(array $payload, string $secret, int $ts, string $nonce): string
    {
        $data = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $base = $ts . '.' . $nonce . '.' . $data;
        return hash_hmac('sha256', $base, $secret);
    }
}
