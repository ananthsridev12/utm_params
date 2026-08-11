<?php
$stats = [
    ['landing_pages', 'Landing Pages', 'browser', 'landing-pages'],
    ['tracking_configs', 'Tracking Configurations', 'code', 'tracking-configs'],
    ['campaigns', 'Campaigns', 'link', 'campaigns'],
    ['verticals', 'Verticals', 'layers', 'verticals'],
    ['services', 'Services', 'briefcase', 'services'],
    ['lead_magnets', 'Lead Magnets', 'gift', 'lead-magnets'],
];
?>
<div class="page-header">
  <div>
    <h1>Dashboard</h1>
    <p class="subtitle">Welcome back, <?= e($currentUser['name'] ?? '') ?>.</p>
  </div>
</div>

<div class="stat-grid">
  <?php foreach ($stats as [$key, $label, $iconName, $route]): ?>
    <a class="stat-card" href="<?= url($route) ?>" style="color:inherit;text-decoration:none">
      <div class="icon"><?= icon($iconName) ?></div>
      <div>
        <div class="num"><?= (int) $counts[$key] ?></div>
        <div class="label"><?= e($label) ?></div>
      </div>
    </a>
  <?php endforeach; ?>
</div>

<div class="card">
  <h2>Recent activity</h2>
  <?php if (empty($recentActivity)): ?>
    <div class="empty-state" style="padding:24px 0">
      <?= icon('inbox') ?>
      <p>No activity yet. Start by adding a Vertical and a Landing Page.</p>
    </div>
  <?php else: ?>
    <table>
      <thead><tr><th>When</th><th>Who</th><th>Action</th></tr></thead>
      <tbody>
      <?php foreach ($recentActivity as $a): ?>
        <tr>
          <td class="text-muted"><?= e($a['created_at']) ?></td>
          <td><?= e($a['user_name'] ?? 'System') ?></td>
          <td><?= e($a['description']) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>

<div class="card">
  <h2>Quick start</h2>
  <ol style="margin:0;padding-left:20px;color:var(--text-muted);line-height:2">
    <li>Review your <a href="<?= url('verticals') ?>">Verticals</a>, <a href="<?= url('services') ?>">Services</a> and other taxonomy lists — they're pre-filled with sample data.</li>
    <li>Add a <a href="<?= url('landing-pages') ?>">Landing Page</a>.</li>
    <li>Create a <a href="<?= url('tracking-configs') ?>">Tracking Configuration</a> for it to get its auto-generated form_id and tracking snippets.</li>
    <li>Customize your <a href="<?= url('snippet-templates') ?>">Snippet Templates</a> to match your own GA4/CRM setup.</li>
  </ol>
</div>
