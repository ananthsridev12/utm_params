<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Str;
use App\Core\View;
use App\Models\Service;
use App\Models\Vertical;

class ServiceController extends BaseController
{
    protected string $modelClass = Service::class;
    protected string $viewDir = 'services';
    protected string $routeBase = 'services';
    protected string $title = 'Service';

    protected function extraViewData(): array
    {
        return ['verticalOptions' => Vertical::allActive()];
    }

    public function index(array $params = []): void
    {
        Auth::requireLogin();
        View::render($this->viewDir . '/index', array_merge([
            'title' => $this->title,
            'routeBase' => $this->routeBase,
            'records' => Service::allWithVertical(),
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
                'description' => $r['description'],
                'status' => $r['status'],
                'created_at' => $r['created_at'],
                'updated_at' => $r['updated_at'],
            ];
        }, Service::allWithVertical());
        $this->streamCsv('services.csv', $rows);
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

        $data = [
            'vertical_id' => $verticalId,
            'name' => $name,
            'slug' => $slug,
            'description' => trim((string) ($input['description'] ?? '')) ?: null,
            'status' => $status,
        ];
        return [$errors, $data];
    }
}
