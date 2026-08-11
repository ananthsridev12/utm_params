<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Str;
use App\Core\View;
use App\Models\CustomVariable;

class CustomVariableController extends BaseController
{
    protected string $modelClass = CustomVariable::class;
    protected string $viewDir = 'custom_variables';
    protected string $routeBase = 'custom-variables';
    protected string $title = 'Custom Variable';

    public function index(array $params = []): void
    {
        Auth::requireLogin();
        View::render($this->viewDir . '/index', [
            'title' => $this->title,
            'routeBase' => $this->routeBase,
            'records' => CustomVariable::allWithOptions(),
        ]);
    }

    public function edit(array $params): void
    {
        Auth::requireRole($this->writeRole);
        $records = CustomVariable::allWithOptions();
        $record = null;
        foreach ($records as $r) {
            if ((int) $r['id'] === (int) $params['id']) { $record = $r; break; }
        }
        if (!$record) {
            http_response_code(404);
            exit('Not found.');
        }
        View::render($this->viewDir . '/form', [
            'title' => $this->title,
            'routeBase' => $this->routeBase,
            'mode' => 'edit',
            'record' => $record,
            'errors' => [],
        ]);
    }

    protected function validate(array $input, ?int $id): array
    {
        $errors = [];
        $label = trim((string) ($input['label'] ?? ''));
        $keyName = strtolower(str_replace('-', '_', Str::slugify((string) ($input['key_name'] ?? ($label)))));
        $sourceType = ($input['source_type'] ?? '') === 'static_list' ? 'static_list' : 'free_text';

        if ($label === '') $errors['label'] = 'Label is required.';
        if ($keyName === '') $errors['key_name'] = 'Key is required.';
        elseif (!preg_match('/^[a-z][a-z0-9_]*$/', $keyName)) {
            $errors['key_name'] = 'Must start with a letter and contain only lowercase letters, numbers, and underscores.';
        }

        $data = [
            'key_name' => $keyName,
            'label' => $label,
            'source_type' => $sourceType,
            'applies_to_tracking_config' => !empty($input['applies_to_tracking_config']) ? 1 : 0,
            'applies_to_campaign' => !empty($input['applies_to_campaign']) ? 1 : 0,
            'description' => trim((string) ($input['description'] ?? '')) ?: null,
            'sort_order' => (int) ($input['sort_order'] ?? 0),
            'status' => ($input['status'] ?? 'active') === 'active' ? 'active' : 'inactive',
        ];
        return [$errors, $data];
    }

    protected function afterSave(int $savedId, array $input): void
    {
        $sourceType = ($input['source_type'] ?? '') === 'static_list' ? 'static_list' : 'free_text';
        if ($sourceType !== 'static_list') {
            CustomVariable::saveOptions($savedId, []);
            return;
        }

        $values = Request::post('option_value', []);
        $labels = Request::post('option_label', []);
        $options = [];
        if (is_array($values)) {
            foreach ($values as $i => $value) {
                $value = trim((string) $value);
                $label = trim((string) ($labels[$i] ?? ''));
                if ($value === '') continue;
                $options[] = ['value' => $value, 'label' => $label !== '' ? $label : $value];
            }
        }
        CustomVariable::saveOptions($savedId, $options);
    }
}
