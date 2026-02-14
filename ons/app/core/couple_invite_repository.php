<?php
declare(strict_types=1);

namespace App\Core;

final class CoupleInviteRepository
{
    public static function create(int $inviterUserId, string $inviteeEmailNorm, int $ttlHours = 48): array
    {
        $ttlHours = max(1, min(168, $ttlHours)); // 1 uur .. 7 dagen
        $token = self::token();
        $tokenHash = hash('sha256', $token);

        $expiresAt = (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))
            ->modify('+' . $ttlHours . ' hours')
            ->format('Y-m-d H:i:s.u');

        $pdo = DB::pdo();
        $st = $pdo->prepare(
            "INSERT INTO couple_invites (inviter_user_id, invitee_email_norm, token_hash, expires_at)
             VALUES (:inv, :em, :th, :exp)"
        );
        $st->execute([
            ':inv' => $inviterUserId,
            ':em'  => $inviteeEmailNorm,
            ':th'  => $tokenHash,
            ':exp' => $expiresAt,
        ]);

        return [
            'id' => (int)$pdo->lastInsertId(),
            'token' => $token, // alleen tonen aan inviter (we mailen later)
            'expires_at' => $expiresAt,
        ];
    }

    public static function findValidByToken(string $token): ?array
    {
        $token = trim($token);
        if ($token === '' || strlen($token) > 200) {
            return null;
        }

        $hash = hash('sha256', $token);

        $pdo = DB::pdo();
        $st = $pdo->prepare(
            "SELECT *
             FROM couple_invites
             WHERE token_hash = :h
               AND accepted_at IS NULL
               AND revoked_at IS NULL
               AND expires_at > CURRENT_TIMESTAMP(6)
             LIMIT 1"
        );
        $st->execute([':h' => $hash]);
        $row = $st->fetch();
        return $row ?: null;
    }

    public static function accept(int $inviteId): void
    {
        $pdo = DB::pdo();
        $st = $pdo->prepare(
            "UPDATE couple_invites
             SET accepted_at = CURRENT_TIMESTAMP(6)
             WHERE id = :id AND accepted_at IS NULL AND revoked_at IS NULL"
        );
        $st->execute([':id' => $inviteId]);
    }

    private static function token(): string
    {
        // URL-safe token
        $raw = random_bytes(32);
        $b64 = rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
        return $b64;
    }
}
