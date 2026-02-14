<?php declare(strict_types=1); ?>

<div class="card">
  <h1>Login</h1>

  <?php if (!empty($error)): ?>
    <div class="flash err"><?= e($error) ?></div>
  <?php endif; ?>

  <div class="hr"></div>

  <form method="post" action="<?= e((\App\Core\Env::config()['app']['base_path'] ?? '') . '/login') ?>">
    <input type="hidden" name="csrf" value="<?= e($csrf ?? '') ?>">

    <div class="row">
      <div>
        <label>Email</label>
        <input name="email" type="email" autocomplete="username" required>
      </div>
      <div>
        <label>Wachtwoord</label>
        <input name="password" type="password" autocomplete="current-password" required>
      </div>
    </div>

    <div style="margin-top:12px;">
      <button class="btn primary" type="submit">Inloggen</button>
    </div>
  </form>

  <p class="small" style="margin-top:12px;">
    Nog geen account? <a href="<?= e((\App\Core\Env::config()['app']['base_path'] ?? '') . '/register') ?>">Registreren</a>
  </p>
</div>
