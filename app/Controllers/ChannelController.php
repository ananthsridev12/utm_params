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

        $listPattern = '/^[a-zA-Z0-9_, -]+$/';
        $extraLabels = trim((string) ($input['extra_param_labels'] ?? ''));
        if ($extraLabels !== '' && !preg_match($listPattern, $extraLabels)) {
            $errors['extra_param_labels'] = 'Use a comma-separated list of simple names, e.g. network,device,matchtype.';
        }
        $recommendedSources = trim((string) ($input['recommended_sources'] ?? ''));
        if ($recommendedSources !== '' && !preg_match($listPattern, $recommendedSources)) {
            $errors['recommended_sources'] = 'Use a comma-separated list, e.g. google,youtube.';
        }
        $recommendedMediums = trim((string) ($input['recommended_mediums'] ?? ''));
        if ($recommendedMediums !== '' && !preg_match($listPattern, $recommendedMediums)) {
            $errors['recommended_mediums'] = 'Use a comma-separated list, e.g. cpc,ppc,paidsearch.';
        }

        $data = [
            'name' => $name,
            'short_code' => strtoupper(trim((string) ($input['short_code'] ?? ''))) ?: null,
            'default_utm_source' => trim((string) ($input['default_utm_source'] ?? '')) ?: null,
            'default_utm_medium' => trim((string) ($input['default_utm_medium'] ?? '')) ?: null,
            'recommended_sources' => $recommendedSources ?: null,
            'recommended_mediums' => $recommendedMediums ?: null,
            'term_label' => trim((string) ($input['term_label'] ?? '')) ?: null,
            'requires_term' => !empty($input['requires_term']) ? 1 : 0,
            'extra_param_labels' => $extraLabels ?: null,
            'description' => trim((string) ($input['description'] ?? '')) ?: null,
            'status' => ($input['status'] ?? 'active') === 'active' ? 'active' : 'inactive',
        ];
        return [$errors, $data];
    }
}
