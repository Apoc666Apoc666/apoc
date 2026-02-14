<?php declare(strict_types=1); ?>

<h1>Kaarten</h1>

<?php if (!empty($msg)): ?>
  <p style="color:#060;"><?= e($msg) ?></p>
<?php endif; ?>
<?php if (!empty($error)): ?>
  <p style="color:#b00;"><?= e($error) ?></p>
<?php endif; ?>

<?php if (empty($couple_id)): ?>
  <p>Je zit nog niet in een koppel.</p>
<?php else: ?>

  <p>Couple: <strong>#<?= e((string)$couple_id) ?></strong></p>

  <h2>Deck status</h2>
  <ul>
    <li>In deck: <?= e((string)($counts['in_deck'] ?? 0)) ?></li>
    <li>Getrokken: <?= e((string)($counts['drawn'] ?? 0)) ?></li>
    <li>Completed: <?= e((string)($counts['completed'] ?? 0)) ?></li>
  </ul>

  <form method="post" action="<?= e((\App\Core\Env::config()['app']['base_path'] ?? '') . '/cards/init') ?>" style="margin-bottom: 12px;">
    <input type="hidden" name="csrf" value="<?= e($csrf['init'] ?? '') ?>">
    <button type="submit">Init deck (missing cards toevoegen)</button>
  </form>

  <?php if (empty($current)): ?>
    <h2>Actie</h2>
    <form method="post" action="<?= e((\App\Core\Env::config()['app']['base_path'] ?? '') . '/cards/draw') ?>">
      <input type="hidden" name="csrf" value="<?= e($csrf['draw'] ?? '') ?>">
      <button type="submit">Trek kaart</button>
    </form>
  <?php else: ?>
    <h2>Huidige kaart</h2>
    <p><strong><?= e((string)$current['title']) ?></strong> (difficulty <?= e((string)$current['difficulty']) ?>)</p>
    <p><?= nl2br(e((string)$current['body'])) ?></p>

    <div style="display:flex; gap: 10px; flex-wrap: wrap;">
      <form method="post" action="<?= e((\App\Core\Env::config()['app']['base_path'] ?? '') . '/cards/return') ?>">
        <input type="hidden" name="csrf" value="<?= e($csrf['return'] ?? '') ?>">
        <button type="submit">Terugleggen</button>
      </form>

      <form method="post" action="<?= e((\App\Core\Env::config()['app']['base_path'] ?? '') . '/cards/complete') ?>">
        <input type="hidden" name="csrf" value="<?= e($csrf['complete'] ?? '') ?>">
        <label>Punten (optioneel)
          <input type="number" name="points" min="0" max="1000" placeholder="<?= e((string)((int)$current['difficulty'] * 10)) ?>">
        </label>
        <button type="submit">Afronden</button>
      </form>
    </div>
  <?php endif; ?>

<?php endif; ?>

<hr>
<p>
  <a href="<?= e((\App\Core\Env::config()['app']['base_path'] ?? '') . '/') ?>">Home</a>
  · <a href="<?= e((\App\Core\Env::config()['app']['base_path'] ?? '') . '/mood') ?>">Mood</a>
  · <a href="<?= e((\App\Core\Env::config()['app']['base_path'] ?? '') . '/profile') ?>">Profiel</a>
</p>
