<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Flash;
use App\Core\Request;
use App\Core\TenantContext;
use App\Core\Url;
use App\Core\View;
use App\Core\XlsxWriter;
use App\Models\Campaign;
use App\Models\CampaignKeyword;
use App\Models\CampaignTargeting;
use App\Models\Channel;
use App\Models\CustomVariable;
use App\Models\CustomVariableValue;
use App\Models\LandingPage;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\TrafficType;
use App\Models\Vertical;

class CampaignController extends BaseController
{
    protected string $modelClass = Campaign::class;
    protected string $viewDir = 'campaigns';
    protected string $routeBase = 'campaigns';
    protected string $title = 'Campaign';

    public function index(array $params = []): void
    {
        Auth::requireLogin();
        View::render($this->viewDir . '/index', [
            'title' => $this->title,
            'routeBase' => $this->routeBase,
            'records' => Campaign::allWithRelations(),
        ]);
    }

    public function create(array $params = []): void
    {
        Auth::requireRole($this->writeRole);
        View::render($this->viewDir . '/form', array_merge([
            'title' => $this->title,
            'routeBase' => $this->routeBase,
            'mode' => 'create',
            'record' => [],
            'errors' => [],
            'customValues' => [],
            'keywords' => [],
            'targeting' => [],
        ], $this->extraViewData()));
    }

    public function edit(array $params): void
    {
        // Viewers may open this read-only to see the generated URL; the actual
        // save (update()) still requires editor+ via BaseController.
        Auth::requireLogin();
        $id = (int) $params['id'];
        $model = $this->modelClass;
        $record = $model::find($id);
        if (!$record) {
            http_response_code(404);
            exit('Not found.');
        }
        View::render($this->viewDir . '/form', array_merge([
            'title' => $this->title,
            'routeBase' => $this->routeBase,
            'mode' => 'edit',
            'record' => $record,
            'errors' => [],
            'customValues' => CustomVariableValue::forEntityById('campaign', $id),
            'keywords' => CampaignKeyword::forCampaign($id),
            'targeting' => CampaignTargeting::forCampaign($id),
        ], $this->extraViewData()));
    }

    protected function extraViewData(): array
    {
        $channels = Channel::allActive();
        $tenant = Tenant::find(TenantContext::requireTenant());

        return [
            'channelOptions' => $channels,
            'channelsJson' => array_map(fn($c) => [
                'id' => (int) $c['id'],
                'name' => $c['name'],
                'short_code' => $c['short_code'],
                'default_utm_source' => $c['default_utm_source'],
                'default_utm_medium' => $c['default_utm_medium'],
                'recommended_sources' => Channel::parseCsvList($c['recommended_sources']),
                'recommended_mediums' => Channel::parseCsvList($c['recommended_mediums']),
                'term_label' => $c['term_label'],
                'requires_term' => (bool) $c['requires_term'],
                'extra_params' => Channel::parseExtraParamLabels($c['extra_param_labels']),
                'platform_type' => $c['platform_type'] ?? 'other',
            ], $channels),
            'landingPageOptions' => LandingPage::forDropdown(),
            'verticalOptions' => Vertical::allActive(),
            'serviceOptions' => Service::allActive(),
            'trafficTypeOptions' => TrafficType::allActive(),
            'trafficTypesJson' => array_map(fn($t) => ['id' => (int) $t['id'], 'code' => $t['code'], 'name' => $t['name']], TrafficType::allActive()),
            'customVariables' => CustomVariable::allWithOptions(true, 'campaign'),
            'campaignNamePattern' => $tenant['campaign_name_pattern'] ?? '',
            'nextSeq' => Campaign::nextSeq(),
        ];
    }

    public function exportCsv(array $params = []): void
    {
        Auth::requireLogin();
        $customVariables = CustomVariable::allWithOptions(false, 'campaign');
        $rows = array_map(function ($r) use ($customVariables) {
            $extra = [];
            if (!empty($r['extra_params'])) {
                $decoded = json_decode($r['extra_params'], true);
                if (is_array($decoded)) {
                    foreach ($decoded as $k => $v) $extra[] = "{$k}={$v}";
                }
            }
            $row = [
                'id' => $r['id'],
                'name' => $r['name'],
                'channel' => $r['channel_name'] ?? '',
                'landing_page' => $r['landing_page_name'] ?? '',
                'target_url' => $r['target_url'],
                'utm_source' => $r['utm_source'],
                'utm_medium' => $r['utm_medium'],
                'utm_campaign' => $r['utm_campaign'],
                'utm_cv' => $r['traffic_type_code'] ?? '',
                'utm_term' => $r['utm_term'],
                'utm_content' => $r['utm_content'],
                'extra_params' => implode('; ', $extra),
                'generated_url' => $r['generated_url'],
                'status' => $r['status'],
            ];
            $values = CustomVariableValue::forEntityByKey('campaign', (int) $r['id']);
            foreach ($customVariables as $cv) {
                $row[$cv['label']] = $values[$cv['key_name']] ?? '';
            }
            $row['created_at'] = $r['created_at'];
            $row['updated_at'] = $r['updated_at'];
            return $row;
        }, Campaign::allWithRelations());
        $this->streamCsv('campaigns.csv', $rows);
    }

