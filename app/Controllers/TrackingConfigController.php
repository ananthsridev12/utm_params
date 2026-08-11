<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\Request;
use App\Core\Str;
use App\Core\Url;
use App\Core\View;
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
use App\Models\TrackingConfig;
use App\Models\TrafficType;
use App\Models\Vertical;
use PDOException;

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
            'snippetPreviewContext' => null,
        ], $this->extraViewData()));
    }

    public function edit(array $params): void
    {
        // Viewers may open this page read-only to see the generated snippets;
        // the actual save (update()) still requires editor+ via BaseController.
        Auth::requireLogin();
        $record = TrackingConfig::findWithRelations((int) $params['id']);
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
            'snippetPreviewContext' => SnippetTemplate::buildContext($record),
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
        $formTypeId = $toIntOrNull($input['form_type_id'] ?? null);
        $formLocationId = $toIntOrNull($input['form_location_id'] ?? null);

        // Resolve short codes/slugs for the auto form_id builder.
        $pageTypeShort = $this->lookupField(PageType::class, $pageTypeId, 'short_code');
        $verticalCode = $this->lookupField(Vertical::class, $verticalId, 'short_code');
        $serviceSlug = $this->lookupField(Service::class, $serviceId, 'slug');
        $formTypeName = $this->lookupField(FormType::class, $formTypeId, 'name');
        $formLocationName = $this->lookupField(FormLocation::class, $formLocationId, 'name');

        $formId = trim((string) ($input['form_id'] ?? ''));
        if ($formId === '') {
            $formId = Str::buildFormId(
                (string) $pageTypeShort, (string) $verticalCode, (string) $serviceSlug,
                (string) $formTypeName, (string) $formLocationName
            );
        } else {
            $formId = strtolower(str_replace(' ', '-', $formId));
        }

        if ($formId === '' || $formId === '----') {
            $errors['form_id'] = 'Could not build a form_id -- fill in at least Page Type, Vertical, Form Type and Form Location, or enter one manually.';
        } elseif (TrackingConfig::formIdExists($formId, $id)) {
            $errors['form_id'] = 'This form_id is already used by another tracking configuration in your workspace: "' . $formId . '". Adjust the fields or enter a unique form_id manually.';
        }

        $data = [
            'landing_page_id' => $toIntOrNull($input['landing_page_id'] ?? null),
            'page_url' => $pageUrl,
            'page_type_id' => $pageTypeId,
            'vertical_id' => $verticalId,
            'service_id' => $serviceId,
            'lead_magnet_id' => $toIntOrNull($input['lead_magnet_id'] ?? null),
            'form_type_id' => $formTypeId,
            'form_location_id' => $formLocationId,
            'funnel_stage_id' => $toIntOrNull($input['funnel_stage_id'] ?? null),
            'event_id' => $toIntOrNull($input['event_id'] ?? null),
            'traffic_type_id' => $toIntOrNull($input['traffic_type_id'] ?? null),
            'form_id' => $formId,
            'status' => ($input['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active',
            'notes' => trim((string) ($input['notes'] ?? '')) ?: null,
        ];
        return [$errors, $data];
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
