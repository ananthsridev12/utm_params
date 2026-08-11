<div class="page-header"><h1>Company Settings</h1></div>
<div class="card" style="max-width:520px">
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
    <div class="form-actions">
      <button class="btn" type="submit">Save</button>
    </div>
  </form>
</div>
