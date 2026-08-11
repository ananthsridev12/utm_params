<?php

namespace App\Models;

use App\Core\Database;
use App\Core\TenantContext;

/**
 * The taxonomy-driven "URL sheet" equivalent: one row links a page to the
 * event/form/funnel taxonomy, gets a unique form_id slug, and can render its
 * tracking snippets live from the tenant's Snippet Templates.
 */
class TrackingConfig extends BaseModel
{
    protected static string $table = 'tracking_configs';
    protected static array $fillable = [
        'landing_page_id', 'page_url', 'page_type_id', 'vertical_id', 'service_id', 'lead_magnet_id',
        'form_type_id', 'form_location_id', 'funnel_stage_id', 'event_id', 'traffic_type_id',
        'form_id', 'status', 'notes',
    ];

    private const RELATIONS_SQL = '
        SELECT tc.*,
               lp.name AS landing_page_name,
               pt.name AS page_type_name, pt.short_code AS page_type_short_code,
               v.name AS vertical_name, v.short_code AS vertical_short_code,
               s.name AS service_name, s.slug AS service_slug,
               lm.name AS lead_magnet_name_full, lm.slug AS lead_magnet_slug,
               ft.name AS form_type_name,
               fl.name AS form_location_name,
               fs.name AS funnel_stage_name,
               ev.name AS event_name,
               tt.name AS traffic_type_name, tt.code AS traffic_type_code
        FROM tracking_configs tc
        LEFT JOIN landing_pages lp ON lp.id = tc.landing_page_id
        LEFT JOIN page_types pt ON pt.id = tc.page_type_id
        LEFT JOIN verticals v ON v.id = tc.vertical_id
        LEFT JOIN services s ON s.id = tc.service_id
        LEFT JOIN lead_magnets lm ON lm.id = tc.lead_magnet_id
        LEFT JOIN form_types ft ON ft.id = tc.form_type_id
        LEFT JOIN form_locations fl ON fl.id = tc.form_location_id
        LEFT JOIN funnel_stages fs ON fs.id = tc.funnel_stage_id
        LEFT JOIN events ev ON ev.id = tc.event_id
        LEFT JOIN traffic_types tt ON tt.id = tc.traffic_type_id
    ';

    public static function allWithRelations(): array
    {
        $tenantId = TenantContext::requireTenant();
        $stmt = Database::connection()->prepare(self::RELATIONS_SQL . ' WHERE tc.tenant_id = ? ORDER BY tc.created_at DESC');
        $stmt->execute([$tenantId]);
        return $stmt->fetchAll();
    }

    public static function findWithRelations(int $id): ?array
    {
        $tenantId = TenantContext::requireTenant();
        $stmt = Database::connection()->prepare(self::RELATIONS_SQL . ' WHERE tc.id = ? AND tc.tenant_id = ? LIMIT 1');
        $stmt->execute([$id, $tenantId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function formIdExists(string $formId, ?int $excludeId = null): bool
    {
        $tenantId = TenantContext::requireTenant();
        $sql = 'SELECT COUNT(*) FROM tracking_configs WHERE tenant_id = ? AND form_id = ?';
        $params = [$tenantId, $formId];
        if ($excludeId !== null) {
            $sql .= ' AND id != ?';
            $params[] = $excludeId;
        }
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }
}
