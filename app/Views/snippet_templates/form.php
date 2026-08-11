<?php $record = $record ?? []; $errors = $errors ?? []; ?>
<div class="page-header"><h1><?= $mode === 'create' ? 'Add' : 'Edit' ?> Snippet Template</h1></div>
<div class="card" style="max-width:760px">
  <form method="post" action="<?= $mode === 'create' ? url('snippet-templates/create') : url('snippet-templates/edit/' . $record['id']) ?>">
    <?= csrf_field() ?>
    <div class="field">
      <label for="name">Name</label>
      <input type="text" id="name" name="name" value="<?= e($record['name'] ?? '') ?>" required autofocus>
      <?php if (!empty($errors['name'])): ?><div class="hint" style="color:var(--danger)"><?= e($errors['name']) ?></div><?php endif; ?>
    </div>
    <?php if ($mode === 'create'): ?>
    <div class="field">
      <label for="key_name">Key</label>
      <input type="text" id="key_name" name="key_name" value="<?= e($record['key_name'] ?? '') ?>" placeholder="e.g. hubspot, salesforce, ga4">
      <div class="hint">Short internal identifier. Auto-filled from the name if left blank.</div>
      <?php if (!empty($errors['key_name'])): ?><div class="hint" style="color:var(--danger)"><?= e($errors['key_name']) ?></div><?php endif; ?>
    </div>
    <?php endif; ?>
    <div class="field">
      <label for="template">Template</label>
      <textarea id="template" name="template" rows="14" class="code" required><?= e($record['template'] ?? '') ?></textarea>
      <div class="hint">Available tokens: <code>{{form_id}}</code> <code>{{page_url}}</code> <code>{{event_name}}</code> <code>{{service_vertical}}</code> <code>{{vertical_name}}</code> <code>{{service}}</code> <code>{{service_js}}</code> <code>{{lead_magnet_name}}</code> <code>{{lead_magnet_name_js}}</code> <code>{{form_type}}</code> <code>{{form_location}}</code> <code>{{funnel_stage}}</code> <code>{{traffic_type}}</code> <code>{{utm_cv}}</code> <code>{{page_type}}</code> <code>{{page_type_short}}</code>. The <code>_js</code> variants render as a quoted JS string or <code>null</code>.</div>
      <?php if (!empty($errors['template'])): ?><div class="hint" style="color:var(--danger)"><?= e($errors['template']) ?></div><?php endif; ?>
    </div>
    <div class="form-actions">
      <button class="btn" type="submit">Save</button>
      <a class="btn secondary" href="<?= url('snippet-templates') ?>">Cancel</a>
    </div>
  </form>
</div>
