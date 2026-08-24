<div class="page-header">
  <div>
    <h1>Ad Channels</h1>
    <p class="subtitle">Drive the Campaign / UTM Builder: pick a channel to auto-fill utm_source/medium, relabel the keyword field, and reveal channel-specific extra parameters.</p>
  </div>
  <div>
    <a class="btn secondary" href="<?= url($routeBase . '/export') ?>">Export CSV</a>
    <?php if (has_role('editor')): ?><a class="btn" href="<?= url($routeBase . '/create') ?>"><?= icon('plus') ?>Add Channel</a><?php endif; ?>
  </div>
</div>

<div class="card">
  <?php if (empty($records)): ?>
    <div class="empty-state">No channels yet.</div>
  <?php else: ?>
  <table>
    <thead><tr><th>Name</th><th>Code</th><th>Platform</th><th>Recommended medium(s)</th><th>Keyword</th><th>Extra params</th><th>Status</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($records as $r): ?>
      <tr>
        <td>
          <?= e($r['name']) ?>
          <?php if ($r['description']): ?><div class="text-muted" style="font-size:11.5px;margin-top:2px"><?= e($r['description']) ?></div><?php endif; ?>
        </td>
        <td><?php if ($r['short_code']): ?><code><?= e($r['short_code']) ?></code><?php else: ?><span class="text-muted">—</span><?php endif; ?></td>
        <td class="text-muted"><?php
          $platformLabels = ['google_ads' => 'Google Ads', 'meta_ads' => 'Meta Ads', 'linkedin_ads' => 'LinkedIn Ads', 'other' => '—'];
          echo e($platformLabels[$r['platform_type'] ?? 'other'] ?? '—');
        ?></td>
        <td class="text-muted"><?= e($r['recommended_mediums'] ?: ($r['default_utm_medium'] ?: '—')) ?></td>
        <td><?php if (!empty($r['requires_term'])): ?><span class="badge warn">Required</span><?php else: ?><span class="text-muted">Optional</span><?php endif; ?></td>
        <td class="text-muted"><?= e($r['extra_param_labels'] ?: '—') ?></td>
        <td><?= status_badge($r['status']) ?></td>
        <td class="table-actions">
          <?php if (has_role('editor')): ?><a href="<?= url($routeBase . '/edit/' . $r['id']) ?>">Edit</a><?php endif; ?>
          <?php if (has_role('admin')): ?>
          <form method="post" action="<?= url($routeBase . '/delete/' . $r['id']) ?>" style="display:inline" data-confirm="Delete this channel?">
            <?= csrf_field() ?>
            <button class="link" type="submit">Delete</button>
          </form>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</div>
