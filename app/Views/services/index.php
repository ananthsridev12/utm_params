<div class="page-header">
  <div>
    <h1><?= e($title) ?>s</h1>
    <p class="subtitle">Services offered within a vertical (e.g. CPQ, Delivery Pods, ESI) used in tracking URLs.</p>
  </div>
  <div>
    <a class="btn secondary" href="<?= url($routeBase . '/export') ?>">Export CSV</a>
    <?php if (has_role('editor')): ?><a class="btn" href="<?= url($routeBase . '/create') ?>">Add <?= e($title) ?></a><?php endif; ?>
  </div>
</div>

<div class="card">
  <?php if (empty($records)): ?>
    <div class="empty-state">No <?= e(strtolower($title)) ?>s yet.</div>
  <?php else: ?>
  <table>
    <thead><tr><th>Name</th><th>Slug</th><th>Vertical</th><th>Status</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($records as $r): ?>
      <tr>
        <td><?= e($r['name']) ?></td>
        <td><code><?= e($r['slug']) ?></code></td>
        <td class="text-muted"><?= e($r['vertical_name'] ?? '—') ?></td>
        <td><?= status_badge($r['status']) ?></td>
        <td class="table-actions">
          <?php if (has_role('editor')): ?><a href="<?= url($routeBase . '/edit/' . $r['id']) ?>">Edit</a><?php endif; ?>
          <?php if (has_role('admin')): ?>
          <form method="post" action="<?= url($routeBase . '/delete/' . $r['id']) ?>" style="display:inline" data-confirm="Delete this <?= e(strtolower($title)) ?>?">
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
