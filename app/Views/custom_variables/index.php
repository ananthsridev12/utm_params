<div class="page-header">
  <div>
    <h1>Custom Variables</h1>
    <p class="subtitle">Your own data-layer keys, beyond the built-in taxonomy. Usable as <code>{{key_name}}</code> in Snippet Templates and Naming Conventions, and shown as fields on Tracking Configurations and Campaigns.</p>
  </div>
  <div>
    <a class="btn secondary" href="<?= url($routeBase . '/export') ?>">Export CSV</a>
    <?php if (has_role('editor')): ?><a class="btn" href="<?= url($routeBase . '/create') ?>"><?= icon('plus') ?>Add Variable</a><?php endif; ?>
  </div>
</div>

<div class="card">
  <?php if (empty($records)): ?>
    <div class="empty-state">
      <?= icon('terminal') ?>
      <p>No custom variables yet. Add one for anything your dataLayer needs that isn't already covered -- e.g. an A/B test variant, a partner code, an ad format.</p>
    </div>
  <?php else: ?>
  <table>
    <thead><tr><th>Label</th><th>Token</th><th>Source</th><th>Status</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($records as $r): ?>
      <tr>
        <td>
          <?= e($r['label']) ?>
          <?php if ($r['description']): ?><div class="text-muted" style="font-size:11.5px;margin-top:2px"><?= e($r['description']) ?></div><?php endif; ?>
        </td>
        <td><code>{{<?= e($r['key_name']) ?>}}</code></td>
        <td class="text-muted">
          <?php if ($r['source_type'] === 'static_list'): ?>
            List (<?= count($r['options']) ?> option<?= count($r['options']) === 1 ? '' : 's' ?>)
          <?php else: ?>
            Free text
          <?php endif; ?>
        </td>
        <td><?= status_badge($r['status']) ?></td>
        <td class="table-actions">
          <?php if (has_role('editor')): ?><a href="<?= url($routeBase . '/edit/' . $r['id']) ?>">Edit</a><?php endif; ?>
          <?php if (has_role('admin')): ?>
          <form method="post" action="<?= url($routeBase . '/delete/' . $r['id']) ?>" style="display:inline" data-confirm="Delete this variable? Any saved values for it on Tracking Configurations/Campaigns will be removed too.">
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
