<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\Request;
use App\Core\Url;
use App\Core\View;
use App\Models\LandingPage;
use App\Models\LeadMagnet;
use App\Models\PageType;
use App\Models\Service;
use App\Models\User;
use App\Models\Vertical;
use PDOException;

class LandingPageController extends BaseController
{
    protected string $modelClass = LandingPage::class;
    protected string $viewDir = 'landing_pages';
    protected string $routeBase = 'landing-pages';
    protected string $title = 'Landing Page';

    public function index(array $params = []): void
    {
        Auth::requireLogin();
        View::render($this->viewDir . '/index', array_merge([
            'title' => $this->title,
            'routeBase' => $this->routeBase,
            'records' => LandingPage::allWithRelations(),
        ], $this->extraViewData()));
    }

    protected function extraViewData(): array
    {
        return [
            'pageTypeOptions' => PageType::allActive(),
            'verticalOptions' => Vertical::allActive(),
            'serviceOptions' => Service::allActive(),
            'leadMagnetOptions' => LeadMagnet::allActive(),
            'ownerOptions' => User::allForTenant(),
        ];
    }

    public function exportCsv(array $params = []): void
    {
        Auth::requireLogin();
        $rows = array_map(function ($r) {
            return [
                'id' => $r['id'],
                'name' => $r['name'],
                'url' => $r['url'],
                'page_type' => $r['page_type_name'] ?? '',
                'vertical' => $r['vertical_name'] ?? '',
                'service' => $r['service_name'] ?? '',
                'lead_magnet' => $r['lead_magnet_name'] ?? '',
                'owner' => $r['owner_name'] ?? '',
                'status' => $r['status'],
                'template' => $r['template'],
                'thumbnail_url' => $r['thumbnail_url'],
                'notes' => $r['notes'],
                'created_at' => $r['created_at'],
                'updated_at' => $r['updated_at'],
            ];
        }, LandingPage::allWithRelations());
        $this->streamCsv('landing-pages.csv', $rows);
    }

