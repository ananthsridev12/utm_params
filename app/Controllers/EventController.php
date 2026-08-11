<?php

namespace App\Controllers;

use App\Models\EventDef;

class EventController extends BaseController
{
    protected string $modelClass = EventDef::class;
    protected string $viewDir = 'events';
    protected string $routeBase = 'events';
    protected string $title = 'Event';

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
