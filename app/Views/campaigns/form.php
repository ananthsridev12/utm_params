<?php $record = $record ?? []; $errors = $errors ?? []; ?>
<div class="page-header"><h1><?= $mode === 'create' ? 'Build a' : 'Edit' ?> Campaign Link</h1></div>
<div class="card" style="max-width:680px">
  <form method="post" action="<?= $mode === 'create' ? url($routeBase . '/create') : url($routeBase . '/edit/' . $record['id']) ?>">
    <?= csrf_field() ?>
    <div class="field">
      <label for="name">Campaign name (internal)</label>
      <input type="text" id="name" name="name" value="<?= e($record['name'] ?? '') ?>" required autofocus>
      <?php if (!empty($errors['name'])): ?><div class="hint" style="color:var(--danger)"><?= e($errors['name']) ?></div><?php endif; ?>
    </div>
    <div class="field">
      <label for="target_url">Destination URL</label>
      <input type="text" id="target_url" name="target_url" value="<?= e($record['target_url'] ?? '') ?>" placeholder="https://example.com/lp/page" required>
      <?php if (!empty($errors['target_url'])): ?><div class="hint" style="color:var(--danger)"><?= e($errors['target_url']) ?></div><?php endif; ?>
    </div>
    <div class="form-grid">
      <div class="field">
        <label for="utm_source">utm_source</label>
        <input type="text" id="utm_source" name="utm_source" value="<?= e($record['utm_source'] ?? '') ?>" placeholder="e.g. linkedin" required>
        <?php if (!empty($errors['utm_source'])): ?><div class="hint" style="color:var(--danger)"><?= e($errors['utm_source']) ?></div><?php endif; ?>
      </div>
      <div class="field">
        <label for="utm_medium">utm_medium</label>
        <input type="text" id="utm_medium" name="utm_medium" value="<?= e($record['utm_medium'] ?? '') ?>" placeholder="e.g. paid-social" required>
        <?php if (!empty($errors['utm_medium'])): ?><div class="hint" style="color:var(--danger)"><?= e($errors['utm_medium']) ?></div><?php endif; ?>
      </div>
      <div class="field">
        <label for="utm_campaign">utm_campaign</label>
        <input type="text" id="utm_campaign" name="utm_campaign" value="<?= e($record['utm_campaign'] ?? '') ?>" placeholder="e.g. q3-dt-launch" required>
        <?php if (!empty($errors['utm_campaign'])): ?><div class="hint" style="color:var(--danger)"><?= e($errors['utm_campaign']) ?></div><?php endif; ?>
      </div>
      <div class="field">
        <label for="utm_term">utm_term (optional)</label>
        <input type="text" id="utm_term" name="utm_term" value="<?= e($record['utm_term'] ?? '') ?>">
      </div>
      <div class="field">
        <label for="utm_content">utm_content (optional)</label>
        <input type="text" id="utm_content" name="utm_content" value="<?= e($record['utm_content'] ?? '') ?>">
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
      <button class="btn" type="submit">Save &amp; generate link</button>
      <a class="btn secondary" href="<?= url($routeBase) ?>">Cancel</a>
    </div>
  </form>
  <?php if (!empty($record['generated_url'])): ?>
    <div class="field" style="margin-top:20px">
      <label>Generated URL</label>
      <div class="snippet-box mb-0">
        <pre id="gen-url" style="white-space:pre-wrap;word-break:break-all"><?= e($record['generated_url']) ?></pre>
        <button class="btn small secondary copy-btn" type="button" data-copy-target="#gen-url">Copy</button>
      </div>
    </div>
  <?php endif; ?>
</div>
