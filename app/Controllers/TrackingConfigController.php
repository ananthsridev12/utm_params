<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\TenantContext;
use App\Core\Url;
use App\Core\View;
use App\Core\Request;
use App\Models\CustomVariable;
use App\Models\CustomVariableValue;
use App\Models\Event;
use App\Models\EventDef;
use App\Models\FormLocation;
use App\Models\FormType;
use App\Models\FunnelStage;
use App\Models\LandingPage;
use App\Models\LeadMagnet;
use App\Models\PageType;
use App\Models\Service;
use App\Models\SnippetTemplate;
use App\Models\Tenant;
use App\Models\TrackingConfig;
use App\Models\TrafficType;
use App\Models\Vertical;

class TrackingConfigController extends BaseController
{
    protected string $modelClass = TrackingConfig::class;
    protected string $viewDir = 'tracking_configs';
    protected string $routeBase = 'tracking-configs';
    protected string $title = 'Tracking Configuration';

    public function index(array $params = []): void
    {
        Auth::requireLogin();
        View::render($this->viewDir . '/index', [
            'title' => $this->title,
            'routeBase' => $this->routeBase,
            'records' => TrackingConfig::allWithRelations(),
        ]);
    }

    protected function extraViewData(): array
    {
        $eventModel = class_exists(EventDef::class) ? EventDef::class : (class_exists(Event::class) ? Event::class : null);
        $tenant = Tenant::find(TenantContext::requireTenant());

        return [
            'landingPageOptions' => LandingPage::forDropdown(),
            'pageTypeOptions' => PageType::allActive(),
            'verticalOptions' => Vertical::allActive(),
            'serviceOptions' => Service::allActive(),
            'leadMagnetOptions' => LeadMagnet::allActive(),
            'formTypeOptions' => FormType::allActive(),
            'formLocationOptions' => FormLocation::allActive(),
            'funnelStageOptions' => FunnelStage::allActive('sort_order ASC, name ASC'),
            'eventOptions' => $eventModel ? $eventModel::allActive() : [],
            'trafficTypeOptions' => TrafficType::allActive(),
            'snippetTemplates' => SnippetTemplate::allForTenant(),
            'customVariables' => CustomVariable::allWithOptions(true, 'tracking_config'),
            'formIdPattern' => $tenant['tracking_form_id_pattern'] ?? '',
        ];
    }

    public function create(array $params = []): void
    {
        Auth::requireRole($this->writeRole);
        $record = [];
        $landingPageId = Request::query('landing_page_id');
        if ($landingPageId) {
            foreach (LandingPage::forDropdown() as $lp) {
                if ((int) $lp['id'] === (int) $landingPageId) {
                    $record = [
                        'landing_page_id' => $lp['id'],
                        'page_url' => $lp['url'],
                        'page_type_id' => $lp['page_type_id'],
                        'vertical_id' => $lp['vertical_id'],
                        'service_id' => $lp['service_id'],
                        'lead_magnet_id' => $lp['lead_magnet_id'],
                    ];
                    break;
                }
            }
        }
        View::render($this->viewDir . '/form', array_merge([
            'title' => $this->title,
            'routeBase' => $this->routeBase,
            'mode' => 'create',
            'record' => $record,
            'errors' => [],
            'customValues' => [],
            'snippetPreviewContext' => null,
        ], $this->extraViewData()));
    }

    public function edit(array $params): void
    {
        // Viewers may open this page read-only to see the generated snippets;
        // the actual save (update()) still requires editor+ via BaseController.
        Auth::requireLogin();
        $id = (int) $params['id'];
        $record = TrackingConfig::findWithRelations($id);
        if (!$record) {
            http_response_code(404);
            exit('Not found.');
        }
        $customValuesByKey = CustomVariableValue::forEntityByKey('tracking_config', $id);
        View::render($this->viewDir . '/form', array_merge([
            'title' => $this->title,
            'routeBase' => $this->routeBase,
            'mode' => 'edit',
            'record' => $record,
            'errors' => [],
            'customValues' => CustomVariableValue::forEntityById('tracking_config', $id),
            'snippetPreviewContext' => SnippetTemplate::buildContext($record, $customValuesByKey),
        ], $this->extraViewData()));
    }

