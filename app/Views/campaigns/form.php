<?php
$record = $record ?? [];
$errors = $errors ?? [];
$existingExtraParams = [];
if (!empty($record['extra_params'])) {
    $decoded = json_decode($record['extra_params'], true);
    if (is_array($decoded)) $existingExtraParams = $decoded;
}
$customValues = $customValues ?? [];
$campaignData = [
    'channels' => $channelsJson,
    'landingPages' => array_column($landingPageOptions, null, 'id'),
    'verticals' => array_column($verticalOptions, null, 'id'),
    'services' => array_column($serviceOptions, null, 'id'),
    'existingExtraParams' => $existingExtraParams,
    'namePattern' => $campaignNamePattern,
    'nextSeq' => $nextSeq,
];
?>
<div class="page-header"><h1><?= $mode === 'create' ? 'Build a' : 'Edit' ?> Campaign Link</h1></div>
<div class="card" style="max-width:680px">
  <form method="post" action="<?= $mode === 'create' ? url($routeBase . '/create') : url($routeBase . '/edit/' . $record['id']) ?>" id="campaign-form">
    <?= csrf_field() ?>
    <div class="field">
      <label for="name">Campaign name (internal)</label>
      <input type="text" id="name" name="name" value="<?= e($record['name'] ?? '') ?>" required autofocus>
      <?php if ($campaignNamePattern): ?>
        <div class="hint">Auto-suggested from your <a href="<?= url('tenant-settings') ?>">Naming Convention</a> (only fills this in if it's still empty) as you fill in the fields below.</div>
      <?php endif; ?>
      <?php if (!empty($errors['name'])): ?><div class="hint" style="color:var(--danger)"><?= e($errors['name']) ?></div><?php endif; ?>
    </div>

    <div class="field">
      <label for="landing_page_id">Landing page (optional)</label>
      <select id="landing_page_id" name="landing_page_id">
        <option value="">— pick one to fill the destination URL, or type your own below —</option>
        <?php foreach ($landingPageOptions as $o): ?>
          <option value="<?= (int) $o['id'] ?>" <?= (int) ($record['landing_page_id'] ?? 0) === (int) $o['id'] ? 'selected' : '' ?>><?= e($o['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field">
      <label for="target_url">Destination URL</label>
      <input type="text" id="target_url" name="target_url" value="<?= e($record['target_url'] ?? '') ?>" placeholder="https://example.com/lp/page" required>
      <?php if (!empty($errors['target_url'])): ?><div class="hint" style="color:var(--danger)"><?= e($errors['target_url']) ?></div><?php endif; ?>
    </div>

    <div class="field">
      <label for="channel_id">Channel (optional)</label>
      <select id="channel_id" name="channel_id">
        <option value="">— none —</option>
        <?php foreach ($channelOptions as $o): ?>
          <option value="<?= (int) $o['id'] ?>" <?= (int) ($record['channel_id'] ?? 0) === (int) $o['id'] ? 'selected' : '' ?>><?= e($o['name']) ?></option>
        <?php endforeach; ?>
      </select>
      <div class="hint">Fills in the usual utm_source/medium for this channel and adds its typical extra parameters below. You can still edit anything by hand. Manage channels under <a href="<?= url('channels') ?>">Ad Channels</a>.</div>
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
        <label for="utm_term" id="utm_term_label">utm_term (optional)</label>
        <input type="text" id="utm_term" name="utm_term" value="<?= e($record['utm_term'] ?? '') ?>" placeholder="e.g. a keyword">
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

    <div id="extra-params-container"></div>

    <?php if (!empty($customVariables)): ?>
    <div class="field">
      <label>Additional details</label>
      <div class="form-grid">
        <?php foreach ($customVariables as $cv): ?>
          <div class="field">
            <label for="ccustom_<?= (int) $cv['id'] ?>"><?= e($cv['label']) ?></label>
            <?php if ($cv['source_type'] === 'static_list'): ?>
              <select id="ccustom_<?= (int) $cv['id'] ?>" name="custom_variables[<?= (int) $cv['id'] ?>]" data-custom-key="<?= e($cv['key_name']) ?>">
                <option value="">— none —</option>
                <?php foreach ($cv['options'] as $opt): ?>
                  <option value="<?= e($opt['value']) ?>" <?= ($customValues[$cv['id']] ?? '') === $opt['value'] ? 'selected' : '' ?>><?= e($opt['label']) ?></option>
                <?php endforeach; ?>
              </select>
            <?php else: ?>
              <input type="text" id="ccustom_<?= (int) $cv['id'] ?>" name="custom_variables[<?= (int) $cv['id'] ?>]" data-custom-key="<?= e($cv['key_name']) ?>" value="<?= e($customValues[$cv['id']] ?? '') ?>">
            <?php endif; ?>
            <?php if ($cv['description']): ?><div class="hint"><?= e($cv['description']) ?></div><?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <div class="form-actions">
      <button class="btn" type="submit">Save &amp; generate link</button>
      <a class="btn secondary" href="<?= url($routeBase) ?>">Cancel</a>
    </div>
  </form>

  <div class="field" style="margin-top:20px">
    <label>Generated URL <span class="text-muted" style="font-weight:400">(live preview)</span></label>
    <div class="snippet-box mb-0">
      <pre id="gen-url-preview" style="white-space:pre-wrap;word-break:break-all"><?= e($record['generated_url'] ?? '') ?></pre>
      <button class="btn small secondary copy-btn" type="button" data-copy-target="#gen-url-preview">Copy</button>
    </div>
  </div>
</div>

<script>window.CAMPAIGN_DATA = <?= json_encode($campaignData, JSON_UNESCAPED_SLASHES) ?>;</script>
<script src="<?= asset('js/campaign-builder.js') ?>"></script>
