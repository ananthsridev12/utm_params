<?php

namespace App\Models;

use App\Core\Database;
use App\Core\TenantContext;

class LeadMagnet extends BaseModel
{
    protected static string $table = 'lead_magnets';
    protected static array $fillable = ['vertical_id', 'service_id', 'name', 'slug', 'asset_url', 'description', 'status'];

    /** Lead magnets joined with their vertical/service names, for index display. */
    public static function allWithRelations(): array
    {
        $tenantId = TenantContext::requireTenant();
        $stmt = Database::connection()->prepare(
            'SELECT lm.*, v.name AS vertical_name, s.name AS service_name FROM lead_magnets lm ' .
            'LEFT JOIN verticals v ON v.id = lm.vertical_id ' .
            'LEFT JOIN services s ON s.id = lm.service_id ' .
            'WHERE lm.tenant_id = ? ORDER BY lm.name ASC'
        );
        $stmt->execute([$tenantId]);
        return $stmt->fetchAll();
    }
}
