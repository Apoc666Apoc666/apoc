<?php declare(strict_types=1); ?>

<h1>Koppel invite accepteren</h1>

<?php if (!empty($msg)): ?>
  <p style="color:#060;"><?= e($msg) ?></p>
<?php endif; ?>
<?php if (!empty($error)): ?>
  <p style="color:#b00;"><?= e($error) ?></p>
<?php endif; ?>

<?php if (!empty($token) && empty($error)): ?>
  <p>Deze invite is geldig voor jouw account. Wil je accepteren?</p>

  <form method="post" action="<?= e((\App\Core\Env::config()['app']['base_path'] ?? '') . '/couple/accept') ?>">
    <input type="hidden" name="csrf" value="<?= e($csrf ?? '') ?>">
    <input type="hidden" name="token" value="<?= e($token) ?>">
    <button type="submit">Accepteer invite</button>
  </form>
<?php endif; ?>

<hr>
<p>
  <a href="<?= e((\App\Core\Env::config()['app']['base_path'] ?? '') . '/') ?>">Home</a>
</p>
