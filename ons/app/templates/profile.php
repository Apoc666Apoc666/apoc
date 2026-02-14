<?php declare(strict_types=1); ?>

<div class="card">
  <h1>Profiel</h1>

  <?php if (!empty($msg)): ?>
    <div class="flash ok"><?= e($msg) ?></div>
  <?php endif; ?>
  <?php if (!empty($error)): ?>
    <div class="flash err"><?= e($error) ?></div>
  <?php endif; ?>

  <div class="hr"></div>

  <form method="post" action="<?= e((\App\Core\Env::config()['app']['base_path'] ?? '') . '/profile') ?>">
    <input type="hidden" name="csrf" value="<?= e($csrf ?? '') ?>">

    <div class="row">
      <div>
        <label>Naam</label>
        <input name="display_name" type="text" maxlength="80" required value="<?= e((string)($profile['display_name'] ?? '')) ?>">
      </div>
      <div>
        <label>Email</label>
        <input type="text" value="<?= e((string)($profile['email'] ?? '')) ?>" disabled>
      </div>
    </div>

    <div style="margin-top:12px;">
      <label>Bio (max 280)</label>
      <textarea name="bio" maxlength="280" rows="4"><?= e((string)($profile['bio'] ?? '')) ?></textarea>
    </div>

    <div style="margin-top:12px;">
      <button class="btn primary" type="submit">Opslaan</button>
    </div>
  </form>
</div>
