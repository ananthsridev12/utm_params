<?php

namespace App\Models;

use App\Core\Database;
use App\Core\TenantContext;
use PDO;

/**
 * Base for every tenant-scoped master-data / module table. Every query goes
 * through tenant_id = ? so a bug in a controller can never leak another
 * tenant's rows -- multi-tenant isolation is enforced here, not per-caller.
 */
abstract class BaseModel
{
    protected static string $table;

    /** Columns editable via create()/update() from a form array. */
    protected static array $fillable = [];

    protected static function db(): PDO
    {
        return Database::connection();
    }

    public static function all(string $orderBy = 'id DESC'): array
    {
        $tenantId = TenantContext::requireTenant();
        $stmt = static::db()->prepare("SELECT * FROM " . static::$table . " WHERE tenant_id = ? ORDER BY {$orderBy}");
        $stmt->execute([$tenantId]);
        return $stmt->fetchAll();
    }

    public static function allActive(string $orderBy = 'name ASC'): array
    {
        $tenantId = TenantContext::requireTenant();
        $stmt = static::db()->prepare("SELECT * FROM " . static::$table . " WHERE tenant_id = ? AND status = 'active' ORDER BY {$orderBy}");
        $stmt->execute([$tenantId]);
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $tenantId = TenantContext::requireTenant();
        $stmt = static::db()->prepare("SELECT * FROM " . static::$table . " WHERE id = ? AND tenant_id = ? LIMIT 1");
        $stmt->execute([$id, $tenantId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function create(array $data, ?int $userId): int
    {
        $tenantId = TenantContext::requireTenant();
        $fields = array_intersect_key($data, array_flip(static::$fillable));

        $columns = array_keys($fields);
        $columns[] = 'tenant_id';
        $columns[] = 'created_by';
        $columns[] = 'updated_by';

        $placeholders = array_fill(0, count($columns), '?');
        $values = array_values($fields);
        $values[] = $tenantId;
        $values[] = $userId;
        $values[] = $userId;

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            static::$table,
            implode(', ', $columns),
            implode(', ', $placeholders)
        );
        $stmt = static::db()->prepare($sql);
        $stmt->execute($values);
        return (int) static::db()->lastInsertId();
    }

    public static function update(int $id, array $data, ?int $userId): bool
    {
        $tenantId = TenantContext::requireTenant();
        $fields = array_intersect_key($data, array_flip(static::$fillable));
        if (empty($fields)) {
            return false;
        }
        $set = array_map(fn($col) => "{$col} = ?", array_keys($fields));
        $set[] = 'updated_by = ?';

        $values = array_values($fields);
        $values[] = $userId;
        $values[] = $id;
        $values[] = $tenantId;

        $sql = sprintf(
            'UPDATE %s SET %s WHERE id = ? AND tenant_id = ?',
            static::$table,
            implode(', ', $set)
        );
        $stmt = static::db()->prepare($sql);
        return $stmt->execute($values);
    }

    public static function delete(int $id): bool
    {
        $tenantId = TenantContext::requireTenant();
        $stmt = static::db()->prepare('DELETE FROM ' . static::$table . ' WHERE id = ? AND tenant_id = ?');
        return $stmt->execute([$id, $tenantId]);
    }

    /** Case-insensitive exact match on any column, tenant-scoped. Used by CSV importers
     *  that reference related records by human-readable name instead of a numeric ID. */
    public static function findByColumn(string $column, string $value): ?array
    {
        $tenantId = TenantContext::requireTenant();
        $stmt = static::db()->prepare("SELECT * FROM " . static::$table . " WHERE tenant_id = ? AND LOWER({$column}) = LOWER(?) LIMIT 1");
        $stmt->execute([$tenantId, $value]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function count(): int
    {
        $tenantId = TenantContext::requireTenant();
        $stmt = static::db()->prepare('SELECT COUNT(*) FROM ' . static::$table . ' WHERE tenant_id = ?');
        $stmt->execute([$tenantId]);
        return (int) $stmt->fetchColumn();
    }

    public static function table(): string
    {
        return static::$table;
    }

    public static function fillable(): array
    {
        return static::$fillable;
    }
}
