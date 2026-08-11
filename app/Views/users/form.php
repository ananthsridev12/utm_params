<?php $record = $record ?? []; $errors = $errors ?? []; ?>
<div class="page-header"><h1><?= $mode === 'create' ? 'Add User' : 'Edit User' ?></h1></div>
<div class="card" style="max-width:520px">
  <form method="post" action="<?= $mode === 'create' ? url('users/invite') : url('users/edit/' . $record['id']) ?>">
    <?= csrf_field() ?>
    <div class="field">
      <label for="name">Name</label>
      <input type="text" id="name" name="name" value="<?= e($record['name'] ?? '') ?>" required autofocus>
      <?php if (!empty($errors['name'])): ?><div class="hint" style="color:var(--danger)"><?= e($errors['name']) ?></div><?php endif; ?>
    </div>
    <?php if ($mode === 'create'): ?>
    <div class="field">
      <label for="email">Email</label>
      <input type="email" id="email" name="email" value="<?= e($record['email'] ?? '') ?>" required>
      <?php if (!empty($errors['email'])): ?><div class="hint" style="color:var(--danger)"><?= e($errors['email']) ?></div><?php endif; ?>
    </div>
    <div class="field">
      <label for="password">Temporary password</label>
      <input type="text" id="password" name="password" placeholder="at least 8 characters" required>
      <div class="hint">There's no outbound email on this install -- share this password with them directly, they can't reset it themselves yet.</div>
      <?php if (!empty($errors['password'])): ?><div class="hint" style="color:var(--danger)"><?= e($errors['password']) ?></div><?php endif; ?>
    </div>
    <?php endif; ?>
    <div class="field">
      <label for="role">Role</label>
      <select id="role" name="role">
        <?php foreach (['viewer' => 'Viewer (read-only)', 'editor' => 'Editor (create/edit)', 'admin' => 'Admin (+ delete, manage users)', 'owner' => 'Owner (full control)'] as $val => $label): ?>
          <option value="<?= $val ?>" <?= ($record['role'] ?? 'viewer') === $val ? 'selected' : '' ?>><?= $label ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <?php if ($mode === 'edit'): ?>
    <div class="field">
      <label for="status">Status</label>
      <select id="status" name="status">
        <option value="active" <?= ($record['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active</option>
        <option value="disabled" <?= ($record['status'] ?? '') === 'disabled' ? 'selected' : '' ?>>Disabled</option>
      </select>
    </div>
    <?php endif; ?>
    <div class="form-actions">
      <button class="btn" type="submit">Save</button>
      <a class="btn secondary" href="<?= url('users') ?>">Cancel</a>
    </div>
  </form>

  <?php if ($mode === 'edit'): ?>
    <hr style="margin:22px 0;border:none;border-top:1px solid var(--border)">
    <h3>Reset password</h3>
    <form method="post" action="<?= url('users/reset-password/' . $record['id']) ?>">
      <?= csrf_field() ?>
      <div class="field">
        <label for="new_password">New temporary password</label>
        <input type="text" id="new_password" name="password" placeholder="at least 8 characters" required>
      </div>
      <button class="btn secondary" type="submit">Reset password</button>
    </form>
  <?php endif; ?>
</div>
