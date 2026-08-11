<?php
/** @var array|null $currentUser */
/** @var array $flash */
$nav = [
    'Overview' => [
        ['dashboard', 'Dashboard', 'dashboard'],
    ],
    'Content' => [
        ['landing-pages', 'Landing Pages', 'browser'],
        ['tracking-configs', 'Tracking Configurations', 'code'],
        ['campaigns', 'Campaign / UTM Builder', 'link'],
    ],
    'Taxonomy' => [
        ['verticals', 'Verticals', 'layers'],
        ['services', 'Services', 'briefcase'],
        ['page-types', 'Page Types', 'file-text'],
        ['form-types', 'Form Types', 'clipboard'],
        ['form-locations', 'Form Locations', 'map-pin'],
        ['funnel-stages', 'Funnel Stages', 'filter'],
        ['events', 'Events', 'flag'],
        ['lead-magnets', 'Lead Magnets', 'gift'],
        ['traffic-types', 'Traffic Types', 'compass'],
    ],
    'Settings' => [
        ['snippet-templates', 'Snippet Templates', 'terminal'],
        ['users', 'Users & Roles', 'users'],
        ['tenant-settings', 'Company Settings', 'settings'],
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
<link rel="icon" href="data:image/svg+xml,<?= rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><rect width="24" height="24" rx="6" fill="%234f46e5"/><path d="M7 7v6a5 5 0 0 0 10 0V7" stroke="white" stroke-width="2" fill="none" stroke-linecap="round"/></svg>') ?>">
<link rel="stylesheet" href="<?= asset('css/app.css') ?>">
</head>
<body>
<div class="layout">
  <aside class="sidebar">
    <div class="brand">
      <div class="mark">U</div>
      <div>
        <div class="name">UTM Taxonomy</div>
        <small><?= e($currentUser['tenant_name'] ?? 'Manager') ?></small>
      </div>
    </div>
    <nav>
      <?php foreach ($nav as $section => $items): ?>
        <div class="section-label"><?= e($section) ?></div>
        <?php foreach ($items as [$route, $label, $iconName]): ?>
          <a href="<?= url($route) ?>" class="<?= str_starts_with($currentRoute, $route) ? 'active' : '' ?>"><?= icon($iconName) ?><span><?= e($label) ?></span></a>
        <?php endforeach; ?>
      <?php endforeach; ?>
      <?php if ($currentUser && !empty($currentUser['is_super_admin'])): ?>
        <div class="section-label">Platform</div>
        <a href="<?= url('super-admin') ?>" class="<?= str_starts_with($currentRoute, 'super-admin') ? 'active' : '' ?>"><?= icon('shield') ?><span>Super Admin</span></a>
      <?php endif; ?>
    </nav>
  </aside>
  <div class="main">
    <div class="topbar">
      <div></div>
      <div class="who">
        <?php if ($currentUser): ?>
          <span><?= e($currentUser['name']) ?> &middot; <span class="text-muted"><?= e(ucfirst($currentUser['role'])) ?></span></span>
          <div class="avatar" title="<?= e($currentUser['name']) ?>"><?= e(initials($currentUser['name'])) ?></div>
          <a href="<?= url('logout') ?>" title="Log out" style="color:var(--text-muted);display:flex"><?= icon('logout') ?></a>
        <?php endif; ?>
      </div>
    </div>
    <div class="content">
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
<script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>