    /** Full-details Excel download for one campaign. */
    public function exportExcel(array $params): void
    {
        Auth::requireLogin();
        $id = (int) ($params['id'] ?? 0);
        $record = Campaign::find($id);
        if (!$record) {
            http_response_code(404);
            exit('Not found.');
        }
        $this->streamCampaignExcel([$id]);
    }

    /** Full-details Excel download for a checked set of campaigns from the index. */
    public function exportExcelSelected(): void
    {
        Auth::requireLogin();
        $ids = Request::post('ids', []);
        $ids = is_array($ids) ? array_filter(array_map('intval', $ids)) : [];
        if (empty($ids)) {
            Flash::error('Select at least one campaign to export.');
            header('Location: ' . Url::to($this->routeBase));
            exit;
        }
        $this->streamCampaignExcel($ids);
    }

    /**
     * Builds and streams a multi-sheet .xlsx for the given campaign IDs: an
     * overview sheet (settings/bidding/UTM), a platform-settings sheet
     * (Google/Meta/LinkedIn columns, blank where not applicable), and
     * Keywords/Targeting sheets with one row per repeatable entry, tagged by
     * campaign name so a multi-campaign export stays readable in one file.
     */
    private function streamCampaignExcel(array $ids): void
    {
        $all = Campaign::allWithRelations();
        $selected = array_values(array_filter($all, fn($r) => in_array((int) $r['id'], $ids, true)));
        if (empty($selected)) {
            http_response_code(404);
            exit('Not found.');
        }

        $writer = new XlsxWriter();

        $writer->addSheet('Campaigns', [
            'Campaign Name', 'Channel', 'Platform', 'Status', 'Landing Page', 'Objective', 'Budget Type',
            'Budget Amount', 'Currency', 'Bidding Strategy', 'Bid Amount', 'Start Date', 'End Date',
            'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'utm_cv', 'Generated URL',
        ], array_map(fn($r) => [
            $r['name'], $r['channel_name'] ?? '', self::platformLabel($r['channel_platform_type'] ?? 'other'),
            ucfirst($r['status']), $r['landing_page_name'] ?? '', $r['objective'] ?? '', $r['budget_type'] ?? '',
            $r['budget_amount'] !== null ? (float) $r['budget_amount'] : '', $r['currency'] ?? '',
            $r['bidding_strategy'] ?? '', $r['bid_amount'] !== null ? (float) $r['bid_amount'] : '',
            $r['start_date'] ?? '', $r['end_date'] ?? '', $r['utm_source'], $r['utm_medium'], $r['utm_campaign'],
            $r['utm_term'] ?? '', $r['utm_content'] ?? '', $r['traffic_type_code'] ?? '', $r['generated_url'],
        ], $selected));

        $writer->addSheet('Platform Settings', [
            'Campaign Name', 'Google Campaign Type', 'Google Networks', 'Google Languages', 'Google Devices',
            'Meta Buying Type', 'Meta Placements', 'Meta Ad Format', 'LinkedIn Ad Format', 'LinkedIn Bid Type',
        ], array_map(fn($r) => [
            $r['name'], $r['google_campaign_type'] ?? '', $r['google_networks'] ?? '', $r['google_languages'] ?? '',
            $r['google_devices'] ?? '', $r['meta_buying_type'] ?? '', $r['meta_placements'] ?? '',
            $r['meta_ad_format'] ?? '', $r['linkedin_ad_format'] ?? '', $r['linkedin_bid_type'] ?? '',
        ], $selected));

        $keywordRows = [];
        $targetingRows = [];
        foreach ($selected as $r) {
            foreach (CampaignKeyword::forCampaign((int) $r['id']) as $k) {
                $keywordRows[] = [$r['name'], $k['keyword'], ucfirst($k['match_type']), $k['is_negative'] ? 'Yes' : 'No'];
            }
            foreach (CampaignTargeting::forCampaign((int) $r['id']) as $t) {
                $targetingRows[] = [$r['name'], ucwords(str_replace('_', ' ', $t['criterion_type'])), $t['criterion_value']];
            }
        }
        $writer->addSheet('Keywords', ['Campaign Name', 'Keyword', 'Match Type', 'Negative?'], $keywordRows);
        $writer->addSheet('Targeting', ['Campaign Name', 'Criterion Type', 'Criterion Value'], $targetingRows);

        $filename = count($selected) === 1
            ? (preg_replace('/[^a-zA-Z0-9_-]+/', '-', $selected[0]['name']) ?: 'campaign') . '.xlsx'
            : 'campaigns-' . date('Y-m-d') . '.xlsx';
        $writer->send($filename);
    }

