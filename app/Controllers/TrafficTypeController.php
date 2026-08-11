<?php

namespace App\Controllers;

use App\Models\TrafficType;

class TrafficTypeController extends BaseController
{
    protected string $modelClass = TrafficType::class;
    protected string $viewDir = 'traffic_types';
    protected string $routeBase = 'traffic-types';
    protected string $title = 'Traffic Type';

    protected function validate(array $input, ?int $id): array
    {
        $errors = [];
        $code = strtolower(trim((string) ($input['code'] ?? '')));
        $name = trim((string) ($input['name'] ?? ''));
        $status = ($input['status'] ?? 'active') === 'active' ? 'active' : 'inactive';

        if ($code === '') $errors['code'] = 'Code is required.';
        elseif (!preg_match('/^[a-z0-9_-]+$/', $code)) $errors['code'] = 'Use lowercase letters, numbers, - or _ only.';

        if ($name === '') $errors['name'] = 'Name is required.';

        $data = [
            'code' => $code,
            'name' => $name,
            'description' => trim((string) ($input['description'] ?? '')) ?: null,
            'status' => $status,
        ];
        return [$errors, $data];
    }
}
