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
