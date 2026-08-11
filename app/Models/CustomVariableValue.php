<?php

namespace App\Models;

use App\Core\Database;
use App\Core\TenantContext;

/**
 * Polymorphic values for Custom Variables, attached to either a
 * tracking_config or a campaign row. See database/schema.sql for why this
 * is one shared table instead of two near-identical ones.
 */
class CustomVariableValue
{
    private const ENTITY_TYPES = ['tracking_config', 'campaign'];

    /** @return array<string,string> key_name => value, scoped to the current tenant's variables. */
    public static function forEntityByKey(string $entityType, int $entityId): array
    {
        self::assertEntityType($entityType);
        $tenantId = TenantContext::requireTenant();
        $stmt = Database::connection()->prepare(
            'SELECT cv.key_name, v.value
             FROM custom_variable_values v
             JOIN custom_variables cv ON cv.id = v.custom_variable_id
             WHERE v.entity_type = ? AND v.entity_id = ? AND cv.tenant_id = ?'
        );
        $stmt->execute([$entityType, $entityId, $tenantId]);
        $out = [];
        foreach ($stmt->fetchAll() as $row) {
            $out[$row['key_name']] = $row['value'];
        }
        return $out;
    }

    /** @return array<int,string> custom_variable_id => value, for pre-filling an edit form. */
    public static function forEntityById(string $entityType, int $entityId): array
    {
        self::assertEntityType($entityType);
        $tenantId = TenantContext::requireTenant();
        $stmt = Database::connection()->prepare(
            'SELECT v.custom_variable_id, v.value
             FROM custom_variable_values v
             JOIN custom_variables cv ON cv.id = v.custom_variable_id
             WHERE v.entity_type = ? AND v.entity_id = ? AND cv.tenant_id = ?'
        );
        $stmt->execute([$entityType, $entityId, $tenantId]);
        $out = [];
        foreach ($stmt->fetchAll() as $row) {
            $out[(int) $row['custom_variable_id']] = $row['value'];
        }
        return $out;
    }

    /** @param array<int,string> $valuesByVariableId */
    public static function saveForEntity(string $entityType, int $entityId, array $valuesByVariableId): void
    {
        self::assertEntityType($entityType);
        $db = Database::connection();
        $db->prepare('DELETE FROM custom_variable_values WHERE entity_type = ? AND entity_id = ?')
            ->execute([$entityType, $entityId]);

        $stmt = $db->prepare(
            'INSERT INTO custom_variable_values (entity_type, entity_id, custom_variable_id, value) VALUES (?, ?, ?, ?)'
        );
        foreach ($valuesByVariableId as $variableId => $value) {
            $value = trim((string) $value);
            if ($value === '') continue;
            $stmt->execute([$entityType, $entityId, (int) $variableId, $value]);
        }
    }

    public static function deleteForEntity(string $entityType, int $entityId): void
    {
        self::assertEntityType($entityType);
        Database::connection()
            ->prepare('DELETE FROM custom_variable_values WHERE entity_type = ? AND entity_id = ?')
            ->execute([$entityType, $entityId]);
    }

    private static function assertEntityType(string $entityType): void
    {
        if (!in_array($entityType, self::ENTITY_TYPES, true)) {
            throw new \InvalidArgumentException("Unknown custom variable entity type: {$entityType}");
        }
    }
}
