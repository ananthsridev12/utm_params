<?php
/** @var array|null $currentUser */
/** @var array $flash */
$nav = [
    'Overview' => [
        ['dashboard', 'Dashboard'],
    ],
    'Content' => [
        ['landing-pages', 'Landing Pages'],
        ['tracking-configs', 'Tracking Configurations'],
        ['campaigns', 'Campaign / UTM Builder'],
    ],
    'Taxonomy' => [
        ['verticals', 'Verticals'],
        ['services', 'Services'],
        ['page-types', 'Page Types'],
        ['form-types', 'Form Types'],
        ['form-locations', 'Form Locations'],
        ['funnel-stages', 'Funnel Stages'],
        ['events', 'Events'],
        ['lead-magnets', 'Lead Magnets'],
        ['traffic-types', 'Traffic Types'],
    ],
    'Settings' => [
        ['snippet-templates', 'Snippet Templates'],
        ['users', 'Users & Roles'],
        ['tenant-settings', 'Company Settings'],
    ],
];
$currentRoute = \App\Core\Request::route();
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= isset($title) ? e($title) . ' – ' : '' ?>UTM Taxonomy Manager</title>
<link rel="stylesheet" href="<?= asset('css/app.css') ?>">
</head>
<body>
<div class="layout">
  <aside class="sidebar">
    <div class="brand">UTM Taxonomy
      <small><?= e($currentUser['tenant_name'] ?? 'Manager') ?></small>
    </div>
    <nav>
      <?php foreach ($nav as $section => $items): ?>
        <div class="section-label"><?= e($section) ?></div>
        <?php foreach ($items as [$route, $label]): ?>
          <a href="<?= url($route) ?>" class="<?= str_starts_with($currentRoute, $route) ? 'active' : '' ?>"><?= e($label) ?></a>
        <?php endforeach; ?>
      <?php endforeach; ?>
      <?php if ($currentUser && !empty($currentUser['is_super_admin'])): ?>
        <div class="section-label">Platform</div>
        <a href="<?= url('super-admin') ?>" class="<?= str_starts_with($currentRoute, 'super-admin') ? 'active' : '' ?>">Super Admin</a>
      <?php endif; ?>
    </nav>
  </aside>
  <div class="main">
    <div class="topbar">
      <div></div>
      <div class="who">
        <?php if ($currentUser): ?>
          <?= e($currentUser['name']) ?> &middot; <span class="text-muted"><?= e(ucfirst($currentUser['role'])) ?></span>
          &middot; <a href="<?= url('logout') ?>">Log out</a>
        <?php endif; ?>
      </div>
    </div>
    <div class="content">
      <?php foreach (($flash['success'] ?? []) as $m): ?>
        <div class="alert success"><?= e($m) ?></div>
      <?php endforeach; ?>
      <?php foreach (($flash['error'] ?? []) as $m): ?>
        <div class="alert error"><?= e($m) ?></div>
      <?php endforeach; ?>
      <?= $content ?>
    </div>
  </div>
</div>
<script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>
