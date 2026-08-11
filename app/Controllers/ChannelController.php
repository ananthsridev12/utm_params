<?php

namespace App\Controllers;

use App\Models\Channel;

class ChannelController extends BaseController
{
    protected string $modelClass = Channel::class;
    protected string $viewDir = 'channels';
    protected string $routeBase = 'channels';
    protected string $title = 'Channel';

    protected function validate(array $input, ?int $id): array
    {
        $errors = [];
        $name = trim((string) ($input['name'] ?? ''));
        if ($name === '') $errors['name'] = 'Name is required.';

        $extraLabels = trim((string) ($input['extra_param_labels'] ?? ''));
        if ($extraLabels !== '' && !preg_match('/^[a-zA-Z0-9_, -]+$/', $extraLabels)) {
            $errors['extra_param_labels'] = 'Use a comma-separated list of simple names, e.g. network,device,matchtype.';
        }

        $data = [
            'name' => $name,
            'default_utm_source' => trim((string) ($input['default_utm_source'] ?? '')) ?: null,
            'default_utm_medium' => trim((string) ($input['default_utm_medium'] ?? '')) ?: null,
            'term_label' => trim((string) ($input['term_label'] ?? '')) ?: null,
            'extra_param_labels' => $extraLabels ?: null,
            'description' => trim((string) ($input['description'] ?? '')) ?: null,
            'status' => ($input['status'] ?? 'active') === 'active' ? 'active' : 'inactive',
        ];
        return [$errors, $data];
    }
}
