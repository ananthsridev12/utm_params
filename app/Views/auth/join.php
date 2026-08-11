<?php $old = $old ?? []; ?>
<?php if (!$tenant): ?>
  <h1>Invite link invalid</h1>
  <p class="subtitle">This invite link is invalid, expired, or has been disabled by the workspace admin. Ask them to send you a fresh one.</p>
  <div class="switch">
    <a href="<?= url('login') ?>">Back to log in</a>
  </div>
<?php else: ?>
  <h1>Join <?= e($tenant['name']) ?></h1>
  <p class="subtitle">You'll join as <strong><?= e(ucfirst($tenant['invite_role'])) ?></strong>. Set up your own account below.</p>
  <form method="post" action="<?= url('join/' . $token) ?>">
    <?= csrf_field() ?>
    <div class="field">
      <label for="name">Your name</label>
      <input type="text" id="name" name="name" value="<?= e($old['name'] ?? '') ?>" required autofocus>
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
      <button class="btn" type="submit" style="width:100%">Join workspace</button>
    </div>
  </form>
  <div class="switch">
    Already have an account? <a href="<?= url('login') ?>">Log in</a>
  </div>
<?php endif; ?>
