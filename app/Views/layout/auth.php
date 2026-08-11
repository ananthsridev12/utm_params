<?php /** @var array $flash */ ?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= isset($title) ? e($title) . ' – ' : '' ?>UTM Taxonomy Manager</title>
<link rel="icon" href="data:image/svg+xml,<?= rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><rect width="24" height="24" rx="6" fill="%234f46e5"/><path d="M7 7v6a5 5 0 0 0 10 0V7" stroke="white" stroke-width="2" fill="none" stroke-linecap="round"/></svg>') ?>">
<link rel="stylesheet" href="<?= asset('css/app.css') ?>">
</head>
<body>
<div class="auth-wrap">
  <div class="auth-brand-panel">
    <div class="mark">U</div>
    <h2>Stop hand-typing UTM taxonomy into a spreadsheet.</h2>
    <p>One workspace per company: manage verticals, forms, funnel stages and landing pages, and get tracking snippets generated for you — consistent, unique, every time.</p>
    <div class="feature-list">
      <div><?= icon('check-circle') ?> Auto-generated, collision-checked form_id slugs</div>
      <div><?= icon('check-circle') ?> Live GA4 + CRM snippet preview, no copy-paste errors</div>
      <div><?= icon('check-circle') ?> Your team, your roles, your data — fully isolated per company</div>
    </div>
  </div>
  <div class="auth-form-panel">
    <div class="auth-card">
      <?php foreach (($flash['success'] ?? []) as $m): ?>
        <div class="alert success"><?= icon('check-circle') ?><?= e($m) ?></div>
      <?php endforeach; ?>
      <?php foreach (($flash['error'] ?? []) as $m): ?>
        <div class="alert error"><?= e($m) ?></div>
      <?php endforeach; ?>
      <?= $content ?>
    </div>
  </div>
</div>
</body>
</html>
