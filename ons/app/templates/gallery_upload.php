<?php declare(strict_types=1);
/** @var string $csrf */
/** @var string $msg */
/** @var string $error */
?>
<h1>Upload foto</h1>

<?php if ($error): ?>
  <div class="error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<form method="post" action="/gallery/upload" enctype="multipart/form-data">
  <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
  <div style="margin: 12px 0;">
    <input type="file" name="image" accept="image/jpeg,image/png,image/webp" required>
  </div>
  <button class="btn" type="submit">Upload</button>
  <a class="btn secondary" href="/gallery">Terug</a>
</form>

<p class="muted" style="margin-top:12px;">
  Toegestaan: JPG/PNG/WebP · Max 12MB · Wordt gedeeld met jullie koppel (als gekoppeld).
</p>
