<div class="page-header">
  <div>
    <h1>Super Admin</h1>
    <p class="subtitle">Every tenant workspace on this install.</p>
  </div>
</div>

<div class="card">
  <table>
    <thead><tr><th>Company</th><th>Slug</th><th>Domain</th><th>Users</th><th>Status</th><th>Created</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($tenants as $t): ?>
      <tr>
        <td><?= e($t['name']) ?></td>
        <td class="text-muted"><?= e($t['slug']) ?></td>
        <td class="text-muted"><?= e($t['primary_domain'] ?? '—') ?></td>
        <td><?= (int) $t['user_count'] ?></td>
        <td><?= status_badge($t['status'] === 'active' ? 'active' : 'disabled') ?></td>
        <td class="text-muted"><?= e($t['created_at']) ?></td>
        <td class="table-actions">
          <?php if ($t['status'] === 'active'): ?>
            <form method="post" action="<?= url('super-admin/suspend/' . $t['id']) ?>" style="display:inline" data-confirm="Suspend this workspace? Its users won't be able to log in.">
              <?= csrf_field() ?>
              <button class="link" type="submit">Suspend</button>
            </form>
          <?php else: ?>
            <form method="post" action="<?= url('super-admin/activate/' . $t['id']) ?>" style="display:inline">
              <?= csrf_field() ?>
              <button class="link" type="submit" style="color:var(--success)">Reactivate</button>
            </form>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
