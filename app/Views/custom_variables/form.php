<?php $record = $record ?? []; $errors = $errors ?? []; $options = $record['options'] ?? []; ?>
<div class="page-header"><h1><?= $mode === 'create' ? 'Add' : 'Edit' ?> Custom Variable</h1></div>
<div class="card" style="max-width:640px">
  <form method="post" action="<?= $mode === 'create' ? url($routeBase . '/create') : url($routeBase . '/edit/' . $record['id']) ?>" id="custom-variable-form">
    <?= csrf_field() ?>
    <div class="field">
      <label for="label">Label</label>
      <input type="text" id="label" name="label" value="<?= e($record['label'] ?? '') ?>" placeholder="e.g. AB Test Variant, Ad Format, Version" required autofocus>
      <?php if (!empty($errors['label'])): ?><div class="hint" style="color:var(--danger)"><?= e($errors['label']) ?></div><?php endif; ?>
    </div>
    <div class="field">
      <label for="key_name">Token / dataLayer key</label>
      <input type="text" id="key_name" name="key_name" value="<?= e($record['key_name'] ?? '') ?>" placeholder="auto-filled from label" <?= $mode === 'edit' ? 'readonly' : '' ?>>
      <div class="hint">Used as <code>{{this}}</code> in Snippet Templates and Naming Convention patterns. Lowercase letters, numbers, underscores only.<?= $mode === 'edit' ? ' Can\'t be changed after creation (existing templates/patterns reference it).' : '' ?></div>
      <?php if (!empty($errors['key_name'])): ?><div class="hint" style="color:var(--danger)"><?= e($errors['key_name']) ?></div><?php endif; ?>
    </div>
    <div class="field">
      <label for="source_type">Source</label>
      <select id="source_type" name="source_type">
        <option value="free_text" <?= ($record['source_type'] ?? 'free_text') === 'free_text' ? 'selected' : '' ?>>Free text -- typed in each time</option>
        <option value="static_list" <?= ($record['source_type'] ?? '') === 'static_list' ? 'selected' : '' ?>>Fixed list -- pick from options you define below</option>
      </select>
    </div>

    <div class="field">
      <label>Show on</label>
      <label style="display:flex;align-items:center;gap:7px;font-weight:400;margin-bottom:6px">
        <input type="checkbox" name="applies_to_tracking_config" value="1" style="width:auto" <?= !empty($record['applies_to_tracking_config']) || $record === [] ? 'checked' : '' ?>>
        Tracking Configurations
      </label>
      <label style="display:flex;align-items:center;gap:7px;font-weight:400">
        <input type="checkbox" name="applies_to_campaign" value="1" style="width:auto" <?= !empty($record['applies_to_campaign']) || $record === [] ? 'checked' : '' ?>>
        Campaigns
      </label>
      <div class="hint">Keeps each form limited to the fields that actually apply to it.</div>
    </div>

    <div class="field" id="options-editor" style="<?= ($record['source_type'] ?? 'free_text') === 'static_list' ? '' : 'display:none' ?>">
      <label>Options</label>
      <div id="options-rows">
        <?php if (empty($options)): $options = [['value' => '', 'label' => '']]; endif; ?>
        <?php foreach ($options as $opt): ?>
          <div class="option-row" style="display:flex;gap:8px;margin-bottom:8px">
            <input type="text" name="option_value[]" placeholder="value, e.g. RSA" value="<?= e($opt['value'] ?? '') ?>">
            <input type="text" name="option_label[]" placeholder="label, e.g. Responsive Search Ad" value="<?= e($opt['label'] ?? '') ?>">
            <button type="button" class="btn secondary small remove-option-row">&times;</button>
          </div>
        <?php endforeach; ?>
      </div>
      <button type="button" class="btn secondary small" id="add-option-row"><?= icon('plus') ?>Add option</button>
    </div>

    <div class="field">
      <label for="description">Description</label>
      <textarea id="description" name="description" rows="2"><?= e($record['description'] ?? '') ?></textarea>
    </div>
    <div class="form-grid">
      <div class="field">
        <label for="sort_order">Sort order</label>
        <input type="number" id="sort_order" name="sort_order" value="<?= e($record['sort_order'] ?? 0) ?>">
      </div>
      <div class="field">
        <label for="status">Status</label>
        <select id="status" name="status">
          <option value="active" <?= ($record['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active</option>
          <option value="inactive" <?= ($record['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
        </select>
      </div>
    </div>
    <div class="form-actions">
      <button class="btn" type="submit">Save</button>
      <a class="btn secondary" href="<?= url($routeBase) ?>">Cancel</a>
    </div>
  </form>
</div>

<script src="<?= asset('js/custom-variable-form.js') ?>"></script>
