<?php
$record = $record ?? [];
$errors = $errors ?? [];
$readOnly = !has_role('editor');

// Data embedded for the client-side live form_id builder + snippet preview
// (app/assets/js/tracking-config.js). Keeps the preview instant with no
// server round-trip, while validate() in TrackingConfigController remains
// the authoritative, server-side source of truth on save.
$customValues = $customValues ?? [];
$tcData = [
    'pageTypes'      => array_column($pageTypeOptions, null, 'id'),
    'verticals'      => array_column($verticalOptions, null, 'id'),
    'services'       => array_column($serviceOptions, null, 'id'),
    'leadMagnets'    => array_column($leadMagnetOptions, null, 'id'),
    'formTypes'      => array_column($formTypeOptions, null, 'id'),
    'formLocations'  => array_column($formLocationOptions, null, 'id'),
    'funnelStages'   => array_column($funnelStageOptions, null, 'id'),
    'events'         => array_column($eventOptions, null, 'id'),
    'trafficTypes'   => array_column($trafficTypeOptions, null, 'id'),
    'landingPages'   => array_column($landingPageOptions, null, 'id'),
    'templates'      => array_map(fn($t) => ['key' => $t['key_name'], 'name' => $t['name'], 'template' => $t['template']], $snippetTemplates),
    'customVariableKeys' => array_column($customVariables, 'key_name', 'id'),
    'formIdPattern'  => $formIdPattern,
];
?>
<div class="page-header"><h1><?= $mode === 'create' ? 'Add' : ($readOnly ? 'View' : 'Edit') ?> Tracking Configuration</h1></div>

<?php if (!empty($errors)): ?>
  <div class="alert error">
    <?php foreach ($errors as $err): ?><div><?= e($err) ?></div><?php endforeach; ?>
  </div>
<?php endif; ?>

