<?php

namespace App\Models;

use App\Core\Database;
use App\Core\TenantContext;

class Campaign extends BaseModel
{
    protected static string $table = 'campaigns';
    protected static array $fillable = [
        'landing_page_id', 'channel_id', 'traffic_type_id', 'name', 'target_url', 'utm_source', 'utm_medium',
        'utm_campaign', 'utm_term', 'utm_content', 'extra_params', 'generated_url', 'status',
        // Full campaign brief -- common fields, resolved server-side from whichever
        // platform-specific block (Google/Meta/LinkedIn) the selected channel maps to.
        'objective', 'budget_type', 'budget_amount', 'currency', 'bidding_strategy', 'bid_amount',
        'start_date', 'end_date',
        'google_campaign_type', 'google_networks', 'google_languages', 'google_devices',
        'meta_buying_type', 'meta_placements', 'meta_ad_format',
        'linkedin_ad_format', 'linkedin_bid_type',
    ];

    public static function allWithRelations(): array
    {
        $tenantId = TenantContext::requireTenant();
        $stmt = Database::connection()->prepare(
            'SELECT c.*, ch.name AS channel_name, ch.short_code AS channel_short_code, ch.platform_type AS channel_platform_type,
                    lp.name AS landing_page_name, tt.name AS traffic_type_name, tt.code AS traffic_type_code
             FROM campaigns c
             LEFT JOIN channels ch ON ch.id = c.channel_id
             LEFT JOIN landing_pages lp ON lp.id = c.landing_page_id
             LEFT JOIN traffic_types tt ON tt.id = c.traffic_type_id
             WHERE c.tenant_id = ? ORDER BY c.created_at DESC'
        );
        $stmt->execute([$tenantId]);
        return $stmt->fetchAll();
    }

    /** Running count for this tenant, for the {{seq}} naming-convention token. */
    public static function nextSeq(): int
    {
        return self::count() + 1;
    }

    public static function delete(int $id): bool
    {
        CustomVariableValue::deleteForEntity('campaign', $id);
        return parent::delete($id);
    }
}
