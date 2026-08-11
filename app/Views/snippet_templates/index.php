<div class="page-header">
  <div>
    <h1>Snippet Templates</h1>
    <p class="subtitle">Token-based templates (e.g. GA4 dataLayer push, CRM lead object) used by Tracking Configurations. Use <code>{{form_id}}</code>, <code>{{page_url}}</code>, <code>{{event_name}}</code>, <code>{{service_vertical}}</code>, <code>{{service}}</code>, <code>{{service_js}}</code>, <code>{{lead_magnet_name}}</code>, <code>{{lead_magnet_name_js}}</code>, <code>{{form_type}}</code>, <code>{{form_location}}</code>, <code>{{funnel_stage}}</code>, <code>{{traffic_type}}</code> / <code>{{utm_cv}}</code>, <code>{{page_type}}</code>, <code>{{page_type_short}}</code>.</p>
  </div>
  <?php if (has_role('admin')): ?><a class="btn" href="<?= url('snippet-templates/create') ?>">Add Template</a><?php endif; ?>
</div>

<?php foreach ($records as $r): ?>
  <div class="card">
    <div class="page-header" style="margin-bottom:10px">
      <h3 class="mb-0"><?= e($r['name']) ?> <span class="text-muted" style="font-weight:400">(<?= e($r['key_name']) ?>)</span></h3>
      <div class="table-actions">
        <?php if (has_role('admin')): ?>
          <a href="<?= url('snippet-templates/edit/' . $r['id']) ?>">Edit</a>
          <?php if (!$r['is_default']): ?>
          <form method="post" action="<?= url('snippet-templates/delete/' . $r['id']) ?>" style="display:inline" data-confirm="Delete this template?">
            <?= csrf_field() ?>
            <button class="link" type="submit">Delete</button>
          </form>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    </div>
    <div class="snippet-box">
      <pre id="tpl-<?= (int) $r['id'] ?>"><?= e($r['template']) ?></pre>
      <button class="btn small secondary copy-btn" type="button" data-copy-target="#tpl-<?= (int) $r['id'] ?>">Copy</button>
    </div>
  </div>
<?php endforeach; ?>
