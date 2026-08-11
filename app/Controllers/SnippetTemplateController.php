<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\Request;
use App\Core\Str;
use App\Core\Url;
use App\Core\View;
use App\Models\SnippetTemplate;

class SnippetTemplateController
{
    public function index(): void
    {
        Auth::requireLogin();
        View::render('snippet_templates/index', [
            'title' => 'Snippet Templates',
            'records' => SnippetTemplate::allForTenant(),
        ]);
    }

    public function create(): void
    {
        Auth::requireRole('admin');
        View::render('snippet_templates/form', [
            'title' => 'Snippet Templates',
            'mode' => 'create',
            'record' => [],
            'errors' => [],
        ]);
    }

    public function edit(array $params): void
    {
        Auth::requireRole('admin');
        $record = SnippetTemplate::find((int) $params['id']);
        if (!$record) {
            http_response_code(404);
            exit('Not found.');
        }
        View::render('snippet_templates/form', [
            'title' => 'Snippet Templates',
            'mode' => 'edit',
            'record' => $record,
            'errors' => [],
        ]);
    }

    public function store(): void
    {
        Auth::requireRole('admin');
        $this->save(null);
    }

    public function update(array $params): void
    {
        Auth::requireRole('admin');
        $this->save((int) $params['id']);
    }

    private function save(?int $id): void
    {
        if (!Csrf::verifyRequest()) {
            Flash::error('Your session expired, please try again.');
            header('Location: ' . Url::to('snippet-templates'));
            exit;
        }

        $name = trim((string) Request::post('name'));
        $template = (string) Request::post('template');
        $keyName = $id ? null : (Str::slugify(trim((string) Request::post('key_name'))) ?: Str::slugify($name));
        $appliesToTrackingConfig = Request::post('applies_to_tracking_config') ? 1 : 0;

        $errors = [];
        if ($name === '') $errors['name'] = 'Name is required.';
        if (trim($template) === '') $errors['template'] = 'Template body is required.';
        if (!$id && $keyName === '') $errors['key_name'] = 'Key is required.';

        if (!empty($errors)) {
            View::render('snippet_templates/form', [
                'title' => 'Snippet Templates',
                'mode' => $id ? 'edit' : 'create',
                'record' => [
                    'id' => $id, 'name' => $name, 'template' => $template, 'key_name' => $keyName,
                    'applies_to_tracking_config' => $appliesToTrackingConfig,
                ],
                'errors' => $errors,
            ]);
            return;
        }

        $data = ['name' => $name, 'template' => $template, 'applies_to_tracking_config' => $appliesToTrackingConfig];
        if ($id) {
            SnippetTemplate::update($id, $data);
            Flash::success('Snippet template updated.');
        } else {
            SnippetTemplate::create($data + ['key_name' => $keyName]);
            Flash::success('Snippet template created.');
        }
        header('Location: ' . Url::to('snippet-templates'));
        exit;
    }

    public function destroy(array $params): void
    {
        Auth::requireRole('admin');
        if (!Csrf::verifyRequest()) {
            Flash::error('Your session expired, please try again.');
            header('Location: ' . Url::to('snippet-templates'));
            exit;
        }
        SnippetTemplate::delete((int) $params['id']);
        Flash::success('Snippet template deleted.');
        header('Location: ' . Url::to('snippet-templates'));
        exit;
    }
}
