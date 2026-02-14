<?php declare(strict_types=1); ?>

<?php
  $name = \App\Core\Auth::displayName();
?>

<div class="grid cols-2">
  <div class="card">
    <h1>Ons</h1>

    <?php if (\App\Core\Auth::check()): ?>
      <p class="badge">
        Welkom terug, <strong><?= e($name ?? 'Gebruiker') ?></strong>
      </p>
      <p class="small">Kies bovenin een onderdeel.</p>
    <?php else: ?>
      <p class="small">Log in of maak een account aan om te starten.</p>
      <div style="margin-top:12px; display:flex; gap:10px; flex-wrap:wrap;">
        <a class="btn primary" href="<?= e((\App\Core\Env::config()['app']['base_path'] ?? '') . '/login') ?>">Login</a>
        <a class="btn" href="<?= e((\App\Core\Env::config()['app']['base_path'] ?? '') . '/register') ?>">Registreren</a>
      </div>
    <?php endif; ?>
  </div>

  <div class="card">
    <h2>Snelkoppelingen</h2>
    <?php $base = \App\Core\Env::config()['app']['base_path'] ?? ''; ?>
    <?php if (\App\Core\Auth::check()): ?>
      <div style="display:flex; gap:10px; flex-wrap:wrap;">
        <a class="pill" href="<?= e($base . '/profile') ?>">Profiel</a>
        <a class="pill" href="<?= e($base . '/mood') ?>">Mood</a>
        <a class="pill" href="<?= e($base . '/couple/invite') ?>">Koppel</a>
        <a class="pill" href="<?= e($base . '/cards') ?>">Kaarten</a>
        <a class="pill" href="<?= e($base . '/chaos') ?>">Chaos</a>
      </div>
      <p class="small" style="margin-top:12px;">
        Tip: zet chaosregels aan voor cooldown/bonus of random reroll.
      </p>
    <?php else: ?>
      <p class="small">Na inloggen verschijnen hier je onderdelen.</p>
    <?php endif; ?>
  </div>
</div>
