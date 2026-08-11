(function () {
  var data = window.CAMPAIGN_DATA;
  if (!data) return;

  var $ = function (id) { return document.getElementById(id); };
  var channelsById = {};
  (data.channels || []).forEach(function (c) { channelsById[c.id] = c; });

  var nameEl = $('name');
  var landingPageEl = $('landing_page_id');
  var targetUrlEl = $('target_url');
  var channelEl = $('channel_id');
  var sourceEl = $('utm_source');
  var mediumEl = $('utm_medium');
  var campaignEl = $('utm_campaign');
  var termEl = $('utm_term');
  var termLabelEl = $('utm_term_label');
  var contentEl = $('utm_content');
  var extraContainer = $('extra-params-container');
  var preview = $('gen-url-preview');
  var sourceSuggestions = $('utm_source_suggestions');
  var mediumSuggestions = $('utm_medium_suggestions');
  var customFields = Array.prototype.slice.call(document.querySelectorAll('[data-custom-key]'));

  var currentExtraValues = Object.assign({}, data.existingExtraParams || {});
  var nameTouched = !!(nameEl && nameEl.value);
  if (nameEl) nameEl.addEventListener('input', function () { nameTouched = true; });

  function applyLandingPage() {
    var lp = data.landingPages[landingPageEl.value];
    if (lp && lp.url && !targetUrlEl.value) {
      targetUrlEl.value = lp.url;
    }
  }

  function renderExtraParams(channel, preserveValues) {
    var previousValues = {};
    if (preserveValues) {
      extraContainer.querySelectorAll('input[data-extra-key]').forEach(function (input) {
        previousValues[input.getAttribute('data-extra-key')] = input.value;
      });
    }
    extraContainer.innerHTML = '';
    if (!channel || !channel.extra_params || !channel.extra_params.length) return;

    var grid = document.createElement('div');
    grid.className = 'form-grid';
    channel.extra_params.forEach(function (p) {
      var field = document.createElement('div');
      field.className = 'field';
      var label = document.createElement('label');
      label.textContent = p.label + ' (optional)';
      label.setAttribute('for', 'extra_' + p.key);
      var input = document.createElement('input');
      input.type = 'text';
      input.id = 'extra_' + p.key;
      input.name = 'extra_params[' + p.key + ']';
      input.setAttribute('data-extra-key', p.key);
      var existing = previousValues.hasOwnProperty(p.key) ? previousValues[p.key] : (currentExtraValues[p.key] || '');
      input.value = existing;
      input.addEventListener('input', renderPreview);
      field.appendChild(label);
      field.appendChild(input);
      grid.appendChild(field);
    });
    extraContainer.appendChild(grid);
  }

  // Clickable pills of GA4-recommended values for the selected channel, so people don't
  // have to already know which utm_source/utm_medium strings GA4's Default Channel
  // Grouping expects (e.g. "cpc" for paid search vs "paid-social" for social ads).
  function renderSuggestionChips(container, values, targetInput) {
    if (!container) return;
    container.innerHTML = '';
    (values || []).forEach(function (value) {
      var chip = document.createElement('button');
      chip.type = 'button';
      chip.className = 'suggestion-chip';
      chip.textContent = value;
      if (targetInput.value === value) chip.classList.add('active');
      chip.addEventListener('click', function () {
        targetInput.value = value;
        container.querySelectorAll('.suggestion-chip').forEach(function (c) { c.classList.remove('active'); });
        chip.classList.add('active');
        renderPreview();
        suggestName();
      });
      container.appendChild(chip);
    });
  }

  function applyChannel(preserveExtraValues) {
    var channel = channelsById[channelEl.value];
    if (channel) {
      if (channel.default_utm_source && !sourceEl.value) sourceEl.value = channel.default_utm_source;
      if (channel.default_utm_medium && !mediumEl.value) mediumEl.value = channel.default_utm_medium;
      termLabelEl.textContent = (channel.term_label || 'utm_term') + (channel.requires_term ? ' *' : ' (optional)');
      termEl.required = !!channel.requires_term;
      renderSuggestionChips(sourceSuggestions, channel.recommended_sources, sourceEl);
      renderSuggestionChips(mediumSuggestions, channel.recommended_mediums, mediumEl);
    } else {
      termLabelEl.textContent = 'utm_term (optional)';
      termEl.required = false;
      renderSuggestionChips(sourceSuggestions, [], sourceEl);
      renderSuggestionChips(mediumSuggestions, [], mediumEl);
    }
    renderExtraParams(channel, preserveExtraValues);
    renderPreview();
    suggestName();
  }

  function renderPreview() {
    var url = (targetUrlEl.value || '').trim();
    if (!url) { preview.textContent = ''; return; }
    var params = {};
    if (sourceEl.value) params.utm_source = sourceEl.value;
    if (mediumEl.value) params.utm_medium = mediumEl.value;
    if (campaignEl.value) params.utm_campaign = campaignEl.value;
    if (termEl.value) params.utm_term = termEl.value;
    if (contentEl.value) params.utm_content = contentEl.value;
    extraContainer.querySelectorAll('input[data-extra-key]').forEach(function (input) {
      if (input.value) params[input.getAttribute('data-extra-key')] = input.value;
    });
    var query = Object.keys(params).map(function (k) {
      return encodeURIComponent(k) + '=' + encodeURIComponent(params[k]);
    }).join('&');
    if (!query) { preview.textContent = url; return; }
    var separator = url.indexOf('?') === -1 ? '?' : '&';
    preview.textContent = url + separator + query;
  }

  function renderTemplate(tpl, ctx) {
    return tpl.replace(/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/g, function (m, key) {
      return Object.prototype.hasOwnProperty.call(ctx, key) ? ctx[key] : '';
    });
  }

  function customValues() {
    var out = {};
    customFields.forEach(function (el) { out[el.getAttribute('data-custom-key')] = el.value || ''; });
    return out;
  }

  // {{service_vertical}}/{{service}} come from the selected Landing Page's own
  // vertical/service, mirroring the server-side rule in CampaignController.
  function buildNameContext() {
    var lp = data.landingPages[landingPageEl.value];
    var vertical = lp && data.verticals[lp.vertical_id] ? data.verticals[lp.vertical_id] : null;
    var service = lp && data.services[lp.service_id] ? data.services[lp.service_id] : null;
    var channel = channelsById[channelEl.value];
    var ctx = {
      seq: String(data.nextSeq || ''),
      service_vertical: vertical ? (vertical.short_code || '') : '',
      vertical_name: vertical ? (vertical.name || '') : '',
      service: service ? (service.slug || '') : '',
      service_name: service ? (service.name || '') : '',
      channel: channel ? (channel.short_code || '') : '',
      channel_name: channel ? (channel.name || '') : '',
      utm_source: sourceEl.value || '',
      utm_medium: mediumEl.value || '',
      utm_campaign: campaignEl.value || '',
      utm_term: termEl.value || '',
      utm_content: contentEl.value || '',
    };
    var cv = customValues();
    Object.keys(cv).forEach(function (k) { ctx[k] = cv[k]; });
    return ctx;
  }

  // Only ever fills the Name field if the user hasn't typed in it themselves --
  // this is a suggestion, not an override, since Name stays free-text.
  function suggestName() {
    if (!data.namePattern || !nameEl || nameTouched) return;
    var raw = renderTemplate(data.namePattern, buildNameContext());
    raw = raw.replace(/-{2,}/g, '-').replace(/^-+|-+$/g, '');
    nameEl.value = raw;
  }

  landingPageEl.addEventListener('change', function () { applyLandingPage(); renderPreview(); suggestName(); });
  channelEl.addEventListener('change', function () { applyChannel(false); });
  [sourceEl, mediumEl, campaignEl, termEl, contentEl].forEach(function (el) {
    el.addEventListener('input', function () { renderPreview(); suggestName(); });
  });
  targetUrlEl.addEventListener('input', renderPreview);
  customFields.forEach(function (el) {
    el.addEventListener('input', suggestName);
    el.addEventListener('change', suggestName);
  });

  // Initial render (covers edit mode: channel already selected, existing extra param values).
  applyChannel(true);
})();
