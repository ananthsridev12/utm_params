<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\Request;
use App\Core\Url;
use App\Core\View;
use App\Models\User;
use PDOException;

/**
 * Shared request lifecycle for the simple master-data modules (Verticals,
 * Services, Page Types, Form Types, Form Locations, Funnel Stages, Events,
 * Lead Magnets, Traffic Types). Concrete controllers just set the four
 * properties below, provide validate(), and supply their own view templates
 * under app/Views/{viewDir}/ (index.php, form.php) since field sets differ
 * per module.
 */
abstract class BaseController
{
    /** @var class-string */
    protected string $modelClass;
    protected string $viewDir;
    protected string $routeBase;
    protected string $title;
    /** Minimum role to create/edit records. */
    protected string $writeRole = 'editor';
    /** Minimum role to delete records. */
    protected string $deleteRole = 'admin';

    /**
     * @return array{0: array<string,string>, 1: array<string,mixed>} [errors keyed by field, cleaned data]
     */
    abstract protected function validate(array $input, ?int $id): array;

    /** Extra data (e.g. dropdown option lists) merged into form/index views. */
    protected function extraViewData(): array
    {
        return [];
    }

    public function index(array $params = []): void
    {
        Auth::requireLogin();
        $model = $this->modelClass;
        View::render($this->viewDir . '/index', array_merge([
            'title' => $this->title,
            'routeBase' => $this->routeBase,
            'records' => $model::all(),
        ], $this->extraViewData()));
    }

    public function create(array $params = []): void
    {
        Auth::requireRole($this->writeRole);
        View::render($this->viewDir . '/form', array_merge([
            'title' => $this->title,
            'routeBase' => $this->routeBase,
            'mode' => 'create',
            'record' => [],
            'errors' => [],
        ], $this->extraViewData()));
    }

    public function store(array $params = []): void
    {
        Auth::requireRole($this->writeRole);
        $this->handleWrite(null);
    }

    public function edit(array $params): void
    {
        Auth::requireRole($this->writeRole);
        $model = $this->modelClass;
        $record = $model::find((int) $params['id']);
        if (!$record) {
            http_response_code(404);
            exit('Not found.');
        }
        View::render($this->viewDir . '/form', array_merge([
            'title' => $this->title,
            'routeBase' => $this->routeBase,
            'mode' => 'edit',
            'record' => $record,
            'errors' => [],
        ], $this->extraViewData()));
    }

    public function update(array $params): void
    {
        Auth::requireRole($this->writeRole);
        $this->handleWrite((int) $params['id']);
    }

    private function handleWrite(?int $id): void
    {
        if (!Csrf::verifyRequest()) {
            Flash::error('Your session expired, please try again.');
            header('Location: ' . Url::to($this->routeBase));
            exit;
        }

        [$errors, $data] = $this->validate(Request::all(), $id);

        if (!empty($errors)) {
            $model = $this->modelClass;
            $record = $id ? ($model::find($id) ?? []) : Request::all();
            View::render($this->viewDir . '/form', array_merge([
                'title' => $this->title,
                'routeBase' => $this->routeBase,
                'mode' => $id ? 'edit' : 'create',
                'record' => array_merge($record, Request::all(), ['id' => $id]),
                'errors' => $errors,
            ], $this->extraViewData()));
            return;
        }

        $model = $this->modelClass;
        try {
            if ($id) {
                $model::update($id, $data, Auth::id());
                Flash::success($this->title . ' updated.');
                $savedId = $id;
            } else {
                $savedId = $model::create($data, Auth::id());
                Flash::success($this->title . ' created.');
            }
        } catch (PDOException $e) {
            Flash::error('Could not save: a record with those values already exists.');
            header('Location: ' . Url::to($this->routeBase . ($id ? '/edit/' . $id : '/create')));
            exit;
        }

        $this->afterSave($savedId, Request::all());

        header('Location: ' . $this->redirectAfterSave($savedId));
        exit;
    }

    /**
     * Runs right after a successful create/update, before the redirect. No-op
     * by default; override to persist related/nested data alongside the main
     * record (e.g. Custom Variables' options, Tracking Configs' custom
     * variable values) using the same raw request the main save already used.
     */
    protected function afterSave(int $savedId, array $input): void
    {
    }

    /**
     * Where to send the user after a successful create/update. Defaults to
     * the module's index; override to land on e.g. the edit page instead.
     */
    protected function redirectAfterSave(int $savedId): string
    {
        return Url::to($this->routeBase);
    }

    public function destroy(array $params): void
    {
        Auth::requireRole($this->deleteRole);
        if (!Csrf::verifyRequest()) {
            Flash::error('Your session expired, please try again.');
            header('Location: ' . Url::to($this->routeBase));
            exit;
        }
        $model = $this->modelClass;
        try {
            $model::delete((int) $params['id']);
            Flash::success($this->title . ' deleted.');
        } catch (PDOException $e) {
            Flash::error('Could not delete: this record is still referenced elsewhere.');
        }
        header('Location: ' . Url::to($this->routeBase));
        exit;
    }

    public function exportCsv(array $params = []): void
    {
        Auth::requireLogin();
        $model = $this->modelClass;
        $this->streamCsv($this->routeBase . '.csv', $this->resolveExportRows($model::all('id ASC')));
    }

    /**
     * Strips columns that are meaningless outside the app (tenant_id is
     * implicit -- the export IS one tenant's data) and swaps raw
     * created_by/updated_by user IDs for the user's actual name, so a CSV
     * export never shows a bare database ID where a human-readable value
     * belongs. Override in a controller with its own foreign keys (e.g.
     * vertical_id) to resolve those too -- see ServiceController for the
     * pattern.
     */
    protected function resolveExportRows(array $rows): array
    {
        if (empty($rows)) {
            return [];
        }
        $userNames = [];
        foreach (User::allForTenant() as $u) {
            $userNames[(int) $u['id']] = $u['name'];
        }

        $out = [];
        foreach ($rows as $row) {
            unset($row['tenant_id']);
            foreach (['created_by', 'updated_by'] as $col) {
                if (array_key_exists($col, $row)) {
                    $row[$col] = $row[$col] ? ($userNames[(int) $row[$col]] ?? '') : '';
                }
            }
            $out[] = $row;
        }
        return $out;
    }

    /** Writes $rows (each an assoc array with the same keys) as a downloadable CSV. */
    protected function streamCsv(string $filename, array $rows): void
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $out = fopen('php://output', 'w');
        if (!empty($rows)) {
            fputcsv($out, array_keys($rows[0]), ',', '"', '\\');
            foreach ($rows as $row) {
                fputcsv($out, $row, ',', '"', '\\');
            }
        }
        fclose($out);
        exit;
    }
}
