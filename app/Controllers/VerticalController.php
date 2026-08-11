<?php

namespace App\Controllers;

use App\Core\Str;
use App\Models\Vertical;

class VerticalController extends BaseController
{
    protected string $modelClass = Vertical::class;
    protected string $viewDir = 'verticals';
    protected string $routeBase = 'verticals';
    protected string $title = 'Vertical';

    protected function validate(array $input, ?int $id): array
    {
        $errors = [];
        $name = trim((string) ($input['name'] ?? ''));
        $shortCode = strtoupper(trim((string) ($input['short_code'] ?? '')));
        $status = ($input['status'] ?? 'active') === 'active' ? 'active' : 'inactive';

        if ($name === '') $errors['name'] = 'Name is required.';
        if ($shortCode === '') $errors['short_code'] = 'Short code is required.';
        elseif (!preg_match('/^[A-Z0-9_-]+$/', $shortCode)) $errors['short_code'] = 'Use letters, numbers, - or _ only.';

        $data = [
            'name' => $name,
            'short_code' => $shortCode,
            'description' => trim((string) ($input['description'] ?? '')) ?: null,
            'status' => $status,
        ];
        return [$errors, $data];
    }
}
