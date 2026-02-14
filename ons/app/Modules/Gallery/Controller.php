<?php
declare(strict_types=1);

namespace App\Modules\Gallery;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\View;
use App\Core\Response;
use App\Core\RateLimit;
use App\Core\EventLog;
use App\Core\GalleryRepository;
use App\Core\CoupleRepository;

final class Controller
{
    private const MAX_BYTES = 12_000_000; // 12 MB
    private const ALLOWED_MIMES = ['image/jpeg', 'image/png', 'image/webp'];
    private const THUMB_MAX_W = 480;
    private const THUMB_MAX_H = 480;

    public function index(): void
    {
        Auth::requireLogin();
        $uid = (int)Auth::id();

        $cid = CoupleRepository::activeCoupleIdForUser($uid);

        $assets = $cid
            ? GalleryRepository::listForCouple($cid, 60)
            : GalleryRepository::listForOwner($uid, 60);

        View::render('gallery', [
            'assets' => $assets,
            'hasCouple' => (bool)$cid,
            'csrf' => Csrf::token('gallery_upload'),
            'msg' => '',
            'error' => '',
        ]);
    }

    public function uploadForm(): void
    {
        Auth::requireLogin();
        View::render('gallery_upload', [
            'csrf' => Csrf::token('gallery_upload'),
            'msg' => '',
            'error' => '',
        ]);
    }

    public function upload(): void
    {
        Auth::requireLogin();
        $uid = (int)Auth::id();

        Csrf::requireToken('gallery_upload');

        RateLimit::hit('upload:' . $uid, 10, 600);
        RateLimit::hit('uploadip:' . ($_SERVER['REMOTE_ADDR'] ?? '0'), 20, 600);

        if (!isset($_FILES['image']) || !is_array($_FILES['image'])) {
            $this->renderUploadError('Geen bestand ontvangen.');
            return;
        }

        $f = $_FILES['image'];

        if (($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $this->renderUploadError('Upload fout (code ' . (int)$f['error'] . ').');
            return;
        }

        $tmp = (string)$f['tmp_name'];
        $origName = (string)($f['name'] ?? 'upload');
        $size = (int)($f['size'] ?? 0);

        if ($size <= 0 || $size > self::MAX_BYTES) {
            $this->renderUploadError('Bestand te groot of leeg (max 12MB).');
            return;
        }

        $mime = $this->detectMime($tmp);
        if (!in_array($mime, self::ALLOWED_MIMES, true)) {
            $this->renderUploadError('Onveilig bestandsformaat. Alleen JPG/PNG/WebP.');
            return;
        }

        $img = $this->gdLoad($tmp, $mime);
        if (!$img) {
            $this->renderUploadError('Kon afbeelding niet lezen.');
            return;
        }

        // Policy A: couple shared
        $cid = CoupleRepository::activeCoupleIdForUser($uid);

        $base = bin2hex(random_bytes(16));
        $ext = $mime === 'image/jpeg' ? 'jpg' : ($mime === 'image/png' ? 'png' : 'webp');

        $uploadRel = 'uploads/' . date('Y/m/') . $base . '.' . $ext;
        $thumbRel  = 'thumbs/' . date('Y/m/') . $base . '.jpg';

        $uploadAbs = $this->storagePath($uploadRel);
        $thumbAbs  = $this->storagePath($thumbRel);

        $this->ensureDir(dirname($uploadAbs));
        $this->ensureDir(dirname($thumbAbs));

        if (!$this->gdSave($img, $uploadAbs, $mime)) {
            imagedestroy($img);
            $this->renderUploadError('Kon bestand niet opslaan.');
            return;
        }

        $sha = hash_file('sha256', $uploadAbs);
        $existingId = GalleryRepository::existsByOwnerAndSha($uid, $sha);
        if ($existingId) {
            imagedestroy($img);
            Response::redirect('/gallery?msg=Bestand+bestond+al');
            return;
        }

        $thumb = $this->makeThumb($img, self::THUMB_MAX_W, self::THUMB_MAX_H);
        imagedestroy($img);

        if (!$thumb || !$this->gdSave($thumb, $thumbAbs, 'image/jpeg')) {
            if ($thumb) imagedestroy($thumb);
            $this->renderUploadError('Kon thumbnail niet maken.');
            return;
        }
        imagedestroy($thumb);

        $assetId = GalleryRepository::insert([
            'owner_user_id' => $uid,
            'couple_id' => $cid,
            'original_filename' => $this->sanitizeFilename($origName),
            'mime' => $mime,
            'size_bytes' => filesize($uploadAbs),
            'sha256' => $sha,
            'storage_path' => $uploadRel,
            'thumb_path' => $thumbRel,
        ]);

        EventLog::add($cid, $uid, 'gallery.upload', [
            'asset_id' => $assetId,
            'mime' => $mime,
            'size_bytes' => (int)filesize($uploadAbs),
        ]);

        Response::redirect('/gallery?msg=Upload+gelukt');
    }

    public function view(): void { $this->serve(false); }
    public function thumb(): void { $this->serve(true); }

    private function serve(bool $thumb): void
    {
        Auth::requireLogin();
        $uid = (int)Auth::id();

        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) { Response::status(404); echo 'Not found'; return; }

        $asset = GalleryRepository::get($id);
        if (!$asset) { Response::status(404); echo 'Not found'; return; }

        $cid = CoupleRepository::activeCoupleIdForUser($uid);
        $ok = ((int)$asset['owner_user_id'] === $uid) || ($cid && (int)$asset['couple_id'] === $cid);
        if (!$ok) { Response::status(403); echo 'Forbidden'; return; }

        $rel = $thumb ? (string)$asset['thumb_path'] : (string)$asset['storage_path'];
        if ($rel === '') { Response::status(404); echo 'Not found'; return; }

        $abs = $this->storagePath($rel);
        if (!is_file($abs)) { Response::status(404); echo 'Not found'; return; }

        $etag = '"' . (string)$asset['sha256'] . ($thumb ? ':t' : ':o') . '"';
        header('ETag: ' . $etag);
        header('Cache-Control: private, max-age=86400');

        $ifNone = $_SERVER['HTTP_IF_NONE_MATCH'] ?? '';
        if ($ifNone === $etag) { Response::status(304); return; }

        $mime = $thumb ? 'image/jpeg' : (string)$asset['mime'];
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . (string)filesize($abs));
        header('X-Content-Type-Options: nosniff');

