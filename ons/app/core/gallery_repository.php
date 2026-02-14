<?php
declare(strict_types=1);

namespace App\Core;

final class GalleryRepository
{
    public static function listForCouple(int $coupleId, int $limit = 60): array
    {
        $db = Db::pdo();
        $sql = "SELECT id, owner_user_id, couple_id, original_filename, mime, size_bytes, sha256, created_at
                FROM gallery_assets
                WHERE couple_id = :cid
                ORDER BY created_at DESC
                LIMIT " . (int)$limit;
        $st = $db->prepare($sql);
        $st->execute([':cid' => $coupleId]);
        return $st->fetchAll();
    }

    public static function listForOwner(int $ownerUserId, int $limit = 60): array
    {
        $db = Db::pdo();
        $sql = "SELECT id, owner_user_id, couple_id, original_filename, mime, size_bytes, sha256, created_at
                FROM gallery_assets
                WHERE owner_user_id = :uid
                ORDER BY created_at DESC
                LIMIT " . (int)$limit;
        $st = $db->prepare($sql);
        $st->execute([':uid' => $ownerUserId]);
        return $st->fetchAll();
    }

    public static function get(int $assetId): ?array
    {
        $db = Db::pdo();
        $st = $db->prepare("SELECT * FROM gallery_assets WHERE id = :id LIMIT 1");
        $st->execute([':id' => $assetId]);
        $row = $st->fetch();
        return $row ?: null;
    }

    public static function insert(array $row): int
    {
        $db = Db::pdo();
        $sql = "INSERT INTO gallery_assets
                (owner_user_id, couple_id, original_filename, mime, size_bytes, sha256, storage_path, thumb_path, created_at)
                VALUES
                (:owner_user_id, :couple_id, :original_filename, :mime, :size_bytes, :sha256, :storage_path, :thumb_path, NOW(6))";
        $st = $db->prepare($sql);
        $st->execute([
            ':owner_user_id' => $row['owner_user_id'],
            ':couple_id' => $row['couple_id'],
            ':original_filename' => $row['original_filename'],
            ':mime' => $row['mime'],
            ':size_bytes' => $row['size_bytes'],
            ':sha256' => $row['sha256'],
            ':storage_path' => $row['storage_path'],
            ':thumb_path' => $row['thumb_path'],
        ]);
        return (int)$db->lastInsertId();
    }

    public static function existsByOwnerAndSha(int $ownerUserId, string $sha256): ?int
    {
        $db = Db::pdo();
        $st = $db->prepare("SELECT id FROM gallery_assets WHERE owner_user_id = :uid AND sha256 = :sha LIMIT 1");
        $st->execute([':uid' => $ownerUserId, ':sha' => $sha256]);
        $id = $st->fetchColumn();
        return $id ? (int)$id : null;
    }
}
