<?php
$record = $record ?? [];
$errors = $errors ?? [];
$readOnly = !has_role('editor');
$existingExtraParams = [];
if (!empty($record['extra_params'])) {
    $decoded = json_decode($record['extra_params'], true);
    if (is_array($decoded)) $existingExtraParams = $decoded;
}
$customValues = $customValues ?? [];
$keywords = $keywords ?? [];
$targeting = $targeting ?? [];
$campaignData = [
    'channels' => $channelsJson,
    'landingPages' => array_column($landingPageOptions, null, 'id'),
    'verticals' => array_column($verticalOptions, null, 'id'),
    'services' => array_column($serviceOptions, null, 'id'),
    'trafficTypes' => array_column($trafficTypesJson, null, 'id'),
    'existingExtraParams' => $existingExtraParams,
    'namePattern' => $campaignNamePattern,
    'nextSeq' => $nextSeq,
];
// Which fixed platform block to show on page load (edit mode) -- matches the
// channel already saved on the record; JS re-derives this on every channel change.
$recordPlatformType = 'other';
foreach ($channelsJson as $c) {
    if ((int) $c['id'] === (int) ($record['channel_id'] ?? 0)) { $recordPlatformType = $c['platform_type']; break; }
}
$googleTargetingOptions = []; // Google uses Keywords, not the generic Targeting table.
$metaTargetingOptions = [
    'location' => 'Location', 'age_range' => 'Age Range', 'gender' => 'Gender', 'interest' => 'Interest',
    'custom_audience' => 'Custom Audience', 'lookalike_audience' => 'Lookalike Audience', 'behavior' => 'Behavior',
];
$linkedinTargetingOptions = [
    'location' => 'Location', 'job_title' => 'Job Title', 'job_function' => 'Job Function', 'seniority' => 'Seniority',
    'industry' => 'Industry', 'company_name' => 'Company Name', 'company_size' => 'Company Size',
    'skill' => 'Skill', 'group' => 'Group', 'school' => 'School',
];
?>
<div class="page-header"><h1><?= $mode === 'create' ? 'Build a' : ($readOnly ? 'View' : 'Edit') ?> Campaign Link</h1></div>
<div class="card" style="max-width:680px">
  <form method="post" action="<?= $mode === 'create' ? url($routeBase . '/create') : url($routeBase . '/edit/' . $record['id']) ?>" id="campaign-form">
    <?= csrf_field() ?>
    <fieldset <?= $readOnly ? 'disabled' : '' ?> style="border:0;padding:0;margin:0">
    <div class="field">
      <label for="name">Campaign name (internal)</label>
      <input type="text" id="name" name="name" value="<?= e($record['name'] ?? '') ?>" required autofocus>
      <?php if ($campaignNamePattern): ?>
        <div class="hint">Auto-suggested from your <a href="<?= url('tenant-settings') ?>">Naming Convention</a> (only fills this in if it's still empty) as you fill in the fields below.</div>
      <?php endif; ?>
      <?php if (!empty($errors['name'])): ?><div class="hint" style="color:var(--danger)"><?= e($errors['name']) ?></div><?php endif; ?>
    </div>

    <div class="field">
      <label for="landing_page_id">Landing page (optional)</label>
      <select id="landing_page_id" name="landing_page_id">
        <option value="">— pick one to fill the destination URL, or type your own below —</option>
        <?php foreach ($landingPageOptions as $o): ?>
          <option value="<?= (int) $o['id'] ?>" <?= (int) ($record['landing_page_id'] ?? 0) === (int) $o['id'] ? 'selected' : '' ?>><?= e($o['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field">
      <label for="target_url">Destination URL</label>
      <input type="text" id="target_url" name="target_url" value="<?= e($record['target_url'] ?? '') ?>" placeholder="https://example.com/lp/page" required>
      <?php if (!empty($errors['target_url'])): ?><div class="hint" style="color:var(--danger)"><?= e($errors['target_url']) ?></div><?php endif; ?>
    </div>

    <div class="field">
      <label for="channel_id">Channel (optional)</label>
      <select id="channel_id" name="channel_id">
        <option value="">— none —</option>
        <?php foreach ($channelOptions as $o): ?>
          <option value="<?= (int) $o['id'] ?>" <?= (int) ($record['channel_id'] ?? 0) === (int) $o['id'] ? 'selected' : '' ?>><?= e($o['name']) ?></option>
        <?php endforeach; ?>
      </select>
      <div class="hint">Fills in the usual utm_source/medium for this channel and adds its typical extra parameters below. You can still edit anything by hand. Manage channels under <a href="<?= url('channels') ?>">Ad Channels</a>.</div>
    </div>

    <div class="form-grid">
      <div class="field">
        <label for="utm_source">utm_source</label>
        <input type="text" id="utm_source" name="utm_source" value="<?= e($record['utm_source'] ?? '') ?>" placeholder="e.g. linkedin" required>
        <div id="utm_source_suggestions" class="suggestion-chips"></div>
        <?php if (!empty($errors['utm_source'])): ?><div class="hint" style="color:var(--danger)"><?= e($errors['utm_source']) ?></div><?php endif; ?>
      </div>
      <div class="field">
        <label for="utm_medium">utm_medium</label>
        <input type="text" id="utm_medium" name="utm_medium" value="<?= e($record['utm_medium'] ?? '') ?>" placeholder="e.g. paid-social" required>
        <div id="utm_medium_suggestions" class="suggestion-chips"></div>
        <div class="hint">Pick a channel above to see the GA4-recommended values for it -- typing your own is fine too, but an unrecognized medium may not group into the report you expect.</div>
        <?php if (!empty($errors['utm_medium'])): ?><div class="hint" style="color:var(--danger)"><?= e($errors['utm_medium']) ?></div><?php endif; ?>
      </div>
      <div class="field">
        <label for="utm_campaign">utm_campaign</label>
        <input type="text" id="utm_campaign" name="utm_campaign" value="<?= e($record['utm_campaign'] ?? '') ?>" placeholder="e.g. q3-dt-launch" required>
        <?php if (!empty($errors['utm_campaign'])): ?><div class="hint" style="color:var(--danger)"><?= e($errors['utm_campaign']) ?></div><?php endif; ?>
      </div>
      <div class="field">
        <label for="traffic_type_id">Traffic Type (utm_cv)</label>
        <select id="traffic_type_id" name="traffic_type_id" required>
          <option value="">— select —</option>
          <?php foreach ($trafficTypeOptions as $o): ?>
            <option value="<?= (int) $o['id'] ?>" <?= (int) ($record['traffic_type_id'] ?? 0) === (int) $o['id'] ? 'selected' : '' ?>><?= e($o['name']) ?> (<?= e($o['code']) ?>)</option>
          <?php endforeach; ?>
        </select>
        <div class="hint">Appended to the generated URL as <code>utm_cv=&lt;code&gt;</code> -- your landing page scripts read this straight off the link. Manage the list under <a href="<?= url('traffic-types') ?>">Traffic Types</a>.</div>
        <?php if (!empty($errors['traffic_type_id'])): ?><div class="hint" style="color:var(--danger)"><?= e($errors['traffic_type_id']) ?></div><?php endif; ?>
      </div>
      <div class="field">
        <label for="utm_term" id="utm_term_label">utm_term (optional)</label>
        <input type="text" id="utm_term" name="utm_term" value="<?= e($record['utm_term'] ?? '') ?>" placeholder="e.g. a keyword">
        <?php if (!empty($errors['utm_term'])): ?><div class="hint" style="color:var(--danger)"><?= e($errors['utm_term']) ?></div><?php endif; ?>
      </div>
      <div class="field">
        <label for="utm_content">utm_content (optional)</label>
        <input type="text" id="utm_content" name="utm_content" value="<?= e($record['utm_content'] ?? '') ?>">
      </div>
      <div class="field">
        <label for="status">Status</label>
        <select id="status" name="status">
          <option value="active" <?= ($record['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active</option>
          <option value="inactive" <?= ($record['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
        </select>
      </div>
    </div>

    <div id="extra-params-container"></div>

    <?php if (!empty($customVariables)): ?>
    <div class="field">
      <label>Additional details</label>
      <div class="form-grid">
        <?php foreach ($customVariables as $cv): ?>
          <div class="field">
            <label for="ccustom_<?= (int) $cv['id'] ?>"><?= e($cv['label']) ?></label>
            <?php if ($cv['source_type'] === 'static_list'): ?>
              <select id="ccustom_<?= (int) $cv['id'] ?>" name="custom_variables[<?= (int) $cv['id'] ?>]" data-custom-key="<?= e($cv['key_name']) ?>">
                <option value="">— none —</option>
                <?php foreach ($cv['options'] as $opt): ?>
                  <option value="<?= e($opt['value']) ?>" <?= ($customValues[$cv['id']] ?? '') === $opt['value'] ? 'selected' : '' ?>><?= e($opt['label']) ?></option>
                <?php endforeach; ?>
              </select>
            <?php else: ?>
              <input type="text" id="ccustom_<?= (int) $cv['id'] ?>" name="custom_variables[<?= (int) $cv['id'] ?>]" data-custom-key="<?= e($cv['key_name']) ?>" value="<?= e($customValues[$cv['id']] ?? '') ?>">
            <?php endif; ?>
            <?php if ($cv['description']): ?><div class="hint"><?= e($cv['description']) ?></div><?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <div class="field">
      <label>Campaign settings</label>
      <div class="form-grid">
        <div class="field">
          <label for="budget_type">Budget type</label>
          <select id="budget_type" name="budget_type">
            <option value="">— none —</option>
            <option value="daily" <?= ($record['budget_type'] ?? '') === 'daily' ? 'selected' : '' ?>>Daily</option>
            <option value="lifetime" <?= ($record['budget_type'] ?? '') === 'lifetime' ? 'selected' : '' ?>>Lifetime</option>
          </select>
        </div>
        <div class="field">
          <label for="budget_amount">Budget amount</label>
          <input type="number" step="0.01" min="0" id="budget_amount" name="budget_amount" value="<?= e($record['budget_amount'] ?? '') ?>">
        </div>
        <div class="field">
          <label for="currency">Currency</label>
          <input type="text" id="currency" name="currency" value="<?= e($record['currency'] ?? 'USD') ?>" placeholder="USD" maxlength="10">
        </div>
        <div class="field">
          <label for="start_date">Start date</label>
          <input type="date" id="start_date" name="start_date" value="<?= e($record['start_date'] ?? '') ?>">
        </div>
        <div class="field">
          <label for="end_date">End date</label>
          <input type="date" id="end_date" name="end_date" value="<?= e($record['end_date'] ?? '') ?>">
        </div>
      </div>
      <div class="hint">Shared across every platform. The sections below only show once you pick a Google/Meta/LinkedIn Ads channel above.</div>
    </div>

    <div id="platform-google_ads" class="platform-block field" style="display:<?= $recordPlatformType === 'google_ads' ? 'block' : 'none' ?>">
      <label>Google Ads settings</label>
      <div class="form-grid">
        <div class="field">
          <label for="google_campaign_type">Campaign type</label>
          <select id="google_campaign_type" name="google_campaign_type">
            <option value="">— select —</option>
            <?php foreach (['Search', 'Display', 'Performance Max', 'Shopping', 'Video', 'App'] as $o): ?>
              <option value="<?= e($o) ?>" <?= ($record['google_campaign_type'] ?? '') === $o ? 'selected' : '' ?>><?= e($o) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label for="google_objective">Objective</label>
          <select id="google_objective" name="google_objective">
            <option value="">— select —</option>
            <?php foreach (['Sales', 'Leads', 'Website Traffic', 'Awareness and Consideration', 'App Promotion', 'Local Store Visits'] as $o): ?>
              <option value="<?= e($o) ?>" <?= ($record['objective'] ?? '') === $o && $recordPlatformType === 'google_ads' ? 'selected' : '' ?>><?= e($o) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label for="google_bidding_strategy">Bidding strategy</label>
          <select id="google_bidding_strategy" name="google_bidding_strategy">
            <option value="">— select —</option>
            <?php foreach (['Maximize Clicks', 'Maximize Conversions', 'Target CPA', 'Target ROAS', 'Manual CPC', 'Target Impression Share'] as $o): ?>
              <option value="<?= e($o) ?>" <?= ($record['bidding_strategy'] ?? '') === $o && $recordPlatformType === 'google_ads' ? 'selected' : '' ?>><?= e($o) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label for="google_bid_amount">Bid amount</label>
          <input type="number" step="0.01" min="0" id="google_bid_amount" name="google_bid_amount" value="<?= $recordPlatformType === 'google_ads' ? e($record['bid_amount'] ?? '') : '' ?>">
        </div>
        <div class="field">
          <label for="google_languages">Languages</label>
          <input type="text" id="google_languages" name="google_languages" value="<?= e($record['google_languages'] ?? '') ?>" placeholder="e.g. English, Spanish">
        </div>
      </div>
      <div class="form-grid">
        <div class="field">
          <label>Networks</label>
          <?php $googleNetworks = array_map('trim', explode(',', $record['google_networks'] ?? '')); ?>
          <?php foreach (['search_network' => 'Search Network', 'display_network' => 'Display Network', 'search_partners' => 'Search Partners'] as $val => $label): ?>
            <label style="display:flex;align-items:center;gap:7px;font-weight:400">
              <input type="checkbox" name="google_networks[]" value="<?= e($val) ?>" style="width:auto" <?= in_array($val, $googleNetworks, true) ? 'checked' : '' ?>><?= e($label) ?>
            </label>
          <?php endforeach; ?>
        </div>
        <div class="field">
          <label>Devices</label>
          <?php $googleDevices = array_map('trim', explode(',', $record['google_devices'] ?? '')); ?>
          <?php foreach (['desktop' => 'Desktop', 'mobile' => 'Mobile', 'tablet' => 'Tablet'] as $val => $label): ?>
            <label style="display:flex;align-items:center;gap:7px;font-weight:400">
              <input type="checkbox" name="google_devices[]" value="<?= e($val) ?>" style="width:auto" <?= in_array($val, $googleDevices, true) ? 'checked' : '' ?>><?= e($label) ?>
            </label>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="field">
        <label>Keywords</label>
        <div id="keyword-rows">
          <?php $keywordRows = $keywords ?: [['keyword' => '', 'match_type' => 'broad', 'is_negative' => 0]]; ?>
          <?php foreach ($keywordRows as $k): ?>
            <div class="keyword-row" style="display:flex;gap:8px;margin-bottom:8px">
              <input type="text" name="keyword_text[]" placeholder="e.g. crm software" value="<?= e($k['keyword'] ?? '') ?>">
              <select name="keyword_match_type[]">
                <?php foreach (['broad' => 'Broad', 'phrase' => 'Phrase', 'exact' => 'Exact'] as $val => $label): ?>
                  <option value="<?= $val ?>" <?= ($k['match_type'] ?? 'broad') === $val ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
              </select>
              <select name="keyword_negative[]">
                <option value="positive" <?= empty($k['is_negative']) ? 'selected' : '' ?>>Positive</option>
                <option value="negative" <?= !empty($k['is_negative']) ? 'selected' : '' ?>>Negative</option>
              </select>
              <button type="button" class="btn secondary small remove-keyword-row">&times;</button>
            </div>
          <?php endforeach; ?>
        </div>
        <button type="button" class="btn secondary small" id="add-keyword-row"><?= icon('plus') ?>Add keyword</button>
      </div>
    </div>

    <div id="platform-meta_ads" class="platform-block field" style="display:<?= $recordPlatformType === 'meta_ads' ? 'block' : 'none' ?>">
      <label>Meta Ads settings</label>
      <div class="form-grid">
        <div class="field">
          <label for="meta_objective">Objective</label>
          <select id="meta_objective" name="meta_objective">
            <option value="">— select —</option>
            <?php foreach (['Awareness', 'Traffic', 'Engagement', 'Leads', 'App Promotion', 'Sales'] as $o): ?>
              <option value="<?= e($o) ?>" <?= ($record['objective'] ?? '') === $o && $recordPlatformType === 'meta_ads' ? 'selected' : '' ?>><?= e($o) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label for="meta_buying_type">Buying type</label>
          <select id="meta_buying_type" name="meta_buying_type">
            <option value="">— select —</option>
            <?php foreach (['Auction', 'Reach and Frequency'] as $o): ?>
              <option value="<?= e($o) ?>" <?= ($record['meta_buying_type'] ?? '') === $o ? 'selected' : '' ?>><?= e($o) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label for="meta_bidding_strategy">Bidding strategy</label>
          <select id="meta_bidding_strategy" name="meta_bidding_strategy">
            <option value="">— select —</option>
            <?php foreach (['Lowest Cost', 'Cost Cap', 'Bid Cap', 'ROAS Goal'] as $o): ?>
              <option value="<?= e($o) ?>" <?= ($record['bidding_strategy'] ?? '') === $o && $recordPlatformType === 'meta_ads' ? 'selected' : '' ?>><?= e($o) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label for="meta_bid_amount">Bid amount</label>
          <input type="number" step="0.01" min="0" id="meta_bid_amount" name="meta_bid_amount" value="<?= $recordPlatformType === 'meta_ads' ? e($record['bid_amount'] ?? '') : '' ?>">
        </div>
        <div class="field">
          <label for="meta_ad_format">Ad format</label>
          <select id="meta_ad_format" name="meta_ad_format">
            <option value="">— select —</option>
            <?php foreach (['Single Image', 'Carousel', 'Video', 'Collection'] as $o): ?>
              <option value="<?= e($o) ?>" <?= ($record['meta_ad_format'] ?? '') === $o ? 'selected' : '' ?>><?= e($o) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="field">
        <label>Placements</label>
        <?php $metaPlacements = array_map('trim', explode(',', $record['meta_placements'] ?? '')); ?>
        <?php foreach (['feed' => 'Feed', 'stories' => 'Stories', 'reels' => 'Reels', 'audience_network' => 'Audience Network', 'marketplace' => 'Marketplace', 'right_column' => 'Right Column'] as $val => $label): ?>
          <label style="display:flex;align-items:center;gap:7px;font-weight:400">
            <input type="checkbox" name="meta_placements[]" value="<?= e($val) ?>" style="width:auto" <?= in_array($val, $metaPlacements, true) ? 'checked' : '' ?>><?= e($label) ?>
          </label>
        <?php endforeach; ?>
      </div>
      <div class="field">
        <label>Audience targeting</label>
        <div id="meta-targeting-rows">
          <?php $metaRows = ($recordPlatformType === 'meta_ads' && $targeting) ? $targeting : [['criterion_type' => 'location', 'criterion_value' => '']]; ?>
          <?php foreach ($metaRows as $t): ?>
            <div class="targeting-row" style="display:flex;gap:8px;margin-bottom:8px">
              <select name="meta_targeting_type[]">
                <?php foreach ($metaTargetingOptions as $val => $label): ?>
                  <option value="<?= e($val) ?>" <?= ($t['criterion_type'] ?? '') === $val ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
              </select>
              <input type="text" name="meta_targeting_value[]" placeholder="e.g. United States" value="<?= e($t['criterion_value'] ?? '') ?>">
              <button type="button" class="btn secondary small remove-targeting-row">&times;</button>
            </div>
          <?php endforeach; ?>
        </div>
        <button type="button" class="btn secondary small add-targeting-row" data-target="meta-targeting-rows" data-name-prefix="meta_targeting"><?= icon('plus') ?>Add targeting</button>
      </div>
    </div>

    <div id="platform-linkedin_ads" class="platform-block field" style="display:<?= $recordPlatformType === 'linkedin_ads' ? 'block' : 'none' ?>">
      <label>LinkedIn Ads settings</label>
      <div class="form-grid">
        <div class="field">
          <label for="linkedin_objective">Objective</label>
          <select id="linkedin_objective" name="linkedin_objective">
            <option value="">— select —</option>
            <?php foreach (['Brand Awareness', 'Website Visits', 'Engagement', 'Video Views', 'Lead Generation', 'Website Conversions', 'Job Applicants'] as $o): ?>
              <option value="<?= e($o) ?>" <?= ($record['objective'] ?? '') === $o && $recordPlatformType === 'linkedin_ads' ? 'selected' : '' ?>><?= e($o) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label for="linkedin_ad_format">Ad format</label>
          <select id="linkedin_ad_format" name="linkedin_ad_format">
            <option value="">— select —</option>
            <?php foreach (['Single Image', 'Carousel', 'Video', 'Message Ad', 'Text Ad', 'Dynamic Ad', 'Document Ad'] as $o): ?>
              <option value="<?= e($o) ?>" <?= ($record['linkedin_ad_format'] ?? '') === $o ? 'selected' : '' ?>><?= e($o) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label for="linkedin_bid_type">Bid type</label>
          <select id="linkedin_bid_type" name="linkedin_bid_type">
            <option value="">— select —</option>
            <?php foreach (['CPC', 'CPM', 'Automated Bid'] as $o): ?>
              <option value="<?= e($o) ?>" <?= ($record['linkedin_bid_type'] ?? '') === $o ? 'selected' : '' ?>><?= e($o) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label for="linkedin_bidding_strategy">Bidding strategy</label>
          <select id="linkedin_bidding_strategy" name="linkedin_bidding_strategy">
            <option value="">— select —</option>
            <?php foreach (['Maximum Delivery', 'Cost Cap', 'Manual Bidding'] as $o): ?>
              <option value="<?= e($o) ?>" <?= ($record['bidding_strategy'] ?? '') === $o && $recordPlatformType === 'linkedin_ads' ? 'selected' : '' ?>><?= e($o) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label for="linkedin_bid_amount">Bid amount</label>
          <input type="number" step="0.01" min="0" id="linkedin_bid_amount" name="linkedin_bid_amount" value="<?= $recordPlatformType === 'linkedin_ads' ? e($record['bid_amount'] ?? '') : '' ?>">
        </div>
      </div>
      <div class="field">
        <label>Audience targeting</label>
        <div id="linkedin-targeting-rows">
          <?php $linkedinRows = ($recordPlatformType === 'linkedin_ads' && $targeting) ? $targeting : [['criterion_type' => 'location', 'criterion_value' => '']]; ?>
          <?php foreach ($linkedinRows as $t): ?>
            <div class="targeting-row" style="display:flex;gap:8px;margin-bottom:8px">
              <select name="linkedin_targeting_type[]">
                <?php foreach ($linkedinTargetingOptions as $val => $label): ?>
                  <option value="<?= e($val) ?>" <?= ($t['criterion_type'] ?? '') === $val ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
              </select>
              <input type="text" name="linkedin_targeting_value[]" placeholder="e.g. VP of Marketing" value="<?= e($t['criterion_value'] ?? '') ?>">
              <button type="button" class="btn secondary small remove-targeting-row">&times;</button>
            </div>
          <?php endforeach; ?>
        </div>
        <button type="button" class="btn secondary small add-targeting-row" data-target="linkedin-targeting-rows" data-name-prefix="linkedin_targeting"><?= icon('plus') ?>Add targeting</button>
      </div>
    </div>

    <div id="platform-other" class="platform-block field" style="display:<?= $recordPlatformType === 'other' ? 'block' : 'none' ?>">
      <p class="text-muted">This channel doesn't need ad-platform campaign settings (budget/bidding/targeting) -- those only apply to Google, Meta, and LinkedIn Ads channels. Manage a channel's platform under <a href="<?= url('channels') ?>">Ad Channels</a>.</p>
    </div>
    </fieldset>

    <?php if (!$readOnly): ?>
    <div class="form-actions">
      <button class="btn" type="submit">Save &amp; generate link</button>
      <a class="btn secondary" href="<?= url($routeBase) ?>">Cancel</a>
    </div>
    <?php endif; ?>
  </form>

  <div class="field" style="margin-top:20px">
    <label>Generated URL <span class="text-muted" style="font-weight:400">(live preview)</span></label>
    <div class="snippet-box mb-0">
      <pre id="gen-url-preview" style="white-space:pre-wrap;word-break:break-all"><?= e($record['generated_url'] ?? '') ?></pre>
      <button class="btn small secondary copy-btn" type="button" data-copy-target="#gen-url-preview">Copy</button>
    </div>
  </div>
</div>

<script>window.CAMPAIGN_DATA = <?= json_encode($campaignData, JSON_UNESCAPED_SLASHES) ?>;</script>
<script src="<?= asset('js/campaign-builder.js') ?>"></script>
