<?php declare(strict_types=1); ?>

<div class="card">
  <h1>Chaos</h1>

  <?php if (!empty($msg)): ?>
    <div class="flash ok"><?= e($msg) ?></div>
  <?php endif; ?>
  <?php if (!empty($error)): ?>
    <div class="flash err"><?= e($error) ?></div>
  <?php endif; ?>

  <?php if (empty($couple_id)): ?>
    <p class="small">Geen koppel.</p>
  <?php else: ?>
    <p class="badge">Couple: <strong>#<?= e((string)$couple_id) ?></strong></p>

    <div class="hr"></div>

    <table>
      <tr>
        <th>Actief</th>
        <th>Naam</th>
        <th>Type</th>
        <th>Config</th>
        <th>Toggle</th>
      </tr>

      <?php foreach (($rules ?? []) as $r): ?>
        <?php
          $rid = (int)$r['id'];
          $isRuleActive = (int)$r['is_active'] === 1;
          $isEnabled = in_array($rid, $enabled ?? [], true);
          $cfg = $r['config'] ?? [];
        ?>
        <tr>
          <td><?= $isRuleActive ? '✅' : '—' ?></td>
          <td><?= e((string)$r['name']) ?></td>
          <td><code><?= e((string)$r['rule_type']) ?></code></td>
          <td class="small"><code><?= e(json_encode($cfg, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)) ?></code></td>
          <td>
            <?php if (!$isRuleActive): ?>
              <span class="small">rule inactive</span>
            <?php else: ?>
              <form method="post" action="<?= e((\App\Core\Env::config()['app']['base_path'] ?? '') . '/chaos/toggle') ?>">
                <input type="hidden" name="csrf" value="<?= e($csrf ?? '') ?>">
                <input type="hidden" name="rule_id" value="<?= e((string)$rid) ?>">
                <input type="hidden" name="enable" value="<?= $isEnabled ? '0' : '1' ?>">
                <button class="btn <?= $isEnabled ? '' : 'primary' ?>" type="submit">
                  <?= $isEnabled ? 'Uitzetten' : 'Aanzetten' ?>
                </button>
              </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </table>

    <p class="small" style="margin-top:12px;">
      Deze regels beïnvloeden <strong>draw</strong> en <strong>complete</strong>.
    </p>
  <?php endif; ?>
</div>
