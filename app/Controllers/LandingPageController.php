<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\LandingPage;
use App\Models\LeadMagnet;
use App\Models\PageType;
use App\Models\Service;
use App\Models\User;
use App\Models\Vertical;

class LandingPageController extends BaseController
{
    protected string $modelClass = LandingPage::class;
    protected string $viewDir = 'landing_pages';
    protected string $routeBase = 'landing-pages';
    protected string $title = 'Landing Page';

    public function index(array $params = []): void
    {
        Auth::requireLogin();
        View::render($this->viewDir . '/index', array_merge([
            'title' => $this->title,
            'routeBase' => $this->routeBase,
            'records' => LandingPage::allWithRelations(),
        ], $this->extraViewData()));
    }

    protected function extraViewData(): array
    {
        return [
            'pageTypeOptions' => PageType::allActive(),
            'verticalOptions' => Vertical::allActive(),
            'serviceOptions' => Service::allActive(),
            'leadMagnetOptions' => LeadMagnet::allActive(),
            'ownerOptions' => User::allForTenant(),
        ];
    }

    public function exportCsv(array $params = []): void
    {
        Auth::requireLogin();
        $rows = array_map(function ($r) {
            return [
                'id' => $r['id'],
                'name' => $r['name'],
                'url' => $r['url'],
                'page_type' => $r['page_type_name'] ?? '',
                'vertical' => $r['vertical_name'] ?? '',
                'service' => $r['service_name'] ?? '',
                'lead_magnet' => $r['lead_magnet_name'] ?? '',
                'owner' => $r['owner_name'] ?? '',
                'status' => $r['status'],
                'template' => $r['template'],
                'thumbnail_url' => $r['thumbnail_url'],
                'notes' => $r['notes'],
                'created_at' => $r['created_at'],
                'updated_at' => $r['updated_at'],
            ];
        }, LandingPage::allWithRelations());
        $this->streamCsv('landing-pages.csv', $rows);
    }

    public function edit(array $params): void
    {
        // Viewers may open this read-only; the actual save (update()) still
        // requires editor+ via BaseController.
        Auth::requireLogin();
        $record = LandingPage::find((int) $params['id']);
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
        ], $this->extraViewData()));
    }

    protected function validate(array $input, ?int $id): array
    {
        $errors = [];
        $name = trim((string) ($input['name'] ?? ''));
        $url = trim((string) ($input['url'] ?? ''));
        $status = in_array($input['status'] ?? '', ['draft', 'live', 'archived'], true) ? $input['status'] : 'draft';

        if ($name === '') $errors['name'] = 'Name is required.';
        if ($url === '') $errors['url'] = 'URL is required.';
        elseif (!filter_var($url, FILTER_VALIDATE_URL) && !preg_match('#^[a-z0-9.-]+\.[a-z]{2,}(/.*)?$#i', $url)) {
            $errors['url'] = 'Enter a valid URL, e.g. https://example.com/lp/page.';
        }

        $toIntOrNull = fn($v) => ($v === null || $v === '') ? null : (int) $v;

        $data = [
            'name' => $name,
            'url' => $url,
            'page_type_id' => $toIntOrNull($input['page_type_id'] ?? null),
            'vertical_id' => $toIntOrNull($input['vertical_id'] ?? null),
            'service_id' => $toIntOrNull($input['service_id'] ?? null),
            'lead_magnet_id' => $toIntOrNull($input['lead_magnet_id'] ?? null),
            'owner_user_id' => $toIntOrNull($input['owner_user_id'] ?? null),
            'status' => $status,
            'template' => trim((string) ($input['template'] ?? '')) ?: null,
            'thumbnail_url' => trim((string) ($input['thumbnail_url'] ?? '')) ?: null,
            'notes' => trim((string) ($input['notes'] ?? '')) ?: null,
        ];
        return [$errors, $data];
    }
}
