(function () {
  var data = window.TC_DATA;
  if (!data) return;

  var $ = function (id) { return document.getElementById(id); };
  var fields = ['landing_page_id', 'page_url', 'page_type_id', 'vertical_id', 'service_id',
    'lead_magnet_id', 'form_type_id', 'form_location_id', 'funnel_stage_id', 'event_id', 'traffic_type_id'];
  var formIdField = $('form_id');
  var formIdTouched = !!(formIdField && formIdField.value);

  if (formIdField) {
    formIdField.addEventListener('input', function () { formIdTouched = true; });
  }

  function lookup(map, id, key) {
    if (!id || !map[id]) return '';
    return map[id][key] || '';
  }

  function currentValues() {
    var v = {};
    fields.forEach(function (f) {
      var el = $(f);
      v[f] = el ? el.value : '';
    });
    return v;
  }

  // Auto-fill from the selected Landing Page, but never overwrite a field the user already set.
  function applyLandingPage() {
    var lp = data.landingPages[$('landing_page_id').value];
    if (!lp) return;
    var map = { page_url: lp.url, page_type_id: lp.page_type_id, vertical_id: lp.vertical_id, service_id: lp.service_id, lead_magnet_id: lp.lead_magnet_id };
    Object.keys(map).forEach(function (f) {
      var el = $(f);
      if (el && !el.value && map[f]) el.value = map[f];
    });
  }

  function slugify(s) {
    return (s || '').toString().toLowerCase();
  }

  function buildFormId(v) {
    var pageTypeShort = slugify(lookup(data.pageTypes, v.page_type_id, 'short_code'));
    var verticalCode = slugify(lookup(data.verticals, v.vertical_id, 'short_code'));
    var serviceSlug = slugify(lookup(data.services, v.service_id, 'slug'));
    var formType = slugify(lookup(data.formTypes, v.form_type_id, 'name'));
    var formLocation = slugify(lookup(data.formLocations, v.form_location_id, 'name'));
    return [pageTypeShort, verticalCode, serviceSlug, formType, formLocation].join('-');
  }

  function jsStr(v) { return v ? ("'" + String(v).replace(/'/g, "\\'") + "'") : 'null'; }

  function buildContext(v) {
    var leadMagnetSlug = lookup(data.leadMagnets, v.lead_magnet_id, 'slug');
    var serviceSlug = lookup(data.services, v.service_id, 'slug');
    return {
      form_id: formIdField ? formIdField.value : '',
      page_url: v.page_url || '',
      event_name: lookup(data.events, v.event_id, 'name'),
      service_vertical: lookup(data.verticals, v.vertical_id, 'short_code'),
      vertical_name: lookup(data.verticals, v.vertical_id, 'name'),
      service: serviceSlug,
      service_js: jsStr(serviceSlug),
      lead_magnet_name: leadMagnetSlug,
      lead_magnet_name_js: jsStr(leadMagnetSlug),
      form_type: lookup(data.formTypes, v.form_type_id, 'name'),
      form_location: lookup(data.formLocations, v.form_location_id, 'name'),
      funnel_stage: lookup(data.funnelStages || {}, v.funnel_stage_id, 'name'),
      traffic_type: lookup(data.trafficTypes, v.traffic_type_id, 'code'),
      utm_cv: lookup(data.trafficTypes, v.traffic_type_id, 'code'),
      page_type: lookup(data.pageTypes, v.page_type_id, 'name'),
      page_type_short: lookup(data.pageTypes, v.page_type_id, 'short_code'),
    };
  }

  function renderTemplate(tpl, ctx) {
    return tpl.replace(/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/g, function (m, key) {
      return Object.prototype.hasOwnProperty.call(ctx, key) ? ctx[key] : '';
    });
  }

  function renderPreview() {
    var v = currentValues();
    if (!formIdTouched && formIdField) {
      formIdField.value = buildFormId(v);
    }
    var ctx = buildContext(v);
    var container = $('snippet-preview');
    if (!container) return;
    container.innerHTML = '';
    (data.templates || []).forEach(function (t, i) {
      var box = document.createElement('div');
      box.className = 'snippet-box';
      var label = document.createElement('div');
      label.className = 'hint';
      label.style.marginBottom = '4px';
      label.textContent = t.name;
      var pre = document.createElement('pre');
      pre.id = 'snippet-pre-' + i;
      pre.textContent = renderTemplate(t.template, ctx);
      var btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'btn small secondary copy-btn';
      btn.textContent = 'Copy';
      btn.setAttribute('data-copy-target', '#snippet-pre-' + i);
      box.appendChild(label);
      box.appendChild(pre);
      box.appendChild(btn);
      container.appendChild(box);
    });
    // Re-bind copy buttons freshly added to the DOM.
    container.querySelectorAll('[data-copy-target]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var target = document.querySelector(btn.getAttribute('data-copy-target'));
        if (!target) return;
        navigator.clipboard.writeText(target.innerText).then(function () {
          var original = btn.textContent;
          btn.textContent = 'Copied!';
          setTimeout(function () { btn.textContent = original; }, 1500);
        });
      });
    });
  }

  fields.forEach(function (f) {
    var el = $(f);
    if (!el) return;
    el.addEventListener('change', function () {
      if (f === 'landing_page_id') applyLandingPage();
      renderPreview();
    });
  });
  if (formIdField) formIdField.addEventListener('input', renderPreview);

  renderPreview();
})();
