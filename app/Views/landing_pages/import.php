<?php $results = $results ?? null; ?>
<div class="page-header">
  <div>
    <h1>Import Landing Pages</h1>
    <p class="subtitle">Bulk-create Landing Pages from a CSV file.</p>
  </div>
</div>

<?php if ($results): ?>
  <div class="card">
    <h2>Import finished</h2>
    <p><?= (int) $results['created'] ?> of <?= (int) $results['total'] ?> row<?= $results['total'] === 1 ? '' : 's' ?> created.</p>
    <?php if (!empty($results['log'])): ?>
      <table>
        <thead><tr><th>Result</th></tr></thead>
        <tbody>
        <?php foreach ($results['log'] as $line): ?>
          <tr><td class="<?= str_contains($line, 'skipped') ? '' : 'text-muted' ?>"><?= e($line) ?></td></tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
    <div class="form-actions">
      <a class="btn" href="<?= url($routeBase) ?>">Back to Landing Pages</a>
      <a class="btn secondary" href="<?= url($routeBase . '/import') ?>">Import another file</a>
    </div>
  </div>
<?php else: ?>
  <div class="card" style="max-width:640px">
    <h2 class="mt-0">1. Download the template</h2>
    <p class="text-muted">Fill it in and re-upload below. Columns match other modules by <strong>name</strong> (not ID) -- e.g. <code>vertical</code> should be the exact name shown under <a href="<?= url('verticals') ?>">Verticals</a>. Anything that doesn't match is left blank with a note in the results, it won't block the rest of the row.</p>
    <a class="btn secondary" href="<?= url($routeBase . '/import-template') ?>"><?= icon('download') ?>Download CSV template</a>

    <h2>2. Upload your file</h2>
    <form method="post" action="<?= url($routeBase . '/import') ?>" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <div class="field">
        <label for="csv_file">CSV file</label>
        <input type="file" id="csv_file" name="csv_file" accept=".csv,text/csv" required>
      </div>
      <div class="form-actions">
        <button class="btn" type="submit">Import</button>
        <a class="btn secondary" href="<?= url($routeBase) ?>">Cancel</a>
      </div>
    </form>
  </div>
<?php endif; ?>