    public function edit(array $params): void
    {
        // Viewers may open this read-only; the actual save (update()) still
        // requires editor+ via BaseController.
        Auth::requireLogin();
        $record = LandingPage::find((int) $params['id']);
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

    public function showImport(): void
    {
        Auth::requireRole($this->writeRole);
        View::render($this->viewDir . '/import', [
            'title' => $this->title,
            'routeBase' => $this->routeBase,
            'results' => null,
        ]);
    }

    /** A blank-ish CSV with the exact headers the importer expects, plus one example row. */
    public function downloadTemplate(): void
    {
        Auth::requireLogin();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="landing-pages-import-template.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['name', 'url', 'page_type', 'vertical', 'service', 'lead_magnet', 'owner_email', 'status', 'template', 'thumbnail_url', 'notes'], ',', '"', '\\');
        fputcsv($out, [
            'CPQ Readiness Quiz', 'https://example.com/lp/cpq-quiz', 'assessment_landing_page',
            'Digital Transformation', 'CPQ', 'CPQ Readiness Assessment', 'demo@solidpro-es.com',
            'draft', '', '', 'Optional notes',
        ], ',', '"', '\\');
        fclose($out);
        exit;
    }

    /**
     * Bulk-creates Landing Pages from an uploaded CSV. References to other
     * modules (page_type, vertical, service, lead_magnet, owner_email) are
     * matched by human-readable name/email rather than a numeric ID -- see
     * BaseModel::findByColumn() -- since that's what's practical to type or
     * paste into a spreadsheet. An unmatched reference doesn't fail the row,
     * it's just left blank with a warning in the per-row results log, so one
     * typo doesn't block the whole import.
     */
    public function import(): void
    {
        Auth::requireRole($this->writeRole);
        if (!Csrf::verifyRequest()) {
            Flash::error('Your session expired, please try again.');
            header('Location: ' . Url::to($this->routeBase . '/import'));
            exit;
        }

        $file = Request::file('csv_file');
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            Flash::error('Choose a CSV file to upload.');
            header('Location: ' . Url::to($this->routeBase . '/import'));
            exit;
        }
        $handle = fopen($file['tmp_name'], 'r');
        $header = $handle ? fgetcsv($handle) : false;
        if (!$header) {
            if ($handle) fclose($handle);
            Flash::error('Could not read that file -- make sure it\'s a CSV with a header row.');
            header('Location: ' . Url::to($this->routeBase . '/import'));
            exit;
        }
        $header = array_map(fn($h) => strtolower(trim((string) $h)), $header);

        $created = 0;
        $log = [];
        $rowNum = 1;
        while (($row = fgetcsv($handle)) !== false) {
            $rowNum++;
            if (count(array_filter($row, fn($v) => trim((string) $v) !== '')) === 0) {
                continue; // blank row
            }
            $data = [];
            foreach ($header as $i => $col) {
                $data[$col] = trim((string) ($row[$i] ?? ''));
            }

            $name = $data['name'] ?? '';
            $url = $data['url'] ?? '';
            if ($name === '' || $url === '') {
                $log[] = "Row {$rowNum}: skipped -- name and url are both required.";
                continue;
            }

            $warnings = [];
            $lookup = function (string $modelClass, string $key) use ($data, &$warnings) {
                if (empty($data[$key])) return null;
                $record = $modelClass::findByColumn('name', $data[$key]);
                if (!$record) $warnings[] = "{$key} \"{$data[$key]}\" not found";
                return $record;
            };
            $pageType = $lookup(PageType::class, 'page_type');
            $vertical = $lookup(Vertical::class, 'vertical');
            $service = $lookup(Service::class, 'service');
            $leadMagnet = $lookup(LeadMagnet::class, 'lead_magnet');
            $owner = null;
            if (!empty($data['owner_email'])) {
                $owner = User::findByEmail($data['owner_email']);
                if (!$owner) $warnings[] = "owner \"{$data['owner_email']}\" not found";
            }
            $status = in_array($data['status'] ?? '', ['draft', 'live', 'archived'], true) ? $data['status'] : 'draft';

            try {
                LandingPage::create([
                    'name' => $name,
                    'url' => $url,
                    'page_type_id' => $pageType['id'] ?? null,
                    'vertical_id' => $vertical['id'] ?? null,
                    'service_id' => $service['id'] ?? null,
                    'lead_magnet_id' => $leadMagnet['id'] ?? null,
                    'owner_user_id' => $owner['id'] ?? null,
                    'status' => $status,
                    'template' => $data['template'] ?? null,
                    'thumbnail_url' => $data['thumbnail_url'] ?? null,
                    'notes' => $data['notes'] ?? null,
                ], Auth::id());
                $created++;
                $log[] = "Row {$rowNum}: created \"{$name}\"" . ($warnings ? ' (' . implode('; ', $warnings) . ')' : '') . '.';
            } catch (PDOException $e) {
                $log[] = "Row {$rowNum}: skipped -- \"{$url}\" already exists in your workspace, or the data was invalid.";
            }
        }
        fclose($handle);

        View::render($this->viewDir . '/import', [
            'title' => $this->title,
            'routeBase' => $this->routeBase,
            'results' => ['created' => $created, 'total' => $rowNum - 1, 'log' => $log],
        ]);
    }

    protected function validate(array $input, ?int $id): array
    {
        $errors = [];
        $name = trim((string) ($input['name'] ?? ''));
        $url = trim((string) ($input['url'] ?? ''));
        $status = in_array($input['status'] ?? '', ['draft', 'live', 'archived'], true) ? $input['status'] : 'draft';

        if ($name === '') $errors['name'] = 'Name is required.';
        if ($url === '') $errors['url'] = 'URL is required.';
        elseif (!filter_var($url, FILTER_VALIDATE_URL) && !preg_match('#^[a-z0-9.-]+\.[a-z]{2,}(/.*)?$#i', $url)) {
            $errors['url'] = 'Enter a valid URL, e.g. https://example.com/lp/page.';
        }

        $toIntOrNull = fn($v) => ($v === null || $v === '') ? null : (int) $v;

        $data = [
            'name' => $name,
            'url' => $url,
            'page_type_id' => $toIntOrNull($input['page_type_id'] ?? null),
            'vertical_id' => $toIntOrNull($input['vertical_id'] ?? null),
            'service_id' => $toIntOrNull($input['service_id'] ?? null),
            'lead_magnet_id' => $toIntOrNull($input['lead_magnet_id'] ?? null),
            'owner_user_id' => $toIntOrNull($input['owner_user_id'] ?? null),
            'status' => $status,
            'template' => trim((string) ($input['template'] ?? '')) ?: null,
            'thumbnail_url' => trim((string) ($input['thumbnail_url'] ?? '')) ?: null,
            'notes' => trim((string) ($input['notes'] ?? '')) ?: null,
        ];
        return [$errors, $data];
    }
}
