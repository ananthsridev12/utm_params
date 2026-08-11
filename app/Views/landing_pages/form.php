<?php $record = $record ?? []; $errors = $errors ?? []; ?>
<div class="page-header"><h1><?= $mode === 'create' ? 'Add' : 'Edit' ?> Landing Page</h1></div>
<div class="card" style="max-width:760px">
  <form method="post" action="<?= $mode === 'create' ? url($routeBase . '/create') : url($routeBase . '/edit/' . $record['id']) ?>">
    <?= csrf_field() ?>
    <div class="field">
      <label for="name">Name</label>
      <input type="text" id="name" name="name" value="<?= e($record['name'] ?? '') ?>" required autofocus>
      <?php if (!empty($errors['name'])): ?><div class="hint" style="color:var(--danger)"><?= e($errors['name']) ?></div><?php endif; ?>
    </div>
    <div class="field">
      <label for="url">URL</label>
      <input type="text" id="url" name="url" value="<?= e($record['url'] ?? '') ?>" placeholder="https://example.com/lp/page" required>
      <?php if (!empty($errors['url'])): ?><div class="hint" style="color:var(--danger)"><?= e($errors['url']) ?></div><?php endif; ?>
    </div>
    <div class="form-grid">
      <div class="field">
        <label for="page_type_id">Page type</label>
        <select id="page_type_id" name="page_type_id">
          <option value="">— none —</option>
          <?php foreach ($pageTypeOptions as $o): ?>
            <option value="<?= (int) $o['id'] ?>" <?= (int) ($record['page_type_id'] ?? 0) === (int) $o['id'] ? 'selected' : '' ?>><?= e($o['name']) ?> (<?= e($o['short_code']) ?>)</option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label for="vertical_id">Vertical</label>
        <select id="vertical_id" name="vertical_id">
          <option value="">— none —</option>
          <?php foreach ($verticalOptions as $o): ?>
            <option value="<?= (int) $o['id'] ?>" <?= (int) ($record['vertical_id'] ?? 0) === (int) $o['id'] ? 'selected' : '' ?>><?= e($o['name']) ?> (<?= e($o['short_code']) ?>)</option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label for="service_id">Service</label>
        <select id="service_id" name="service_id">
          <option value="">— none —</option>
          <?php foreach ($serviceOptions as $o): ?>
            <option value="<?= (int) $o['id'] ?>" <?= (int) ($record['service_id'] ?? 0) === (int) $o['id'] ? 'selected' : '' ?>><?= e($o['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label for="lead_magnet_id">Lead magnet</label>
        <select id="lead_magnet_id" name="lead_magnet_id">
          <option value="">— none —</option>
          <?php foreach ($leadMagnetOptions as $o): ?>
            <option value="<?= (int) $o['id'] ?>" <?= (int) ($record['lead_magnet_id'] ?? 0) === (int) $o['id'] ? 'selected' : '' ?>><?= e($o['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label for="owner_user_id">Owner</label>
        <select id="owner_user_id" name="owner_user_id">
          <option value="">— unassigned —</option>
          <?php foreach ($ownerOptions as $o): ?>
            <option value="<?= (int) $o['id'] ?>" <?= (int) ($record['owner_user_id'] ?? 0) === (int) $o['id'] ? 'selected' : '' ?>><?= e($o['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label for="status">Status</label>
        <select id="status" name="status">
          <?php foreach (['draft' => 'Draft', 'live' => 'Live', 'archived' => 'Archived'] as $val => $label): ?>
            <option value="<?= $val ?>" <?= ($record['status'] ?? 'draft') === $val ? 'selected' : '' ?>><?= $label ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label for="template">Template</label>
        <input type="text" id="template" name="template" value="<?= e($record['template'] ?? '') ?>" placeholder="optional, e.g. lp-standard-v2">
      </div>
      <div class="field">
        <label for="thumbnail_url">Thumbnail URL</label>
        <input type="text" id="thumbnail_url" name="thumbnail_url" value="<?= e($record['thumbnail_url'] ?? '') ?>" placeholder="optional">
      </div>
    </div>
    <div class="field">
      <label for="notes">Notes</label>
      <textarea id="notes" name="notes" rows="3"><?= e($record['notes'] ?? '') ?></textarea>
    </div>
    <div class="form-actions">
      <button class="btn" type="submit">Save</button>
      <a class="btn secondary" href="<?= url($routeBase) ?>">Cancel</a>
    </div>
  </form>
</div>
