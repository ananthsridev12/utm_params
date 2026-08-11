<?php

namespace App\Models;

use App\Core\Database;
use App\Core\TenantContext;

class Campaign extends BaseModel
{
    protected static string $table = 'campaigns';
    protected static array $fillable = [
        'landing_page_id', 'channel_id', 'name', 'target_url', 'utm_source', 'utm_medium',
        'utm_campaign', 'utm_term', 'utm_content', 'extra_params', 'generated_url', 'status',
    ];

    public static function allWithRelations(): array
    {
        $tenantId = TenantContext::requireTenant();
        $stmt = Database::connection()->prepare(
            'SELECT c.*, ch.name AS channel_name, lp.name AS landing_page_name
             FROM campaigns c
             LEFT JOIN channels ch ON ch.id = c.channel_id
             LEFT JOIN landing_pages lp ON lp.id = c.landing_page_id
             WHERE c.tenant_id = ? ORDER BY c.created_at DESC'
        );
        $stmt->execute([$tenantId]);
        return $stmt->fetchAll();
    }
}
