<div class="page-header">
  <div>
    <h1>Dashboard</h1>
    <p class="subtitle">Welcome back, <?= e($currentUser['name'] ?? '') ?>.</p>
  </div>
</div>

<div class="stat-grid">
  <div class="stat-card"><div class="num"><?= (int) $counts['landing_pages'] ?></div><div class="label">Landing Pages</div></div>
  <div class="stat-card"><div class="num"><?= (int) $counts['tracking_configs'] ?></div><div class="label">Tracking Configurations</div></div>
  <div class="stat-card"><div class="num"><?= (int) $counts['campaigns'] ?></div><div class="label">Campaigns</div></div>
  <div class="stat-card"><div class="num"><?= (int) $counts['verticals'] ?></div><div class="label">Verticals</div></div>
  <div class="stat-card"><div class="num"><?= (int) $counts['services'] ?></div><div class="label">Services</div></div>
  <div class="stat-card"><div class="num"><?= (int) $counts['lead_magnets'] ?></div><div class="label">Lead Magnets</div></div>
</div>

<div class="card">
  <h2>Recent activity</h2>
  <?php if (empty($recentActivity)): ?>
    <p class="text-muted mb-0">No activity yet. Start by adding a Vertical and a Landing Page.</p>
  <?php else: ?>
    <table>
      <thead><tr><th>When</th><th>Who</th><th>Action</th></tr></thead>
      <tbody>
      <?php foreach ($recentActivity as $a): ?>
        <tr>
          <td><?= e($a['created_at']) ?></td>
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
  <ol>
    <li>Review your <a href="<?= url('verticals') ?>">Verticals</a>, <a href="<?= url('services') ?>">Services</a> and other taxonomy lists — they're pre-filled with sample data.</li>
    <li>Add a <a href="<?= url('landing-pages') ?>">Landing Page</a>.</li>
    <li>Create a <a href="<?= url('tracking-configs') ?>">Tracking Configuration</a> for it to get its auto-generated form_id and tracking snippets.</li>
    <li>Customize your <a href="<?= url('snippet-templates') ?>">Snippet Templates</a> to match your own GA4/CRM setup.</li>
  </ol>
</div>
