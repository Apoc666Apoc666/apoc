<?php declare(strict_types=1); ?>

<h1>Koppel uitnodigen</h1>

<?php if (!empty($msg)): ?>
  <p style="color:#060;"><?= e($msg) ?></p>
<?php endif; ?>
<?php if (!empty($error)): ?>
  <p style="color:#b00;"><?= e($error) ?></p>
<?php endif; ?>

<form method="post" action="<?= e((\App\Core\Env::config()['app']['base_path'] ?? '') . '/couple/invite') ?>">
  <input type="hidden" name="csrf" value="<?= e($csrf ?? '') ?>">

  <div style="margin-bottom:10px;">
    <label>Email partner<br>
      <input name="email" type="email" required>
    </label>
  </div>

  <button type="submit">Maak invite</button>
</form>

<?php if (!empty($invite_url)): ?>
  <hr>
  <p>Invite link:</p>
  <p><code><?= e($invite_url) ?></code></p>
<?php endif; ?>

<hr>
<p>
  <a href="<?= e((\App\Core\Env::config()['app']['base_path'] ?? '') . '/') ?>">Home</a>
  · <a href="<?= e((\App\Core\Env::config()['app']['base_path'] ?? '') . '/mood') ?>">Mood</a>
</p>
