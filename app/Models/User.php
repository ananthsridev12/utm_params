<?php

namespace App\Models;

use App\Core\Database;
use App\Core\TenantContext;

class User
{
    public static function findByEmail(string $email): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT u.*, t.name AS tenant_name, t.status AS tenant_status
             FROM users u LEFT JOIN tenants t ON t.id = u.tenant_id
             WHERE u.id = ? LIMIT 1'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function touchLastLogin(int $id): void
    {
        $stmt = Database::connection()->prepare('UPDATE users SET last_login_at = NOW() WHERE id = ?');
        $stmt->execute([$id]);
    }

    public static function create(array $data): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO users (tenant_id, name, email, password_hash, role, is_super_admin, status)
             VALUES (?, ?, ?, ?, ?, 0, \'active\')'
        );
        $stmt->execute([
            $data['tenant_id'],
            $data['name'],
            $data['email'],
            $data['password_hash'],
            $data['role'],
        ]);
        return (int) Database::connection()->lastInsertId();
    }

    public static function allForTenant(): array
    {
        $tenantId = TenantContext::requireTenant();
        $stmt = Database::connection()->prepare('SELECT * FROM users WHERE tenant_id = ? ORDER BY created_at ASC');
        $stmt->execute([$tenantId]);
        return $stmt->fetchAll();
    }

    public static function findInTenant(int $id): ?array
    {
        $tenantId = TenantContext::requireTenant();
        $stmt = Database::connection()->prepare('SELECT * FROM users WHERE id = ? AND tenant_id = ? LIMIT 1');
        $stmt->execute([$id, $tenantId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function inviteToTenant(array $data): int
    {
        $tenantId = TenantContext::requireTenant();
        $stmt = Database::connection()->prepare(
            'INSERT INTO users (tenant_id, name, email, password_hash, role, is_super_admin, status)
             VALUES (?, ?, ?, ?, ?, 0, \'active\')'
        );
        $stmt->execute([
            $tenantId,
            $data['name'],
            $data['email'],
            $data['password_hash'],
            $data['role'],
        ]);
        return (int) Database::connection()->lastInsertId();
    }

    public static function updateInTenant(int $id, array $data): bool
    {
        $tenantId = TenantContext::requireTenant();
        $stmt = Database::connection()->prepare(
            'UPDATE users SET name = ?, role = ?, status = ? WHERE id = ? AND tenant_id = ?'
        );
        return $stmt->execute([$data['name'], $data['role'], $data['status'], $id, $tenantId]);
    }

    public static function resetPasswordInTenant(int $id, string $passwordHash): bool
    {
        $tenantId = TenantContext::requireTenant();
        $stmt = Database::connection()->prepare(
            'UPDATE users SET password_hash = ? WHERE id = ? AND tenant_id = ?'
        );
        return $stmt->execute([$passwordHash, $id, $tenantId]);
    }

    public static function deleteInTenant(int $id): bool
    {
        $tenantId = TenantContext::requireTenant();
        $stmt = Database::connection()->prepare('DELETE FROM users WHERE id = ? AND tenant_id = ?');
        return $stmt->execute([$id, $tenantId]);
    }

    public static function emailExists(string $email): bool
    {
        $stmt = Database::connection()->prepare('SELECT COUNT(*) FROM users WHERE email = ?');
        $stmt->execute([$email]);
        return (int) $stmt->fetchColumn() > 0;
    }
}
