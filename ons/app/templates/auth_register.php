<?php declare(strict_types=1); ?>

<div class="card">
  <h1>Registreren</h1>

  <?php if (!empty($error)): ?>
    <div class="flash err"><?= e($error) ?></div>
  <?php endif; ?>

  <div class="hr"></div>

  <form method="post" action="<?= e((\App\Core\Env::config()['app']['base_path'] ?? '') . '/register') ?>">
    <input type="hidden" name="csrf" value="<?= e($csrf ?? '') ?>">

    <div class="row">
      <div>
        <label>Naam</label>
        <input name="display_name" type="text" maxlength="80" required>
      </div>
      <div>
        <label>Email</label>
        <input name="email" type="email" autocomplete="username" required>
      </div>
    </div>

    <div style="margin-top:12px;">
      <label>Wachtwoord (min. 10)</label>
      <input name="password" type="password" autocomplete="new-password" required>
    </div>

    <div style="margin-top:12px;">
      <button class="btn primary" type="submit">Account aanmaken</button>
    </div>
  </form>

  <p class="small" style="margin-top:12px;">
    Heb je al een account? <a href="<?= e((\App\Core\Env::config()['app']['base_path'] ?? '') . '/login') ?>">Login</a>
  </p>
</div>
