<div class="page-header">
  <div>
    <h1>Users &amp; Roles</h1>
    <p class="subtitle">Owner &gt; Admin &gt; Editor &gt; Viewer. Editors can create/edit content; Admins can also delete and manage users.</p>
  </div>
  <a class="btn" href="<?= url('users/invite') ?>">Add User</a>
</div>

<div class="card">
  <h2 class="mt-0">Invite Link</h2>
  <p class="text-muted">Share a link so people can join <strong><?= e($tenant['name']) ?></strong> themselves,
    choosing their own password, instead of you creating each account by hand.</p>
  <?php if (!empty($tenant['invite_enabled']) && !empty($tenant['invite_token'])): ?>
    <?php $inviteUrl = url('join/' . $tenant['invite_token']); ?>
    <div class="field">
      <label for="invite_link_url">Anyone with this link joins as <?= e(ucfirst($tenant['invite_role'])) ?></label>
      <div style="display:flex; gap:8px">
        <input type="text" id="invite_link_url" value="<?= e($inviteUrl) ?>" readonly style="flex:1">
        <button type="button" class="btn secondary" data-copy-target="#invite_link_url">Copy</button>
      </div>
    </div>
    <div class="form-actions" style="margin-top:12px">
      <form method="post" action="<?= url('users/invite-link/generate') ?>" style="display:inline-flex; align-items:center; gap:8px">
        <?= csrf_field() ?>
        <select name="invite_role">
          <option value="viewer" <?= $tenant['invite_role'] === 'viewer' ? 'selected' : '' ?>>Viewer</option>
          <option value="editor" <?= $tenant['invite_role'] === 'editor' ? 'selected' : '' ?>>Editor</option>
          <option value="admin" <?= $tenant['invite_role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
        </select>
        <button class="btn secondary" type="submit">Regenerate link</button>
      </form>
      <form method="post" action="<?= url('users/invite-link/disable') ?>" data-confirm="Disable this invite link? The current link will stop working.">
        <?= csrf_field() ?>
        <button class="btn secondary" type="submit">Disable</button>
      </form>
    </div>
  <?php else: ?>
    <form method="post" action="<?= url('users/invite-link/generate') ?>" style="display:inline-flex; align-items:center; gap:8px">
      <?= csrf_field() ?>
      <label for="invite_role_new" class="text-muted" style="margin:0">New members join as</label>
      <select name="invite_role" id="invite_role_new">
        <option value="viewer" selected>Viewer</option>
        <option value="editor">Editor</option>
        <option value="admin">Admin</option>
      </select>
      <button class="btn" type="submit">Generate invite link</button>
    </form>
  <?php endif; ?>
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
