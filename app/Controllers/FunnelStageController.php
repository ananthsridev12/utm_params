<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\FunnelStage;

class FunnelStageController extends BaseController
{
    protected string $modelClass = FunnelStage::class;
    protected string $viewDir = 'funnel_stages';
    protected string $routeBase = 'funnel-stages';
    protected string $title = 'Funnel Stage';

    public function index(array $params = []): void
    {
        Auth::requireLogin();
        View::render($this->viewDir . '/index', array_merge([
            'title' => $this->title,
            'routeBase' => $this->routeBase,
            'records' => FunnelStage::all('sort_order ASC, name ASC'),
        ], $this->extraViewData()));
    }

    protected function validate(array $input, ?int $id): array
    {
        $errors = [];
        $name = trim((string) ($input['name'] ?? ''));
        $status = ($input['status'] ?? 'active') === 'active' ? 'active' : 'inactive';

        if ($name === '') $errors['name'] = 'Name is required.';

        $data = [
            'name' => $name,
            'sort_order' => (int) ($input['sort_order'] ?? 0),
            'description' => trim((string) ($input['description'] ?? '')) ?: null,
            'status' => $status,
        ];
        return [$errors, $data];
    }
}
