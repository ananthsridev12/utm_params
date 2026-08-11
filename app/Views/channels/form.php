<?php $record = $record ?? []; $errors = $errors ?? []; ?>
<div class="page-header"><h1><?= $mode === 'create' ? 'Add' : 'Edit' ?> Channel</h1></div>
<div class="card" style="max-width:640px">
  <form method="post" action="<?= $mode === 'create' ? url($routeBase . '/create') : url($routeBase . '/edit/' . $record['id']) ?>">
    <?= csrf_field() ?>
    <div class="field">
      <label for="name">Name</label>
      <input type="text" id="name" name="name" value="<?= e($record['name'] ?? '') ?>" placeholder="e.g. Google Ads - Search" required autofocus>
      <?php if (!empty($errors['name'])): ?><div class="hint" style="color:var(--danger)"><?= e($errors['name']) ?></div><?php endif; ?>
    </div>
    <div class="form-grid">
      <div class="field">
        <label for="short_code">Short code</label>
        <input type="text" id="short_code" name="short_code" value="<?= e($record['short_code'] ?? '') ?>" placeholder="e.g. GA">
        <div class="hint">Used as the <code>{{channel}}</code> token in Naming Conventions (Company Settings), e.g. PA1-DT-CPQ-<strong>GA</strong>-RSA-...</div>
      </div>
      <div class="field">
        <label for="default_utm_source">Default utm_source</label>
        <input type="text" id="default_utm_source" name="default_utm_source" value="<?= e($record['default_utm_source'] ?? '') ?>" placeholder="e.g. google">
        <div class="hint">Pre-fills the Campaign form when this channel is picked (only if the field is still empty).</div>
      </div>
      <div class="field">
        <label for="default_utm_medium">Default utm_medium</label>
        <input type="text" id="default_utm_medium" name="default_utm_medium" value="<?= e($record['default_utm_medium'] ?? '') ?>" placeholder="e.g. cpc">
      </div>
      <div class="field">
        <label for="recommended_sources">Recommended utm_source values</label>
        <input type="text" id="recommended_sources" name="recommended_sources" value="<?= e($record['recommended_sources'] ?? '') ?>" placeholder="e.g. google,youtube">
        <div class="hint">Comma-separated. Shown as clickable suggestions on the Campaign form so people don't have to guess GA4-friendly values.</div>
        <?php if (!empty($errors['recommended_sources'])): ?><div class="hint" style="color:var(--danger)"><?= e($errors['recommended_sources']) ?></div><?php endif; ?>
      </div>
      <div class="field">
        <label for="recommended_mediums">Recommended utm_medium values</label>
        <input type="text" id="recommended_mediums" name="recommended_mediums" value="<?= e($record['recommended_mediums'] ?? '') ?>" placeholder="e.g. cpc,ppc,paidsearch">
        <div class="hint">Comma-separated, GA4-recognized mediums for this channel (e.g. <code>cpc,ppc,paidsearch</code> for paid search, <code>paid-social</code> for social ads) -- these decide which Default Channel Group the campaign lands in inside GA4 reports.</div>
        <?php if (!empty($errors['recommended_mediums'])): ?><div class="hint" style="color:var(--danger)"><?= e($errors['recommended_mediums']) ?></div><?php endif; ?>
      </div>
      <div class="field">
        <label for="term_label">"Keyword" field label</label>
        <input type="text" id="term_label" name="term_label" value="<?= e($record['term_label'] ?? '') ?>" placeholder="e.g. Keyword, Audience">
        <div class="hint">Relabels utm_term on the Campaign form for this channel. Leave blank to keep the generic "utm_term" label.</div>
      </div>
      <div class="field">
        <label style="display:flex;align-items:center;gap:7px;font-weight:400">
          <input type="checkbox" name="requires_term" value="1" style="width:auto" <?= !empty($record['requires_term']) ? 'checked' : '' ?>>
          Keyword/utm_term is required for this channel
        </label>
        <div class="hint">Only keyword-targeted Search channels (Google/Bing Search) typically need this checked. Leave unchecked for Display, Social, Video, Email, etc. -- utm_term stays optional there.</div>
      </div>
      <div class="field">
        <label for="extra_param_labels">Extra parameters</label>
        <input type="text" id="extra_param_labels" name="extra_param_labels" value="<?= e($record['extra_param_labels'] ?? '') ?>" placeholder="e.g. network,device,matchtype">
        <div class="hint">Comma-separated list of extra query-param names, appended to the generated URL (e.g. Google Ads ValueTrack params like {device}, {matchtype}).</div>
        <?php if (!empty($errors['extra_param_labels'])): ?><div class="hint" style="color:var(--danger)"><?= e($errors['extra_param_labels']) ?></div><?php endif; ?>
      </div>
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
