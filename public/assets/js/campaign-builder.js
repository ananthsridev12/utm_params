(function () {
  var data = window.CAMPAIGN_DATA;
  if (!data) return;

  var $ = function (id) { return document.getElementById(id); };
  var channelsById = {};
  (data.channels || []).forEach(function (c) { channelsById[c.id] = c; });

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

  var currentExtraValues = Object.assign({}, data.existingExtraParams || {});

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

  function applyChannel(preserveExtraValues) {
    var channel = channelsById[channelEl.value];
    if (channel) {
      if (channel.default_utm_source && !sourceEl.value) sourceEl.value = channel.default_utm_source;
      if (channel.default_utm_medium && !mediumEl.value) mediumEl.value = channel.default_utm_medium;
      termLabelEl.textContent = (channel.term_label || 'utm_term') + ' (optional)';
    } else {
      termLabelEl.textContent = 'utm_term (optional)';
    }
    renderExtraParams(channel, preserveExtraValues);
    renderPreview();
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

  landingPageEl.addEventListener('change', function () { applyLandingPage(); renderPreview(); });
  channelEl.addEventListener('change', function () { applyChannel(false); });
  [targetUrlEl, sourceEl, mediumEl, campaignEl, termEl, contentEl].forEach(function (el) {
    el.addEventListener('input', renderPreview);
  });

  // Initial render (covers edit mode: channel already selected, existing extra param values).
  applyChannel(true);
})();
