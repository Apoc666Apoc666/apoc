<?php declare(strict_types=1); ?>

<h1>Mood</h1>

<?php if (!empty($msg)): ?>
  <p style="color: #060;"><?= e($msg) ?></p>
<?php endif; ?>
<?php if (!empty($error)): ?>
  <p style="color: #b00;"><?= e($error) ?></p>
<?php endif; ?>

<?php if (empty($couple_id)): ?>
  <p>Je zit nog niet in een koppel.</p>
  <p class="small">In fase 5 bouwen we invites/accept. Voor nu kun je in dev-mode tijdelijk koppelen.</p>
<?php else: ?>
  <p>Couple: <strong>#<?= e((string)$couple_id) ?></strong></p>

  <h2>Huidige (actieve) moods</h2>
  <?php if (empty($items)): ?>
    <p>Geen actieve moods.</p>
  <?php else: ?>
    <table>
      <tr><th>User</th><th>Mood</th><th>Note</th><th>Expires</th></tr>
      <?php foreach ($items as $it): ?>
        <tr>
          <td><?= e((string)$it['user_id']) ?></td>
          <td><?= e((string)$it['mood']) ?></td>
          <td><?= e((string)($it['note'] ?? '')) ?></td>
          <td><?= e((string)$it['expires_at']) ?></td>
        </tr>
      <?php endforeach; ?>
    </table>
  <?php endif; ?>

  <h2>Stel jouw mood in</h2>
  <form method="post" action="<?= e((\App\Core\Env::config()['app']['base_path'] ?? '') . '/mood') ?>">
    <input type="hidden" name="csrf" value="<?= e($csrf ?? '') ?>">

    <div style="margin-bottom: 10px;">
      <label>Mood<br>
        <select name="mood" required>
          <option value="yes">yes</option>
          <option value="maybe">maybe</option>
          <option value="no">no</option>
        </select>
      </label>
    </div>

    <div style="margin-bottom: 10px;">
      <label>Note (optioneel, max 200)<br>
        <input name="note" type="text" maxlength="200" style="width: 100%;">
      </label>
    </div>

    <div style="margin-bottom: 10px;">
      <label>Expiry (minuten, 5..1440)<br>
        <input name="ttl_minutes" type="number" min="5" max="1440" value="120" required>
      </label>
    </div>

    <button type="submit">Opslaan</button>
  </form>
<?php endif; ?>

<hr>
<p>
  <a href="<?= e((\App\Core\Env::config()['app']['base_path'] ?? '') . '/') ?>">Home</a>
  · <a href="<?= e((\App\Core\Env::config()['app']['base_path'] ?? '') . '/profile') ?>">Profiel</a>
</p>


