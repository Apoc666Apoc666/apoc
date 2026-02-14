<?php declare(strict_types=1);
/** @var array $assets */
/** @var bool $hasCouple */
?>
<h1>Galerij</h1>

<?php if (!empty($_GET['msg'])): ?>
  <div class="notice"><?= htmlspecialchars((string)$_GET['msg'], ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<div style="display:flex; gap:12px; align-items:center; margin: 10px 0 18px;">
  <a class="btn" href="/gallery/upload">Upload</a>
  <div class="muted">
    <?= $hasCouple ? 'Gedeeld met jullie koppel.' : 'Geen koppel actief: alleen jouw uploads.' ?>
  </div>
</div>

<?php if (!$assets): ?>
  <p class="muted">Nog geen foto’s.</p>
<?php else: ?>
  <div class="grid">
    <?php foreach ($assets as $a): ?>
      <a class="tile" href="/gallery/view?id=<?= (int)$a['id'] ?>">
        <img loading="lazy" alt=""
             src="/gallery/thumb?id=<?= (int)$a['id'] ?>">
        <div class="cap">
          <?= htmlspecialchars((string)$a['original_filename'], ENT_QUOTES, 'UTF-8') ?>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
