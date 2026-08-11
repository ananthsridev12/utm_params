<?php

namespace App\Models;

use App\Core\Database;
use App\Core\TenantContext;

/**
 * Tenant-editable, token-based code snippet templates (e.g. a GA4 dataLayer
 * push, a CRM lead object). Tracking Configurations render these by
 * substituting {{token}} placeholders with the record's actual values.
 */
class SnippetTemplate
{
    public static function allForTenant(): array
    {
        $tenantId = TenantContext::requireTenant();
        $stmt = Database::connection()->prepare('SELECT * FROM snippet_templates WHERE tenant_id = ? ORDER BY name ASC');
        $stmt->execute([$tenantId]);
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $tenantId = TenantContext::requireTenant();
        $stmt = Database::connection()->prepare('SELECT * FROM snippet_templates WHERE id = ? AND tenant_id = ? LIMIT 1');
        $stmt->execute([$id, $tenantId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function create(array $data): int
    {
        $tenantId = TenantContext::requireTenant();
        $stmt = Database::connection()->prepare(
            'INSERT INTO snippet_templates (tenant_id, key_name, name, template, is_default) VALUES (?, ?, ?, ?, 0)'
        );
        $stmt->execute([$tenantId, $data['key_name'], $data['name'], $data['template']]);
        return (int) Database::connection()->lastInsertId();
    }

    public static function update(int $id, array $data): bool
    {
        $tenantId = TenantContext::requireTenant();
        $stmt = Database::connection()->prepare(
            'UPDATE snippet_templates SET name = ?, template = ? WHERE id = ? AND tenant_id = ?'
        );
        return $stmt->execute([$data['name'], $data['template'], $id, $tenantId]);
    }

    public static function delete(int $id): bool
    {
        $tenantId = TenantContext::requireTenant();
        $stmt = Database::connection()->prepare('DELETE FROM snippet_templates WHERE id = ? AND tenant_id = ? AND is_default = 0');
        return $stmt->execute([$id, $tenantId]);
    }

    /**
     * Replaces {{token}} placeholders in $template with values from $context.
     * Unmatched tokens are left as empty strings.
     */
    public static function render(string $template, array $context): string
    {
        return preg_replace_callback('/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/', function ($m) use ($context) {
            return array_key_exists($m[1], $context) ? (string) $context[$m[1]] : '';
        }, $template) ?? $template;
    }

    /**
     * Builds the token => value map for a tracking_configs row that has
     * already been LEFT JOINed with its related taxonomy tables (see
     * TrackingConfig::findWithRelations()).
     */
    public static function buildContext(array $row): array
    {
        $jsString = fn(?string $v) => $v ? ("'" . addslashes($v) . "'") : 'null';

        return [
            'form_id'            => $row['form_id'] ?? '',
            'page_url'           => $row['page_url'] ?? '',
            'event_name'         => $row['event_name'] ?? '',
            'service_vertical'   => $row['vertical_short_code'] ?? '',
            'vertical_name'      => $row['vertical_name'] ?? '',
            'service'            => $row['service_slug'] ?? '',
            'service_js'         => $jsString($row['service_slug'] ?? null),
            'lead_magnet_name'   => $row['lead_magnet_slug'] ?? '',
            'lead_magnet_name_js'=> $jsString($row['lead_magnet_slug'] ?? null),
            'form_type'          => $row['form_type_name'] ?? '',
            'form_location'      => $row['form_location_name'] ?? '',
            'funnel_stage'       => $row['funnel_stage_name'] ?? '',
            'traffic_type'       => $row['traffic_type_code'] ?? '',
            'utm_cv'             => $row['traffic_type_code'] ?? '',
            'page_type'          => $row['page_type_name'] ?? '',
            'page_type_short'    => $row['page_type_short_code'] ?? '',
        ];
    }
}
