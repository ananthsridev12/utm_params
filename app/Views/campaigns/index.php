<div class="page-header">
  <div>
    <h1>Campaign / UTM Link Builder</h1>
    <p class="subtitle">Build and store trackable URLs and full campaign briefs for outbound campaigns (ads, email, social, partners).</p>
  </div>
  <div>
    <a class="btn secondary" href="<?= url($routeBase . '/export') ?>">Export CSV</a>
    <?php if (has_role('editor')): ?><a class="btn" href="<?= url($routeBase . '/create') ?>">Build a Link</a><?php endif; ?>
  </div>
</div>

<div class="card">
  <?php if (empty($records)): ?>
    <div class="empty-state">No campaign links yet.</div>
  <?php else: ?>
  <form method="post" action="<?= url($routeBase . '/export-excel-selected') ?>" id="campaigns-export-form">
    <?= csrf_field() ?>
    <div class="form-actions" style="margin-bottom:2px">
      <button class="btn secondary small" type="submit"><?= icon('download') ?>Export Selected (Excel)</button>
      <button class="btn secondary small" type="submit" formaction="<?= url($routeBase . '/export-google-bulk-selected') ?>"><?= icon('download') ?>Export Selected (Google Ads Bulk CSV)</button>
    </div>
    <p class="hint" style="margin-bottom:10px">Google Ads Bulk CSV covers Campaign/Ad Group/Keyword rows in Google Ads Editor's bulk-upload format (Google Ads channels only, others are skipped) -- ad copy (headlines/final URL) isn't collected here and needs to be added separately before uploading. Verify column names against your account's own downloaded template before a real upload, since Google's format can change.</p>
    <table>
      <thead><tr><th style="width:28px"><input type="checkbox" id="select-all-campaigns" style="width:auto"></th><th>Name</th><th>Channel</th><th>Source / Medium / Campaign</th><th>Generated URL</th><th>Status</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($records as $r): ?>
        <tr>
          <td><input type="checkbox" name="ids[]" value="<?= (int) $r['id'] ?>" class="campaign-select" style="width:auto"></td>
          <td>
            <?= e($r['name']) ?>
            <?php if (!empty($r['landing_page_name'])): ?><div class="text-muted" style="font-size:11.5px;margin-top:2px">→ <?= e($r['landing_page_name']) ?></div><?php endif; ?>
          </td>
          <td class="text-muted"><?= e($r['channel_name'] ?? '—') ?></td>
          <td class="text-muted">
            <?= e($r['utm_source']) ?> / <?= e($r['utm_medium']) ?> / <?= e($r['utm_campaign']) ?>
            <?php if (!empty($r['traffic_type_code'])): ?><div style="margin-top:2px">utm_cv: <code><?= e($r['traffic_type_code']) ?></code></div><?php endif; ?>
          </td>
          <td>
            <div class="snippet-box mb-0">
              <pre id="camp-<?= (int) $r['id'] ?>" style="white-space:pre-wrap;word-break:break-all"><?= e($r['generated_url']) ?></pre>
              <button class="btn small secondary copy-btn" type="button" data-copy-target="#camp-<?= (int) $r['id'] ?>">Copy</button>
            </div>
          </td>
          <td><?= status_badge($r['status']) ?></td>
          <td class="table-actions">
            <a href="<?= url($routeBase . '/edit/' . $r['id']) ?>"><?= has_role('editor') ? 'Edit' : 'View' ?></a>
            <a href="<?= url($routeBase . '/export-excel/' . $r['id']) ?>">Excel</a>
            <?php if (($r['channel_platform_type'] ?? 'other') === 'google_ads'): ?>
              <a href="<?= url($routeBase . '/export-google-bulk/' . $r['id']) ?>">Google Bulk CSV</a>
            <?php endif; ?>
            <?php if (has_role('admin')): ?>
            <form method="post" action="<?= url($routeBase . '/delete/' . $r['id']) ?>" style="display:inline" data-confirm="Delete this campaign?">
              <?= csrf_field() ?>
              <button class="link" type="submit">Delete</button>
            </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </form>
  <?php endif; ?>
</div>

<script>
(function () {
  var selectAll = document.getElementById('select-all-campaigns');
  if (!selectAll) return;
  selectAll.addEventListener('change', function () {
    document.querySelectorAll('.campaign-select').forEach(function (cb) { cb.checked = selectAll.checked; });
  });
})();
</script>
