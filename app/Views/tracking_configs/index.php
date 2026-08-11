<div class="page-header">
  <div>
    <h1>Tracking Configurations</h1>
    <p class="subtitle">One row per trackable form/event on a page — the source of truth for form_id slugs and tracking snippets.</p>
  </div>
  <div>
    <a class="btn secondary" href="<?= url($routeBase . '/export') ?>">Export CSV</a>
    <?php if (has_role('editor')): ?><a class="btn" href="<?= url($routeBase . '/create') ?>">Add Tracking Configuration</a><?php endif; ?>
  </div>
</div>

<div class="card">
  <?php if (empty($records)): ?>
    <div class="empty-state">No tracking configurations yet. <?php if (has_role('editor')): ?><a href="<?= url($routeBase . '/create') ?>">Add your first one</a>.<?php endif; ?></div>
  <?php else: ?>
  <table>
    <thead><tr><th>form_id</th><th>Page URL</th><th>Event</th><th>Form type / location</th><th>Funnel stage</th><th>Status</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($records as $r): ?>
      <tr>
        <td><code><?= e($r['form_id']) ?></code></td>
        <td class="text-muted" style="max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= e($r['page_url']) ?></td>
        <td><?= e($r['event_name'] ?? '—') ?></td>
        <td class="text-muted"><?= e($r['form_type_name'] ?? '—') ?> / <?= e($r['form_location_name'] ?? '—') ?></td>
        <td class="text-muted"><?= e($r['funnel_stage_name'] ?? '—') ?></td>
        <td><?= status_badge($r['status']) ?></td>
        <td class="table-actions">
          <a href="<?= url($routeBase . '/edit/' . $r['id']) ?>"><?= has_role('editor') ? 'Edit' : 'View' ?></a>
          <?php if (has_role('admin')): ?>
          <form method="post" action="<?= url($routeBase . '/delete/' . $r['id']) ?>" style="display:inline" data-confirm="Delete this tracking configuration?">
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
