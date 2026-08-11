<div class="page-header"><h1>Company Settings</h1></div>
<div class="card" style="max-width:640px">
  <form method="post" action="<?= url('tenant-settings') ?>">
    <?= csrf_field() ?>
    <div class="field">
      <label for="name">Company name</label>
      <input type="text" id="name" name="name" value="<?= e($tenant['name'] ?? '') ?>" required autofocus>
    </div>
    <div class="field">
      <label for="primary_domain">Primary domain</label>
      <input type="text" id="primary_domain" name="primary_domain" value="<?= e($tenant['primary_domain'] ?? '') ?>" placeholder="example.com">
      <div class="hint">Informational only -- where your landing pages live.</div>
    </div>
    <div class="field">
      <label>Workspace slug</label>
      <input type="text" value="<?= e($tenant['slug'] ?? '') ?>" disabled>
    </div>

    <hr style="margin:22px 0;border:none;border-top:1px solid var(--border)">
    <h3 class="mb-0">Naming Conventions</h3>
    <p class="text-muted" style="font-size:12.5px;margin-top:4px">
      Token patterns, same <code>{{token}}</code> syntax as <a href="<?= url('snippet-templates') ?>">Snippet Templates</a>.
      Built-in tokens: <code>{{page_type_short}}</code> <code>{{service_vertical}}</code> <code>{{vertical_name}}</code>
      <code>{{service}}</code> <code>{{service_name}}</code>
      <code>{{form_type}}</code> <code>{{form_location}}</code> <code>{{funnel_stage}}</code> <code>{{event_name}}</code>
      <code>{{traffic_type}}</code> <code>{{lead_magnet_name}}</code> <code>{{channel}}</code> <code>{{channel_name}}</code>
      <code>{{utm_source}}</code> <code>{{utm_medium}}</code> <code>{{utm_campaign}}</code> <code>{{seq}}</code>.
      <code>{{service}}</code>/<code>{{service_vertical}}</code> are lowercase URL-safe slugs/codes;
      <code>{{service_name}}</code>/<code>{{vertical_name}}</code> are the display names as typed under
      <a href="<?= url('services') ?>">Services</a>/<a href="<?= url('verticals') ?>">Verticals</a> (e.g. "CPQ" instead of "cpq") --
      pick whichever casing you want. Plus any <a href="<?= url('custom-variables') ?>">Custom Variable</a> you've defined, by its token.
    </p>

    <div class="field">
      <label for="tracking_form_id_pattern">Tracking Configuration form_id pattern</label>
      <input type="text" id="tracking_form_id_pattern" name="tracking_form_id_pattern" value="<?= e($tenant['tracking_form_id_pattern'] ?? '') ?>" required>
      <div class="hint">Note: <code>{{seq}}</code> isn't available here -- form_id must stay stable when you edit a record later, and a running count would drift. Use a Custom Variable if you need a stable per-record number.</div>
    </div>
    <div class="field">
      <label for="campaign_name_pattern">Campaign name suggestion (optional)</label>
      <input type="text" id="campaign_name_pattern" name="campaign_name_pattern" value="<?= e($tenant['campaign_name_pattern'] ?? '') ?>" placeholder="e.g. PA{{seq}}-{{service_vertical}}-{{service_name}}-{{channel}}-{{format}}-{{objective}}-{{date}}-{{version}}">
      <div class="hint">Suggests the Campaign "name" field (only fills it if still empty) -- e.g. <code>PA{{seq}}-{{service_vertical}}-{{service_name}}-{{channel}}-{{format}}-{{objective}}-{{date}}-{{version}}</code> produces <code>PA1-DT-CPQ-GA-RSA-Traffic-Aug2026-V1</code>. <code>{{service_vertical}}</code>/<code>{{service_name}}</code> come from the campaign's linked Landing Page, if any. Leave blank to keep Name a plain free-text field.</div>
    </div>

    <div class="form-actions">
      <button class="btn" type="submit">Save</button>
    </div>
  </form>
</div>
