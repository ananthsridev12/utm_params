<?php

namespace App\Controllers;

use App\Models\PageType;

class PageTypeController extends BaseController
{
    protected string $modelClass = PageType::class;
    protected string $viewDir = 'page_types';
    protected string $routeBase = 'page-types';
    protected string $title = 'Page Type';

    protected function validate(array $input, ?int $id): array
    {
        $errors = [];
        $name = trim((string) ($input['name'] ?? ''));
        $shortCode = strtolower(trim((string) ($input['short_code'] ?? '')));
        $status = ($input['status'] ?? 'active') === 'active' ? 'active' : 'inactive';

        if ($name === '') $errors['name'] = 'Name is required.';
        if ($shortCode === '') $errors['short_code'] = 'Short code is required.';
        elseif (!preg_match('/^[a-z0-9_-]+$/', $shortCode)) $errors['short_code'] = 'Use lowercase letters, numbers, - or _ only.';

        $data = [
            'name' => $name,
            'short_code' => $shortCode,
            'description' => trim((string) ($input['description'] ?? '')) ?: null,
            'status' => $status,
        ];
        return [$errors, $data];
    }
}
