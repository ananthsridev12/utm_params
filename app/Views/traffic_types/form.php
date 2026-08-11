<?php $record = $record ?? []; $errors = $errors ?? []; ?>
<div class="page-header">
  <h1><?= $mode === 'create' ? 'Add' : 'Edit' ?> <?= e($title) ?></h1>
</div>
<div class="card" style="max-width:640px">
  <form method="post" action="<?= $mode === 'create' ? url($routeBase . '/create') : url($routeBase . '/edit/' . $record['id']) ?>">
    <?= csrf_field() ?>
    <div class="field">
      <label for="code">Code</label>
      <input type="text" id="code" name="code" value="<?= e($record['code'] ?? '') ?>" placeholder="e.g. mar" required autofocus>
      <?php if (!empty($errors['code'])): ?><div class="hint" style="color:var(--danger)"><?= e($errors['code']) ?></div><?php endif; ?>
    </div>
    <div class="field">
      <label for="name">Name</label>
      <input type="text" id="name" name="name" value="<?= e($record['name'] ?? '') ?>" placeholder="e.g. Marketing" required>
      <?php if (!empty($errors['name'])): ?><div class="hint" style="color:var(--danger)"><?= e($errors['name']) ?></div><?php endif; ?>
    </div>
    <div class="field">
      <label for="description">Description</label>
      <textarea id="description" name="description" rows="2"><?= e($record['description'] ?? '') ?></textarea>
    </div>
    <div class="field">
      <label for="status">Status</label>
      <select id="status" name="status">
        <option value="active" <?= ($record['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active</option>
        <option value="inactive" <?= ($record['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
      </select>
    </div>
    <div class="form-actions">
      <button class="btn" type="submit">Save</button>
      <a class="btn secondary" href="<?= url($routeBase) ?>">Cancel</a>
    </div>
  </form>
</div>
