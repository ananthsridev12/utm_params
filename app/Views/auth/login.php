<h1>Log in</h1>
<p class="subtitle">UTM Taxonomy &amp; Tracking Manager</p>
<form method="post" action="<?= url('login') ?>">
  <?= csrf_field() ?>
  <div class="field">
    <label for="email">Email</label>
    <input type="email" id="email" name="email" required autofocus>
  </div>
  <div class="field">
    <label for="password">Password</label>
    <input type="password" id="password" name="password" required>
  </div>
  <div class="form-actions">
    <button class="btn" type="submit" style="width:100%">Log in</button>
  </div>
</form>
<div class="switch">
  New here? <a href="<?= url('register') ?>">Create a workspace</a>
</div>
