<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\TenantContext;
use App\Core\View;
use App\Models\Campaign;
use App\Models\Channel;
use App\Models\CustomVariable;
use App\Models\CustomVariableValue;
use App\Models\LandingPage;
use App\Models\Service;
use App\Models\Tenant;
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
            ], $channels),
            'landingPageOptions' => LandingPage::forDropdown(),
            'verticalOptions' => Vertical::allActive(),
            'serviceOptions' => Service::allActive(),
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
            ], fn($v) => $v !== null && $v !== '');
            $query = array_merge($query, $extraParams);
            $separator = str_contains($targetUrl, '?') ? '&' : '?';
            $generatedUrl = $targetUrl . $separator . http_build_query($query);
        }

        $data = [
            'landing_page_id' => $toIntOrNull($input['landing_page_id'] ?? null),
            'channel_id' => $channelId,
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
    }
}
