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
  var trafficTypeEl = $('traffic_type_id');
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

  // One fixed campaign-details block per ad platform (Google/Meta/LinkedIn each have
  // genuinely different settings) -- shown/hidden based on the selected channel's
  // platform_type. Everything not matching 'other' falls back to 'other' (no ad-platform
  // settings shown) so an unclassified/no-channel state doesn't leave a stale block open.
  var platformBlocks = {
    google_ads: $('platform-google_ads'),
    meta_ads: $('platform-meta_ads'),
    linkedin_ads: $('platform-linkedin_ads'),
    other: $('platform-other'),
  };
  function showPlatformBlock(platformType) {
    var key = platformBlocks[platformType] ? platformType : 'other';
    Object.keys(platformBlocks).forEach(function (k) {
      if (platformBlocks[k]) platformBlocks[k].style.display = (k === key) ? 'block' : 'none';
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
      showPlatformBlock(channel.platform_type || 'other');
    } else {
      termLabelEl.textContent = 'utm_term (optional)';
      termEl.required = false;
      renderSuggestionChips(sourceSuggestions, [], sourceEl);
      renderSuggestionChips(mediumSuggestions, [], mediumEl);
      showPlatformBlock('other');
    }
    renderExtraParams(channel, preserveExtraValues);
    renderPreview();
    suggestName();
  }

  // Generic add/remove for a repeatable-row table (Keywords, Meta/LinkedIn Targeting).
  // Mirrors the same add/remove-row pattern used by custom-variable-form.js's Options
  // editor: clone the template row, clear its values, append; removing the last row in
  // a container just clears it instead of leaving an empty table.
  function initRepeatableRows(container, addBtn) {
    if (!container || !addBtn) return;
    addBtn.addEventListener('click', function () {
      var rows = container.children;
      if (!rows.length) return;
      var clone = rows[0].cloneNode(true);
      clone.querySelectorAll('input[type="text"], input[type="number"]').forEach(function (i) { i.value = ''; });
      clone.querySelectorAll('select').forEach(function (s) { s.selectedIndex = 0; });
      container.appendChild(clone);
    });
    container.addEventListener('click', function (e) {
      var btn = e.target.closest('.remove-keyword-row, .remove-targeting-row');
      if (!btn || !container.contains(btn)) return;
      var rows = container.children;
      if (rows.length > 1) {
        btn.closest('.keyword-row, .targeting-row').remove();
      } else {
        rows[0].querySelectorAll('input[type="text"], input[type="number"]').forEach(function (i) { i.value = ''; });
        rows[0].querySelectorAll('select').forEach(function (s) { s.selectedIndex = 0; });
      }
    });
  }
  initRepeatableRows($('keyword-rows'), $('add-keyword-row'));
  document.querySelectorAll('.add-targeting-row').forEach(function (btn) {
    initRepeatableRows($(btn.getAttribute('data-target')), btn);
  });

  // Ad platforms' click-time placeholders (Google/Bing ValueTrack: {keyword}, {device},
  // {matchtype}, {network}, ...) only get recognized when they appear literally,
  // unencoded, in the URL -- mirrors CampaignController::unencodeValueTrackBraces().
  function unencodeValueTrackBraces(queryString) {
    return queryString.replace(/%7B/gi, '{').replace(/%7D/gi, '}');
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
    if (trafficTypeEl && trafficTypeEl.value) {
      var tt = data.trafficTypes[trafficTypeEl.value];
      if (tt) params.utm_cv = tt.code;
    }
    extraContainer.querySelectorAll('input[data-extra-key]').forEach(function (input) {
      if (input.value) params[input.getAttribute('data-extra-key')] = input.value;
    });
    var query = Object.keys(params).map(function (k) {
      return encodeURIComponent(k) + '=' + encodeURIComponent(params[k]);
    }).join('&');
    if (!query) { preview.textContent = url; return; }
    var separator = url.indexOf('?') === -1 ? '?' : '&';
    preview.textContent = url + separator + unencodeValueTrackBraces(query);
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
    var trafficType = trafficTypeEl && data.trafficTypes[trafficTypeEl.value];
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
      utm_cv: trafficType ? (trafficType.code || '') : '',
      traffic_type: trafficType ? (trafficType.code || '') : '',
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
  if (trafficTypeEl) trafficTypeEl.addEventListener('change', function () { renderPreview(); suggestName(); });
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