    private static function platformLabel(string $platformType): string
    {
        $labels = ['google_ads' => 'Google Ads', 'meta_ads' => 'Meta Ads', 'linkedin_ads' => 'LinkedIn Ads', 'other' => 'Other'];
        return $labels[$platformType] ?? 'Other';
    }

    /** Google Ads Editor / Bulk Actions-style CSV for one campaign. */
    public function exportGoogleAdsBulk(array $params): void
    {
        Auth::requireLogin();
        $id = (int) ($params['id'] ?? 0);
        $record = Campaign::find($id);
        if (!$record) {
            http_response_code(404);
            exit('Not found.');
        }
        $this->streamGoogleAdsBulkCsv([$id]);
    }

    /** Same, for a checked set of campaigns from the index -- non-Google-Ads ones are skipped. */
    public function exportGoogleAdsBulkSelected(): void
    {
        Auth::requireLogin();
        $ids = Request::post('ids', []);
        $ids = is_array($ids) ? array_filter(array_map('intval', $ids)) : [];
        if (empty($ids)) {
            Flash::error('Select at least one campaign to export.');
            header('Location: ' . Url::to($this->routeBase));
            exit;
        }
        $this->streamGoogleAdsBulkCsv($ids);
    }

    /**
     * Builds a CSV in the shape of Google Ads Editor's / Bulk Actions' bulk
     * upload sheet: one row per entity (Campaign, then its Ad Group, then
     * each Keyword), sharing one set of columns and leaving whichever don't
     * apply to that row blank -- the same layout Google's own template uses.
     * Deliberately narrow scope: this covers Campaign/Ad Group/Keyword rows
     * only, since that's the data this app actually collects. It does NOT
     * emit Ad rows (headlines/descriptions/final URLs) -- this app doesn't
     * collect ad creative, and fabricating placeholder ad copy would be
     * actively wrong to hand someone for upload into a live account. Only
     * campaigns on a Google Ads channel are included; anything else is
     * skipped with a warning, matching the CSV importer's pattern elsewhere.
     */
    private function streamGoogleAdsBulkCsv(array $ids): void
    {
        $all = Campaign::allWithRelations();
        $selected = array_values(array_filter($all, fn($r) => in_array((int) $r['id'], $ids, true)));
        $googleCampaigns = array_values(array_filter($selected, fn($r) => ($r['channel_platform_type'] ?? 'other') === 'google_ads'));
        $skipped = count($selected) - count($googleCampaigns);

        if (empty($googleCampaigns)) {
            Flash::error('None of the selected campaigns are on a Google Ads channel -- this template only applies to those.');
            header('Location: ' . Url::to($this->routeBase));
            exit;
        }
        if ($skipped > 0) {
            Flash::error($skipped . ' selected campaign(s) skipped -- not on a Google Ads channel.');
        }

        $columns = [
            'Action', 'Campaign', 'Campaign Type', 'Campaign Daily Budget', 'Budget Type',
            'Bid Strategy Type', 'Networks', 'Languages', 'Campaign Start Date', 'Campaign End Date',
            'Ad Group', 'Max CPC', 'Keyword', 'Criterion Type', 'Status',
        ];
        $blankRow = array_fill_keys($columns, '');
        $toGoogleDate = fn($d) => $d ? date('n/j/Y', strtotime($d)) : '';
        $toSemicolonList = fn($csv) => $csv ? implode(';', array_map(
            fn($v) => ['search_network' => 'Google Search', 'display_network' => 'Google Display Network', 'search_partners' => 'Search Partners'][$v] ?? $v,
            array_filter(array_map('trim', explode(',', $csv)))
        )) : '';

        $rows = [];
        foreach ($googleCampaigns as $r) {
            $status = $r['status'] === 'active' ? 'Enabled' : 'Paused';

            $rows[] = array_merge($blankRow, [
                'Action' => 'Add',
                'Campaign' => $r['name'],
                'Campaign Type' => 'Search',
                'Campaign Daily Budget' => $r['budget_amount'] !== null ? (float) $r['budget_amount'] : '',
                'Budget Type' => $r['budget_type'] ? ucfirst($r['budget_type']) : '',
                'Bid Strategy Type' => $r['bidding_strategy'] ?? '',
                'Networks' => $toSemicolonList($r['google_networks'] ?? ''),
                'Languages' => implode(';', array_filter(array_map('trim', explode(',', $r['google_languages'] ?? '')))),
                'Campaign Start Date' => $toGoogleDate($r['start_date'] ?? null),
                'Campaign End Date' => $toGoogleDate($r['end_date'] ?? null),
                'Status' => $status,
            ]);

            $rows[] = array_merge($blankRow, [
                'Action' => 'Add',
                'Campaign' => $r['name'],
                'Ad Group' => $r['name'],
                'Max CPC' => $r['bid_amount'] !== null ? (float) $r['bid_amount'] : '',
                'Status' => $status,
            ]);

            foreach (CampaignKeyword::forCampaign((int) $r['id']) as $k) {
                $criterionType = ucfirst($k['match_type']);
                if ($k['is_negative']) {
                    $criterionType = 'Negative ' . $criterionType;
                }
                $rows[] = array_merge($blankRow, [
                    'Action' => 'Add',
                    'Campaign' => $r['name'],
                    'Ad Group' => $r['name'],
                    'Keyword' => $k['keyword'],
                    'Criterion Type' => $criterionType,
                    'Status' => 'Enabled',
                ]);
            }
        }

        $filename = count($googleCampaigns) === 1
            ? (preg_replace('/[^a-zA-Z0-9_-]+/', '-', $googleCampaigns[0]['name']) ?: 'campaign') . '-google-ads-bulk.csv'
            : 'google-ads-bulk-' . date('Y-m-d') . '.csv';
        $this->streamCsv($filename, $rows);
    }

