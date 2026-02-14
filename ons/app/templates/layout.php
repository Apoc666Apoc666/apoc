<?php declare(strict_types=1); ?>
<!doctype html>
<html lang="nl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= e(\App\Core\Env::config()['app']['name'] ?? 'App') ?></title>
  <link rel="stylesheet" href="<?= e((\App\Core\Env::config()['app']['base_path'] ?? '') . '/assets/css/app.css') ?>">
</head>
<body>

<?php
  $base = \App\Core\Env::config()['app']['base_path'] ?? '';
  $name = \App\Core\Auth::displayName();
  $initials = \App\Core\Auth::initials($name);
?>

<div class="topbar">
  <div class="nav">
    <a class="brand" href="<?= e($base . '/') ?>">
      <span class="dot"></span>
      <span>Ons</span>
    </a>

    <div class="navlinks">
      <?php if (\App\Core\Auth::check()): ?>

        <span class="badge" style="display:inline-flex; align-items:center; gap:10px;">
          <span style="
            width: 28px; height: 28px; border-radius: 999px;
            display:inline-flex; align-items:center; justify-content:center;
            font-weight: 800; font-size: 12px; letter-spacing: .5px;
            border: 1px solid var(--border);
            background: linear-gradient(135deg, color-mix(in srgb, var(--accent) 25%, var(--panel)), color-mix(in srgb, var(--accent2) 18%, var(--panel)));
            color: var(--text);
          ">
            <?= e($initials) ?>
          </span>
          <span>
            <span class="small">Ingelogd als</span><br>
            <strong><?= e($name ?? 'Gebruiker') ?></strong>
          </span>
        </span>

        <a class="pill" href="<?= e($base . '/profile') ?>">Profiel</a>
        <a class="pill" href="<?= e($base . '/mood') ?>">Mood</a>
        <a class="pill" href="<?= e($base . '/couple/invite') ?>">Koppel</a>
        <a class="pill" href="<?= e($base . '/cards') ?>">Kaarten</a>
        <a class="pill" href="<?= e($base . '/chaos') ?>">Chaos</a>

        <form method="post" action="<?= e($base . '/logout') ?>" style="display:inline;">
          <input type="hidden" name="csrf" value="<?= e(\App\Core\Csrf::token('logout')) ?>">
          <button class="btn danger" type="submit">Uitloggen</button>
        </form>

      <?php else: ?>
        <a class="pill" href="<?= e($base . '/login') ?>">Login</a>
        <a class="pill" href="<?= e($base . '/register') ?>">Registreren</a>
      <?php endif; ?>
    </div>
  </div>
</div>

<div class="container">
  <?= $content ?? '' ?>
</div>

</body>
</html>
