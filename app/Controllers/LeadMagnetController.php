<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Str;
use App\Core\View;
use App\Models\LeadMagnet;
use App\Models\Service;
use App\Models\Vertical;

class LeadMagnetController extends BaseController
{
    protected string $modelClass = LeadMagnet::class;
    protected string $viewDir = 'lead_magnets';
    protected string $routeBase = 'lead-magnets';
    protected string $title = 'Lead Magnet';

    protected function extraViewData(): array
    {
        return [
            'verticalOptions' => Vertical::allActive(),
            'serviceOptions' => Service::allActive(),
        ];
    }

    public function index(array $params = []): void
    {
        Auth::requireLogin();
        View::render($this->viewDir . '/index', array_merge([
            'title' => $this->title,
            'routeBase' => $this->routeBase,
            'records' => LeadMagnet::allWithRelations(),
        ], $this->extraViewData()));
    }

    public function exportCsv(array $params = []): void
    {
        Auth::requireLogin();
        $rows = array_map(function ($r) {
            return [
                'id' => $r['id'],
                'name' => $r['name'],
                'slug' => $r['slug'],
                'vertical' => $r['vertical_name'] ?? '',
                'service' => $r['service_name'] ?? '',
                'asset_url' => $r['asset_url'],
                'description' => $r['description'],
                'status' => $r['status'],
                'created_at' => $r['created_at'],
                'updated_at' => $r['updated_at'],
            ];
        }, LeadMagnet::allWithRelations());
        $this->streamCsv('lead-magnets.csv', $rows);
    }

    protected function validate(array $input, ?int $id): array
    {
        $errors = [];
        $name = trim((string) ($input['name'] ?? ''));
        $status = ($input['status'] ?? 'active') === 'active' ? 'active' : 'inactive';

        $slugInput = trim((string) ($input['slug'] ?? ''));
        $slug = $slugInput === '' ? Str::slugify($name) : Str::slugify($slugInput);

        if ($name === '') $errors['name'] = 'Name is required.';
        if ($slug === '') $errors['slug'] = 'Slug is required.';

        $verticalId = !empty($input['vertical_id']) ? (int) $input['vertical_id'] : null;
        $serviceId = !empty($input['service_id']) ? (int) $input['service_id'] : null;
        $assetUrl = trim((string) ($input['asset_url'] ?? '')) ?: null;

        $data = [
            'vertical_id' => $verticalId,
            'service_id' => $serviceId,
            'name' => $name,
            'slug' => $slug,
            'asset_url' => $assetUrl,
            'description' => trim((string) ($input['description'] ?? '')) ?: null,
            'status' => $status,
        ];
        return [$errors, $data];
    }
}
