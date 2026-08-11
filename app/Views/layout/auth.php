<?php /** @var array $flash */ ?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= isset($title) ? e($title) . ' – ' : '' ?>UTM Taxonomy Manager</title>
<link rel="stylesheet" href="<?= asset('css/app.css') ?>">
</head>
<body>
<div class="auth-wrap">
  <div class="auth-card">
    <?php foreach (($flash['success'] ?? []) as $m): ?>
      <div class="alert success"><?= e($m) ?></div>
    <?php endforeach; ?>
    <?php foreach (($flash['error'] ?? []) as $m): ?>
      <div class="alert error"><?= e($m) ?></div>
    <?php endforeach; ?>
    <?= $content ?>
  </div>
</div>
</body>
</html>
