<?php $old = $old ?? []; ?>
<h1>Create your workspace</h1>
<p class="subtitle">One account per company. You'll be the Owner and can invite teammates after.</p>
<form method="post" action="<?= url('register') ?>">
  <?= csrf_field() ?>
  <div class="field">
    <label for="company_name">Company name</label>
    <input type="text" id="company_name" name="company_name" value="<?= e($old['company_name'] ?? '') ?>" required autofocus>
  </div>
  <div class="field">
    <label for="name">Your name</label>
    <input type="text" id="name" name="name" value="<?= e($old['name'] ?? '') ?>" required>
  </div>
  <div class="field">
    <label for="email">Work email</label>
    <input type="email" id="email" name="email" value="<?= e($old['email'] ?? '') ?>" required>
  </div>
  <div class="field">
    <label for="password">Password</label>
    <input type="password" id="password" name="password" required minlength="8">
    <div class="hint">At least 8 characters.</div>
  </div>
  <div class="field">
    <label for="password_confirm">Confirm password</label>
    <input type="password" id="password_confirm" name="password_confirm" required minlength="8">
  </div>
  <div class="form-actions">
    <button class="btn" type="submit" style="width:100%">Create workspace</button>
  </div>
</form>
<div class="switch">
  Already have an account? <a href="<?= url('login') ?>">Log in</a>
</div>