    protected function validate(array $input, ?int $id): array
    {
        $errors = [];
        $name = trim((string) ($input['name'] ?? ''));
        $targetUrl = trim((string) ($input['target_url'] ?? ''));
        $utmSource = trim((string) ($input['utm_source'] ?? ''));
        $utmMedium = trim((string) ($input['utm_medium'] ?? ''));
        $utmCampaign = trim((string) ($input['utm_campaign'] ?? ''));
        $utmTerm = trim((string) ($input['utm_term'] ?? ''));
        $utmContent = trim((string) ($input['utm_content'] ?? ''));
        $status = ($input['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';
        $toIntOrNull = fn($v) => ($v === null || $v === '') ? null : (int) $v;

        if ($name === '') $errors['name'] = 'Name is required.';
        if ($targetUrl === '' || !filter_var($targetUrl, FILTER_VALIDATE_URL)) {
            $errors['target_url'] = 'Enter a full valid URL, e.g. https://example.com/page.';
        }

        $channelId = $toIntOrNull($input['channel_id'] ?? null);
        if ($channelId !== null && $utmTerm === '') {
            $channel = Channel::find($channelId);
            if ($channel && !empty($channel['requires_term'])) {
                $errors['utm_term'] = ($channel['term_label'] ?: 'utm_term') . ' is required for ' . $channel['name'] . '.';
            }
        }
        if ($utmSource === '') $errors['utm_source'] = 'utm_source is required.';
        if ($utmMedium === '') $errors['utm_medium'] = 'utm_medium is required.';
        if ($utmCampaign === '') $errors['utm_campaign'] = 'utm_campaign is required.';

        // utm_cv (Traffic Type) has to live on the campaign link itself, not just the
        // Tracking Configuration -- landing-page scripts read it straight off the
        // clicked URL via getUrlParam('utm_cv'), same as the other standard UTM params.
        $trafficTypeId = $toIntOrNull($input['traffic_type_id'] ?? null);
        $trafficTypeCode = null;
        if ($trafficTypeId === null) {
            $errors['traffic_type_id'] = 'Traffic Type (utm_cv) is required.';
        } else {
            $trafficType = TrafficType::find($trafficTypeId);
            if (!$trafficType) {
                $errors['traffic_type_id'] = 'Traffic Type (utm_cv) is required.';
            } else {
                $trafficTypeCode = $trafficType['code'];
            }
        }

        // Extra, channel-specific params come in as extra_params[key]=value from the
        // dynamic fields the JS renders for the selected Channel (e.g. network, device).
        // These go into the generated tracking URL's querystring.
        $extraParamsRaw = Request::post('extra_params', []);
        $extraParams = [];
        if (is_array($extraParamsRaw)) {
            foreach ($extraParamsRaw as $key => $value) {
                $key = preg_replace('/[^a-zA-Z0-9_]/', '', (string) $key);
                $value = trim((string) $value);
                if ($key !== '' && $value !== '') {
                    $extraParams[$key] = $value;
                }
            }
        }

        $generatedUrl = $targetUrl;
        if (empty($errors)) {
            $query = array_filter([
                'utm_source' => $utmSource,
                'utm_medium' => $utmMedium,
                'utm_campaign' => $utmCampaign,
                'utm_term' => $utmTerm ?: null,
                'utm_content' => $utmContent ?: null,
                'utm_cv' => $trafficTypeCode,
            ], fn($v) => $v !== null && $v !== '');
            $query = array_merge($query, $extraParams);
            $separator = str_contains($targetUrl, '?') ? '&' : '?';
            // http_build_query percent-encodes { and } like any other character, but ad
            // platforms' click-time placeholders (Google/Bing ValueTrack: {keyword},
            // {device}, {matchtype}, {network}, ...) only get recognized and substituted
            // when they appear literally, unencoded, in the URL -- so undo just those two.
            $generatedUrl = $targetUrl . $separator . self::unencodeValueTrackBraces(http_build_query($query));
        }

        // Full campaign brief: budget/schedule are one shared set of fields (every ad
        // platform has these). Objective/bidding strategy/bid amount are collected as
        // THREE separately-named inputs (google_*/meta_*/linkedin_*) -- one per fixed
        // platform fieldset on the form -- because a hidden fieldset's inputs still get
        // submitted, so same-named fields would silently collide. Which one actually
        // gets saved is resolved here from the channel's real platform_type, never
        // trusted from the client, so switching channels can't leave a stale value in
        // the wrong platform's slot.
        $platformType = 'other';
        if ($channelId !== null) {
            $channel = Channel::find($channelId);
            $platformType = $channel['platform_type'] ?? 'other';
        }
        // Form field names use the short prefix ("google_objective"), not the full
        // platform_type value ("google_ads_objective").
        $platformPrefixes = ['google_ads' => 'google', 'meta_ads' => 'meta', 'linkedin_ads' => 'linkedin'];
        $platformPrefix = $platformPrefixes[$platformType] ?? null;
        $toDecimalOrNull = fn($v) => ($v === null || trim((string) $v) === '') ? null : (float) $v;
        $toDateOrNull = fn($v) => ($v === null || trim((string) $v) === '') ? null : $v;
        $toCsvOrNull = function ($v) {
            if (!is_array($v)) return null;
            $clean = array_filter(array_map('trim', $v), fn($x) => $x !== '');
            return $clean ? implode(',', $clean) : null;
        };

        $budgetType = in_array($input['budget_type'] ?? '', ['daily', 'lifetime'], true) ? $input['budget_type'] : null;

        $data = [
            'landing_page_id' => $toIntOrNull($input['landing_page_id'] ?? null),
            'channel_id' => $channelId,
            'traffic_type_id' => $trafficTypeId,
            'name' => $name,
            'target_url' => $targetUrl,
            'utm_source' => $utmSource,
            'utm_medium' => $utmMedium,
            'utm_campaign' => $utmCampaign,
            'utm_term' => $utmTerm ?: null,
            'utm_content' => $utmContent ?: null,
            'extra_params' => $extraParams ? json_encode($extraParams) : null,
            'generated_url' => $generatedUrl,
            'status' => $status,

            'budget_type' => $budgetType,
            'budget_amount' => $toDecimalOrNull($input['budget_amount'] ?? null),
            'currency' => trim((string) ($input['currency'] ?? 'USD')) ?: 'USD',
            'start_date' => $toDateOrNull($input['start_date'] ?? null),
            'end_date' => $toDateOrNull($input['end_date'] ?? null),

            'objective' => $platformPrefix ? (trim((string) ($input[$platformPrefix . '_objective'] ?? '')) ?: null) : null,
            'bidding_strategy' => $platformPrefix ? (trim((string) ($input[$platformPrefix . '_bidding_strategy'] ?? '')) ?: null) : null,
            'bid_amount' => $platformPrefix ? $toDecimalOrNull($input[$platformPrefix . '_bid_amount'] ?? null) : null,

            // Platform-only fields are cleared for every platform except the one
            // actually selected, so an old value from a since-changed channel doesn't
            // linger unseen in the record (and show up wrong in an Excel export later).
            'google_campaign_type' => $platformType === 'google_ads' ? (trim((string) ($input['google_campaign_type'] ?? '')) ?: null) : null,
            'google_networks' => $platformType === 'google_ads' ? $toCsvOrNull($input['google_networks'] ?? null) : null,
            'google_languages' => $platformType === 'google_ads' ? (trim((string) ($input['google_languages'] ?? '')) ?: null) : null,
            'google_devices' => $platformType === 'google_ads' ? $toCsvOrNull($input['google_devices'] ?? null) : null,
            'meta_buying_type' => $platformType === 'meta_ads' ? (trim((string) ($input['meta_buying_type'] ?? '')) ?: null) : null,
            'meta_placements' => $platformType === 'meta_ads' ? $toCsvOrNull($input['meta_placements'] ?? null) : null,
            'meta_ad_format' => $platformType === 'meta_ads' ? (trim((string) ($input['meta_ad_format'] ?? '')) ?: null) : null,
            'linkedin_ad_format' => $platformType === 'linkedin_ads' ? (trim((string) ($input['linkedin_ad_format'] ?? '')) ?: null) : null,
            'linkedin_bid_type' => $platformType === 'linkedin_ads' ? (trim((string) ($input['linkedin_bid_type'] ?? '')) ?: null) : null,
        ];
        return [$errors, $data];
    }

    protected function afterSave(int $savedId, array $input): void
    {
        $customVariables = CustomVariable::allWithOptions(true, 'campaign');
        $posted = Request::post('custom_variables', []);
        $values = [];
        if (is_array($posted)) {
            foreach ($customVariables as $cv) {
                $values[(int) $cv['id']] = $posted[$cv['id']] ?? '';
            }
        }
        CustomVariableValue::saveForEntity('campaign', $savedId, $values);

        // Google Ads keyword rows (only meaningful for Search-type campaigns, but
        // harmless to save empty for other platforms since the form hides the table).
        $keywordText = Request::post('keyword_text', []);
        $keywordMatchType = Request::post('keyword_match_type', []);
        $keywordNegative = Request::post('keyword_negative', []);
        $keywordRows = [];
        if (is_array($keywordText)) {
            foreach ($keywordText as $i => $keyword) {
                $keywordRows[] = [
                    'keyword' => $keyword,
                    'match_type' => $keywordMatchType[$i] ?? 'broad',
                    'is_negative' => ($keywordNegative[$i] ?? '') === 'negative',
                ];
            }
        }
        CampaignKeyword::saveForCampaign($savedId, $keywordRows);

        // Meta and LinkedIn each render their own Targeting table with their own
        // criterion_type options, but write into the SAME campaign_targeting table --
        // so, same reasoning as objective/bidding_strategy in validate(), their posted
        // field names are prefixed (meta_targeting_*/linkedin_targeting_*) to avoid a
        // hidden fieldset's rows colliding with the visible one, and only the field set
        // matching the channel's actual (server-resolved) platform gets saved.
        $platformType = 'other';
        $channelId = (int) ($input['channel_id'] ?? 0);
        if ($channelId > 0) {
            $channel = Channel::find($channelId);
            $platformType = $channel['platform_type'] ?? 'other';
        }
        $prefix = $platformType === 'meta_ads' ? 'meta' : ($platformType === 'linkedin_ads' ? 'linkedin' : null);
        $targetingType = $prefix ? Request::post($prefix . '_targeting_type', []) : [];
        $targetingValue = $prefix ? Request::post($prefix . '_targeting_value', []) : [];
        $targetingRows = [];
        if (is_array($targetingType)) {
            foreach ($targetingType as $i => $type) {
                $targetingRows[] = ['criterion_type' => $type, 'criterion_value' => $targetingValue[$i] ?? ''];
            }
        }
        CampaignTargeting::saveForCampaign($savedId, $targetingRows);
    }

    /** Public + static so the same logic is trivially unit-testable and mirrors the JS version in campaign-builder.js. */
    public static function unencodeValueTrackBraces(string $queryString): string
    {
        return str_replace(['%7B', '%7D', '%7b', '%7d'], ['{', '}', '{', '}'], $queryString);
    }
}