    protected function validate(array $input, ?int $id): array
    {
        $errors = [];
        $toIntOrNull = fn($v) => ($v === null || $v === '') ? null : (int) $v;

        $pageUrl = trim((string) ($input['page_url'] ?? ''));
        if ($pageUrl === '') $errors['page_url'] = 'Page URL is required.';

        $pageTypeId = $toIntOrNull($input['page_type_id'] ?? null);
        $verticalId = $toIntOrNull($input['vertical_id'] ?? null);
        $serviceId = $toIntOrNull($input['service_id'] ?? null);
        $leadMagnetId = $toIntOrNull($input['lead_magnet_id'] ?? null);
        $formTypeId = $toIntOrNull($input['form_type_id'] ?? null);
        $formLocationId = $toIntOrNull($input['form_location_id'] ?? null);
        $funnelStageId = $toIntOrNull($input['funnel_stage_id'] ?? null);
        $eventId = $toIntOrNull($input['event_id'] ?? null);
        $trafficTypeId = $toIntOrNull($input['traffic_type_id'] ?? null);

        $eventModel = class_exists(EventDef::class) ? EventDef::class : Event::class;

        // Resolve every field this tenant's form_id pattern could reference -- same
        // token names SnippetTemplate::buildContext() produces, so one token
        // vocabulary works across both Snippet Templates and Naming Conventions.
        $fakeRow = [
            'page_type_short_code' => $this->lookupField(PageType::class, $pageTypeId, 'short_code'),
            'page_type_name' => $this->lookupField(PageType::class, $pageTypeId, 'name'),
            'vertical_short_code' => $this->lookupField(Vertical::class, $verticalId, 'short_code'),
            'vertical_name' => $this->lookupField(Vertical::class, $verticalId, 'name'),
            'service_slug' => $this->lookupField(Service::class, $serviceId, 'slug'),
            'service_name' => $this->lookupField(Service::class, $serviceId, 'name'),
            'lead_magnet_slug' => $this->lookupField(LeadMagnet::class, $leadMagnetId, 'slug'),
            'form_type_name' => $this->lookupField(FormType::class, $formTypeId, 'name'),
            'form_location_name' => $this->lookupField(FormLocation::class, $formLocationId, 'name'),
            'funnel_stage_name' => $this->lookupField(FunnelStage::class, $funnelStageId, 'name'),
            'event_name' => $this->lookupField($eventModel, $eventId, 'name'),
            'traffic_type_code' => $this->lookupField(TrafficType::class, $trafficTypeId, 'code'),
            'form_id' => '',
            'page_url' => $pageUrl,
        ];

        $customVariables = CustomVariable::allWithOptions(true, 'tracking_config');
        $customValuesByVariableId = [];
        $customValuesByKey = [];
        $postedCustom = Request::post('custom_variables', []);
        if (is_array($postedCustom)) {
            foreach ($customVariables as $cv) {
                $value = trim((string) ($postedCustom[$cv['id']] ?? ''));
                $customValuesByVariableId[(int) $cv['id']] = $value;
                if ($value !== '') $customValuesByKey[$cv['key_name']] = $value;
            }
        }

        $tenant = Tenant::find(TenantContext::requireTenant());
        $pattern = $tenant['tracking_form_id_pattern'] ?? '';
        $tokenContext = SnippetTemplate::buildContext($fakeRow, $customValuesByKey);

        $formId = trim((string) ($input['form_id'] ?? ''));
        if ($formId === '') {
            $formId = strtolower(trim(SnippetTemplate::render($pattern, $tokenContext), '-'));
            $formId = preg_replace('/-{2,}/', '-', $formId);
        } else {
            $formId = strtolower(str_replace(' ', '-', $formId));
        }

        if ($formId === '') {
            $errors['form_id'] = 'Could not build a form_id from your Naming Convention pattern (Company Settings) -- fill in more fields above, or enter a form_id manually.';
        } elseif (TrackingConfig::formIdExists($formId, $id)) {
            $errors['form_id'] = 'This form_id is already used by another tracking configuration in your workspace: "' . $formId . '". Adjust the fields or enter a unique form_id manually.';
        }

        $data = [
            'landing_page_id' => $toIntOrNull($input['landing_page_id'] ?? null),
            'page_url' => $pageUrl,
            'page_type_id' => $pageTypeId,
            'vertical_id' => $verticalId,
            'service_id' => $serviceId,
            'lead_magnet_id' => $leadMagnetId,
            'form_type_id' => $formTypeId,
            'form_location_id' => $formLocationId,
            'funnel_stage_id' => $funnelStageId,
            'event_id' => $eventId,
            'traffic_type_id' => $trafficTypeId,
            'form_id' => $formId,
            'status' => ($input['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active',
            'notes' => trim((string) ($input['notes'] ?? '')) ?: null,
        ];
        return [$errors, $data];
    }

    protected function afterSave(int $savedId, array $input): void
    {
        $customVariables = CustomVariable::allWithOptions(true, 'tracking_config');
        $posted = Request::post('custom_variables', []);
        $values = [];
        if (is_array($posted)) {
            foreach ($customVariables as $cv) {
                $values[(int) $cv['id']] = $posted[$cv['id']] ?? '';
            }
        }
        CustomVariableValue::saveForEntity('tracking_config', $savedId, $values);
    }

    protected function redirectAfterSave(int $savedId): string
    {
        // Land on the edit page so the user immediately sees the generated snippets.
        return Url::to($this->routeBase . '/edit/' . $savedId);
    }

    private function lookupField(string $modelClass, ?int $id, string $field): ?string
    {
        if ($id === null) return null;
        $record = $modelClass::find($id);
        return $record[$field] ?? null;
    }
}
