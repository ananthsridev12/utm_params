<div class="page-header">
  <div>
    <h1>Landing Pages</h1>
    <p class="subtitle">Every landing/entry-door/resource page your team publishes.</p>
  </div>
  <div>
    <a class="btn secondary" href="<?= url($routeBase . '/export') ?>">Export CSV</a>
    <?php if (has_role('editor')): ?><a class="btn" href="<?= url($routeBase . '/create') ?>">Add Landing Page</a><?php endif; ?>
  </div>
</div>

<div class="card">
  <?php if (empty($records)): ?>
    <div class="empty-state">No landing pages yet. <?php if (has_role('editor')): ?><a href="<?= url($routeBase . '/create') ?>">Add your first one</a>.<?php endif; ?></div>
  <?php else: ?>
  <table>
    <thead><tr><th>Name</th><th>URL</th><th>Type</th><th>Vertical</th><th>Owner</th><th>Status</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($records as $r): ?>
      <tr>
        <td><?= e($r['name']) ?></td>
        <td><a href="<?= e($r['url']) ?>" target="_blank" rel="noopener"><?= e($r['url']) ?></a></td>
        <td class="text-muted"><?= e($r['page_type_name'] ?? '—') ?></td>
        <td class="text-muted"><?= e($r['vertical_name'] ?? '—') ?></td>
        <td class="text-muted"><?= e($r['owner_name'] ?? '—') ?></td>
        <td><?= status_badge($r['status']) ?></td>
        <td class="table-actions">
          <?php if (has_role('editor')): ?><a href="<?= url($routeBase . '/edit/' . $r['id']) ?>">Edit</a><?php endif; ?>
          <a href="<?= url('tracking-configs/create', ['landing_page_id' => $r['id']]) ?>">+ Tracking</a>
          <?php if (has_role('admin')): ?>
          <form method="post" action="<?= url($routeBase . '/delete/' . $r['id']) ?>" style="display:inline" data-confirm="Delete this landing page?">
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
