<?php

namespace App\Models;

use App\Core\Database;
use App\Core\TenantContext;

class Service extends BaseModel
{
    protected static string $table = 'services';
    protected static array $fillable = ['vertical_id', 'name', 'slug', 'description', 'status'];

    /** Services joined with their vertical name, for index display. */
    public static function allWithVertical(): array
    {
        $tenantId = TenantContext::requireTenant();
        $stmt = Database::connection()->prepare(
            'SELECT s.*, v.name AS vertical_name FROM services s ' .
            'LEFT JOIN verticals v ON v.id = s.vertical_id ' .
            'WHERE s.tenant_id = ? ORDER BY s.name ASC'
        );
        $stmt->execute([$tenantId]);
        return $stmt->fetchAll();
    }
}