        readfile($abs);
    }

    private function renderUploadError(string $error): void
    {
        View::render('gallery_upload', [
            'csrf' => Csrf::token('gallery_upload'),
            'msg' => '',
            'error' => $error,
        ]);
    }

    private function detectMime(string $path): string
    {
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($path);
        return is_string($mime) ? $mime : '';
    }

    private function gdLoad(string $path, string $mime)
    {
        return match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($path),
            'image/png'  => @imagecreatefrompng($path),
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false,
            default => false,
        };
    }

    private function gdSave($img, string $path, string $mime): bool
    {
        return match ($mime) {
            'image/jpeg' => @imagejpeg($img, $path, 85),
            'image/png'  => @imagepng($img, $path, 6),
            'image/webp' => function_exists('imagewebp') ? @imagewebp($img, $path, 82) : false,
            default => false,
        };
    }

    private function makeThumb($src, int $maxW, int $maxH)
    {
        $w = imagesx($src); $h = imagesy($src);
        if ($w <= 0 || $h <= 0) return false;

        $scale = min($maxW / $w, $maxH / $h, 1.0);
        $tw = (int)max(1, floor($w * $scale));
        $th = (int)max(1, floor($h * $scale));

        $dst = imagecreatetruecolor($tw, $th);
        if (!$dst) return false;

        $white = imagecolorallocate($dst, 255, 255, 255);
        imagefilledrectangle($dst, 0, 0, $tw, $th, $white);

        if (!imagecopyresampled($dst, $src, 0, 0, 0, 0, $tw, $th, $w, $h)) {
            imagedestroy($dst);
            return false;
        }
        return $dst;
    }

    private function storagePath(string $rel): string
    {
        return dirname(__DIR__, 2) . '/storage/' . ltrim($rel, '/');
    }

    private function ensureDir(string $dir): void
    {
        if (!is_dir($dir)) { mkdir($dir, 0775, true); }
    }

    private function sanitizeFilename(string $name): string
    {
        $name = trim($name);
        $name = preg_replace('/[^\pL\pN\.\-\_\s]+/u', '', $name) ?? 'upload';
        $name = preg_replace('/\s+/', ' ', $name) ?? 'upload';
        return mb_substr($name, 0, 200);
    }
}
