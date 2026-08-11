<?php

namespace App\Controllers;

use App\Models\Campaign;

class CampaignController extends BaseController
{
    protected string $modelClass = Campaign::class;
    protected string $viewDir = 'campaigns';
    protected string $routeBase = 'campaigns';
    protected string $title = 'Campaign';

    protected function validate(array $input, ?int $id): array
    {
        $errors = [];
        $name = trim((string) ($input['name'] ?? ''));
        $targetUrl = trim((string) ($input['target_url'] ?? ''));
        $utmSource = trim((string) ($input['utm_source'] ?? ''));
        $utmMedium = trim((string) ($input['utm_medium'] ?? ''));
        $utmCampaign = trim((string) ($input['utm_campaign'] ?? ''));
        $utmTerm = trim((string) ($input['utm_term'] ?? ''));
        $utmContent = trim((string) ($input['utm_content'] ?? ''));
        $status = ($input['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';

        if ($name === '') $errors['name'] = 'Name is required.';
        if ($targetUrl === '' || !filter_var($targetUrl, FILTER_VALIDATE_URL)) {
            $errors['target_url'] = 'Enter a full valid URL, e.g. https://example.com/page.';
        }
        if ($utmSource === '') $errors['utm_source'] = 'utm_source is required.';
        if ($utmMedium === '') $errors['utm_medium'] = 'utm_medium is required.';
        if ($utmCampaign === '') $errors['utm_campaign'] = 'utm_campaign is required.';

        $generatedUrl = $targetUrl;
        if (empty($errors)) {
            $query = array_filter([
                'utm_source' => $utmSource,
                'utm_medium' => $utmMedium,
                'utm_campaign' => $utmCampaign,
                'utm_term' => $utmTerm ?: null,
                'utm_content' => $utmContent ?: null,
            ], fn($v) => $v !== null && $v !== '');
            $separator = str_contains($targetUrl, '?') ? '&' : '?';
            $generatedUrl = $targetUrl . $separator . http_build_query($query);
        }

        $data = [
            'name' => $name,
            'target_url' => $targetUrl,
            'utm_source' => $utmSource,
            'utm_medium' => $utmMedium,
            'utm_campaign' => $utmCampaign,
            'utm_term' => $utmTerm ?: null,
            'utm_content' => $utmContent ?: null,
            'generated_url' => $generatedUrl,
            'status' => $status,
        ];
        return [$errors, $data];
    }
}
