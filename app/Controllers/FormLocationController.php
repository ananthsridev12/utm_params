<?php

namespace App\Controllers;

use App\Models\FormLocation;

class FormLocationController extends BaseController
{
    protected string $modelClass = FormLocation::class;
    protected string $viewDir = 'form_locations';
    protected string $routeBase = 'form-locations';
    protected string $title = 'Form Location';

    protected function validate(array $input, ?int $id): array
    {
        $errors = [];
        $name = trim((string) ($input['name'] ?? ''));
        $status = ($input['status'] ?? 'active') === 'active' ? 'active' : 'inactive';

        if ($name === '') $errors['name'] = 'Name is required.';

        $data = [
            'name' => $name,
            'description' => trim((string) ($input['description'] ?? '')) ?: null,
            'status' => $status,
        ];
        return [$errors, $data];
    }
}
