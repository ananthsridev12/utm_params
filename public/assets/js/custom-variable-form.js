(function () {
  var labelEl = document.getElementById('label');
  var keyEl = document.getElementById('key_name');
  var sourceTypeEl = document.getElementById('source_type');
  var optionsEditor = document.getElementById('options-editor');
  var optionsRows = document.getElementById('options-rows');
  var addRowBtn = document.getElementById('add-option-row');
  if (!labelEl) return;

  var keyTouched = !!(keyEl && keyEl.value) || (keyEl && keyEl.hasAttribute('readonly'));

  function slugify(s) {
    return (s || '').toString().toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_+|_+$/g, '');
  }

  if (keyEl && !keyEl.hasAttribute('readonly')) {
    keyEl.addEventListener('input', function () { keyTouched = true; });
    labelEl.addEventListener('input', function () {
      if (!keyTouched) keyEl.value = slugify(labelEl.value);
    });
  }

  function toggleOptionsEditor() {
    if (!optionsEditor || !sourceTypeEl) return;
    optionsEditor.style.display = sourceTypeEl.value === 'static_list' ? '' : 'none';
  }
  if (sourceTypeEl) {
    sourceTypeEl.addEventListener('change', toggleOptionsEditor);
    toggleOptionsEditor();
  }

  function addOptionRow() {
    var row = document.createElement('div');
    row.className = 'option-row';
    row.style.cssText = 'display:flex;gap:8px;margin-bottom:8px';
    row.innerHTML =
      '<input type="text" name="option_value[]" placeholder="value, e.g. RSA">' +
      '<input type="text" name="option_label[]" placeholder="label, e.g. Responsive Search Ad">' +
      '<button type="button" class="btn secondary small remove-option-row">&times;</button>';
    optionsRows.appendChild(row);
  }
  if (addRowBtn) addRowBtn.addEventListener('click', addOptionRow);

  if (optionsRows) {
    optionsRows.addEventListener('click', function (e) {
      if (e.target.classList.contains('remove-option-row')) {
        var rows = optionsRows.querySelectorAll('.option-row');
        if (rows.length > 1) {
          e.target.closest('.option-row').remove();
        } else {
          rows[0].querySelectorAll('input').forEach(function (i) { i.value = ''; });
        }
      }
    });
  }
})();
