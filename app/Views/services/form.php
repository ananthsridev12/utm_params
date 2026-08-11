<?php $record = $record ?? []; $errors = $errors ?? []; $verticalOptions = $verticalOptions ?? []; ?>
<div class="page-header">
  <h1><?= $mode === 'create' ? 'Add' : 'Edit' ?> <?= e($title) ?></h1>
</div>
<div class="card" style="max-width:640px">
  <form method="post" action="<?= $mode === 'create' ? url($routeBase . '/create') : url($routeBase . '/edit/' . $record['id']) ?>">
    <?= csrf_field() ?>
    <div class="field">
      <label for="name">Name</label>
      <input type="text" id="name" name="name" value="<?= e($record['name'] ?? '') ?>" required autofocus>
      <?php if (!empty($errors['name'])): ?><div class="hint" style="color:var(--danger)"><?= e($errors['name']) ?></div><?php endif; ?>
    </div>
    <div class="field">
      <label for="vertical_id">Vertical</label>
      <select id="vertical_id" name="vertical_id">
        <option value="">— none —</option>
        <?php foreach ($verticalOptions as $v): ?>
          <option value="<?= (int) $v['id'] ?>" <?= (int) ($record['vertical_id'] ?? 0) === (int) $v['id'] ? 'selected' : '' ?>><?= e($v['name'] . ' (' . $v['short_code'] . ')') ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field">
      <label for="slug">Slug</label>
      <input type="text" id="slug" name="slug" value="<?= e($record['slug'] ?? '') ?>" placeholder="e.g. cpq">
      <div class="hint">Used inside tracking URLs/snippets, e.g. cpq, delivery-pods. Auto-filled from name if left blank.</div>
      <?php if (!empty($errors['slug'])): ?><div class="hint" style="color:var(--danger)"><?= e($errors['slug']) ?></div><?php endif; ?>
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
