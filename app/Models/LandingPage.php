<?php

namespace App\Models;

use App\Core\Database;
use App\Core\TenantContext;

class LandingPage extends BaseModel
{
    protected static string $table = 'landing_pages';
    protected static array $fillable = [
        'page_type_id', 'vertical_id', 'service_id', 'lead_magnet_id', 'owner_user_id',
        'name', 'url', 'status', 'template', 'thumbnail_url', 'notes',
    ];

    public static function allWithRelations(): array
    {
        $tenantId = TenantContext::requireTenant();
        $stmt = Database::connection()->prepare(
            'SELECT lp.*, pt.name AS page_type_name, v.name AS vertical_name, v.short_code AS vertical_short_code,
                    s.name AS service_name, lm.name AS lead_magnet_name, u.name AS owner_name
             FROM landing_pages lp
             LEFT JOIN page_types pt ON pt.id = lp.page_type_id
             LEFT JOIN verticals v ON v.id = lp.vertical_id
             LEFT JOIN services s ON s.id = lp.service_id
             LEFT JOIN lead_magnets lm ON lm.id = lp.lead_magnet_id
             LEFT JOIN users u ON u.id = lp.owner_user_id
             WHERE lp.tenant_id = ? ORDER BY lp.created_at DESC'
        );
        $stmt->execute([$tenantId]);
        return $stmt->fetchAll();
    }

    public static function forDropdown(): array
    {
        $tenantId = TenantContext::requireTenant();
        $stmt = Database::connection()->prepare(
            'SELECT id, name, url, page_type_id, vertical_id, service_id, lead_magnet_id
             FROM landing_pages WHERE tenant_id = ? ORDER BY name ASC'
        );
        $stmt->execute([$tenantId]);
        return $stmt->fetchAll();
    }
}
