(function () {
  var data = window.TC_DATA;
  if (!data) return;

  var $ = function (id) { return document.getElementById(id); };
  var fields = ['landing_page_id', 'page_url', 'page_type_id', 'vertical_id', 'service_id',
    'lead_magnet_id', 'form_type_id', 'form_location_id', 'funnel_stage_id', 'event_id', 'traffic_type_id'];
  var customFields = Array.prototype.slice.call(document.querySelectorAll('[data-custom-key]'));
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

  function jsStr(v) { return v ? ("'" + String(v).replace(/'/g, "\\'") + "'") : 'null'; }

  function customValues() {
    var out = {};
    customFields.forEach(function (el) {
      out[el.getAttribute('data-custom-key')] = el.value || '';
    });
    return out;
  }

  function buildContext(v) {
    var leadMagnetSlug = lookup(data.leadMagnets, v.lead_magnet_id, 'slug');
    var serviceSlug = lookup(data.services, v.service_id, 'slug');
    var ctx = {
      form_id: formIdField ? formIdField.value : '',
      page_url: v.page_url || '',
      event_name: lookup(data.events, v.event_id, 'name'),
      service_vertical: lookup(data.verticals, v.vertical_id, 'short_code'),
      vertical_name: lookup(data.verticals, v.vertical_id, 'name'),
      service: serviceSlug,
      service_name: lookup(data.services, v.service_id, 'name'),
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
    // Custom Variables merge in last, keyed by their own key_name -- same rule
    // as App\Models\SnippetTemplate::buildContext() server-side.
    var cv = customValues();
    Object.keys(cv).forEach(function (k) { ctx[k] = cv[k]; });
    return ctx;
  }

  function renderTemplate(tpl, ctx) {
    return tpl.replace(/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/g, function (m, key) {
      return Object.prototype.hasOwnProperty.call(ctx, key) ? ctx[key] : '';
    });
  }

  // Mirrors TrackingConfigController::validate()'s server-side form_id build:
  // render the tenant's Naming Convention pattern against the current field
  // values, then lowercase/trim/collapse dashes into a clean slug.
  function buildFormId(ctx) {
    if (!data.formIdPattern) return '';
    var raw = renderTemplate(data.formIdPattern, ctx).toLowerCase();
    raw = raw.replace(/^-+|-+$/g, '').replace(/-{2,}/g, '-');
    return raw;
  }

  function renderPreview() {
    var v = currentValues();
    var ctx = buildContext(v);
    if (!formIdTouched && formIdField) {
      formIdField.value = buildFormId(ctx);
      ctx.form_id = formIdField.value;
    }
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
  customFields.forEach(function (el) {
    el.addEventListener('change', renderPreview);
    el.addEventListener('input', renderPreview);
  });
  if (formIdField) formIdField.addEventListener('input', renderPreview);

  renderPreview();
})();
