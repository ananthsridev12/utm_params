<?php

namespace App\Controllers;

use App\Models\FormType;

class FormTypeController extends BaseController
{
    protected string $modelClass = FormType::class;
    protected string $viewDir = 'form_types';
    protected string $routeBase = 'form-types';
    protected string $title = 'Form Type';

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