<div class="card" style="max-width:920px">
  <form method="post" action="<?= $mode === 'create' ? url($routeBase . '/create') : url($routeBase . '/edit/' . $record['id']) ?>" id="tc-form">
    <?= csrf_field() ?>
    <fieldset <?= $readOnly ? 'disabled' : '' ?> style="border:0;padding:0;margin:0">
    <div class="field">
      <label for="landing_page_id">Landing page (optional)</label>
      <select id="landing_page_id" name="landing_page_id">
        <option value="">— not tied to a saved Landing Page —</option>
        <?php foreach ($landingPageOptions as $o): ?>
          <option value="<?= (int) $o['id'] ?>" <?= (int) ($record['landing_page_id'] ?? 0) === (int) $o['id'] ? 'selected' : '' ?>><?= e($o['name']) ?></option>
        <?php endforeach; ?>
      </select>
      <div class="hint">Selecting one pre-fills the URL and taxonomy fields below (only if they're still empty).</div>
    </div>
    <div class="field">
      <label for="page_url">Page URL</label>
      <input type="text" id="page_url" name="page_url" value="<?= e($record['page_url'] ?? '') ?>" required>
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
        <label for="form_type_id">Form type</label>
        <select id="form_type_id" name="form_type_id">
          <option value="">— none —</option>
          <?php foreach ($formTypeOptions as $o): ?>
            <option value="<?= (int) $o['id'] ?>" <?= (int) ($record['form_type_id'] ?? 0) === (int) $o['id'] ? 'selected' : '' ?>><?= e($o['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label for="form_location_id">Form location</label>
        <select id="form_location_id" name="form_location_id">
          <option value="">— none —</option>
          <?php foreach ($formLocationOptions as $o): ?>
            <option value="<?= (int) $o['id'] ?>" <?= (int) ($record['form_location_id'] ?? 0) === (int) $o['id'] ? 'selected' : '' ?>><?= e($o['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label for="funnel_stage_id">Funnel stage</label>
        <select id="funnel_stage_id" name="funnel_stage_id">
          <option value="">— none —</option>
          <?php foreach ($funnelStageOptions as $o): ?>
            <option value="<?= (int) $o['id'] ?>" <?= (int) ($record['funnel_stage_id'] ?? 0) === (int) $o['id'] ? 'selected' : '' ?>><?= e($o['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label for="event_id">Event (GA4)</label>
        <select id="event_id" name="event_id">
          <option value="">— none —</option>
          <?php foreach ($eventOptions as $o): ?>
            <option value="<?= (int) $o['id'] ?>" <?= (int) ($record['event_id'] ?? 0) === (int) $o['id'] ? 'selected' : '' ?>><?= e($o['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label for="traffic_type_id">Traffic type</label>
        <select id="traffic_type_id" name="traffic_type_id">
          <option value="">— none —</option>
          <?php foreach ($trafficTypeOptions as $o): ?>
            <option value="<?= (int) $o['id'] ?>" <?= (int) ($record['traffic_type_id'] ?? 0) === (int) $o['id'] ? 'selected' : '' ?>><?= e($o['name']) ?> (<?= e($o['code']) ?>)</option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label for="status">Status</label>
        <select id="status" name="status">
          <option value="active" <?= ($record['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active</option>
          <option value="inactive" <?= ($record['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
        </select>
      </div>
    </div>

    <?php if (!empty($customVariables)): ?>
    <div class="field">
      <label>Custom fields</label>
      <div class="form-grid">
        <?php foreach ($customVariables as $cv): ?>
          <div class="field">
            <label for="custom_<?= (int) $cv['id'] ?>"><?= e($cv['label']) ?></label>
            <?php if ($cv['source_type'] === 'static_list'): ?>
              <select id="custom_<?= (int) $cv['id'] ?>" name="custom_variables[<?= (int) $cv['id'] ?>]" data-custom-key="<?= e($cv['key_name']) ?>">
                <option value="">— none —</option>
                <?php foreach ($cv['options'] as $opt): ?>
                  <option value="<?= e($opt['value']) ?>" <?= ($customValues[$cv['id']] ?? '') === $opt['value'] ? 'selected' : '' ?>><?= e($opt['label']) ?></option>
                <?php endforeach; ?>
              </select>
            <?php else: ?>
              <input type="text" id="custom_<?= (int) $cv['id'] ?>" name="custom_variables[<?= (int) $cv['id'] ?>]" data-custom-key="<?= e($cv['key_name']) ?>" value="<?= e($customValues[$cv['id']] ?? '') ?>">
            <?php endif; ?>
            <?php if ($cv['description']): ?><div class="hint"><?= e($cv['description']) ?></div><?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <div class="field">
      <label for="form_id">form_id <span class="text-muted" style="font-weight:400">(auto-suggested — edit if you need something different)</span></label>
      <input type="text" id="form_id" name="form_id" value="<?= e($record['form_id'] ?? '') ?>" placeholder="auto-generated from the fields above">
      <div class="hint">Built from your Naming Convention pattern (<a href="<?= url('tenant-settings') ?>">Company Settings</a>): <code><?= e($formIdPattern) ?></code>. Must be unique in your workspace.</div>
    </div>
    <div class="field">
      <label for="notes">Notes</label>
      <textarea id="notes" name="notes" rows="2"><?= e($record['notes'] ?? '') ?></textarea>
    </div>
    </fieldset>

    <?php if (!$readOnly): ?>
    <div class="form-actions">
      <button class="btn" type="submit">Save</button>
      <a class="btn secondary" href="<?= url($routeBase) ?>">Cancel</a>
    </div>
    <?php endif; ?>
  </form>
</div>

<div class="card">
  <h2>Snippet preview</h2>
  <p class="text-muted">Rendered live from your <a href="<?= url('snippet-templates') ?>">Snippet Templates</a> using the fields above.</p>
  <div id="snippet-preview"></div>
</div>

<script>window.TC_DATA = <?= json_encode($tcData, JSON_UNESCAPED_SLASHES) ?>;</script>
<script src="<?= asset('js/tracking-config.js') ?>"></script>
