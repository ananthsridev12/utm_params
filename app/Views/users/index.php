<div class="page-header">
  <div>
    <h1>Users &amp; Roles</h1>
    <p class="subtitle">Owner &gt; Admin &gt; Editor &gt; Viewer. Editors can create/edit content; Admins can also delete and manage users.</p>
  </div>
  <a class="btn" href="<?= url('users/invite') ?>">Add User</a>
</div>

<div class="card">
  <table>
    <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Last login</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($records as $r): ?>
      <tr>
        <td><?= e($r['name']) ?><?= $r['id'] === ($currentUser['id'] ?? null) ? ' <span class="text-muted">(you)</span>' : '' ?></td>
        <td><?= e($r['email']) ?></td>
        <td><span class="badge active"><?= e(ucfirst($r['role'])) ?></span></td>
        <td><?= status_badge($r['status'] === 'active' ? 'active' : 'disabled') ?></td>
        <td class="text-muted"><?= e($r['last_login_at'] ?? 'never') ?></td>
        <td class="table-actions">
          <a href="<?= url('users/edit/' . $r['id']) ?>">Edit</a>
          <?php if ($r['id'] !== ($currentUser['id'] ?? null)): ?>
          <form method="post" action="<?= url('users/delete/' . $r['id']) ?>" style="display:inline" data-confirm="Remove this user?">
            <?= csrf_field() ?>
            <button class="link" type="submit">Remove</button>
          </form>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
