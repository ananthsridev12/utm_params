<?php

namespace App\Models;

use App\Core\Database;

class Tenant
{
    public static function create(string $name, string $slug, ?string $domain = null): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO tenants (name, slug, primary_domain, status) VALUES (?, ?, ?, \'active\')'
        );
        $stmt->execute([$name, $slug, $domain]);
        return (int) Database::connection()->lastInsertId();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM tenants WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function slugExists(string $slug): bool
    {
        $stmt = Database::connection()->prepare('SELECT COUNT(*) FROM tenants WHERE slug = ?');
        $stmt->execute([$slug]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public static function update(int $id, array $data): bool
    {
        $stmt = Database::connection()->prepare(
            'UPDATE tenants SET name = ?, primary_domain = ? WHERE id = ?'
        );
        return $stmt->execute([$data['name'], $data['primary_domain'] ?: null, $id]);
    }

    /** Naming Conventions: the {{token}} patterns used to build form_id and suggest a Campaign name. */
    public static function updateNamingPatterns(int $id, string $trackingFormIdPattern, ?string $campaignNamePattern): bool
    {
        $stmt = Database::connection()->prepare(
            'UPDATE tenants SET tracking_form_id_pattern = ?, campaign_name_pattern = ? WHERE id = ?'
        );
        return $stmt->execute([$trackingFormIdPattern, $campaignNamePattern ?: null, $id]);
    }

    public static function all(): array
    {
        $stmt = Database::connection()->query(
            'SELECT t.*, (SELECT COUNT(*) FROM users u WHERE u.tenant_id = t.id) AS user_count
             FROM tenants t ORDER BY t.created_at DESC'
        );
        return $stmt->fetchAll();
    }

    public static function setStatus(int $id, string $status): bool
    {
        $stmt = Database::connection()->prepare('UPDATE tenants SET status = ? WHERE id = ?');
        return $stmt->execute([$status, $id]);
    }

    /** Tenant whose invite link is active and matches this token, or null if disabled/unknown/suspended. */
    public static function findByInviteToken(string $token): ?array
    {
        $stmt = Database::connection()->prepare(
            "SELECT * FROM tenants WHERE invite_token = ? AND invite_enabled = 1 AND status = 'active' LIMIT 1"
        );
        $stmt->execute([$token]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** (Re)generates the invite token and sets the role it grants; enables the link. Returns the new token. */
    public static function regenerateInviteLink(int $id, string $role): string
    {
        $token = bin2hex(random_bytes(20));
        $stmt = Database::connection()->prepare(
            'UPDATE tenants SET invite_token = ?, invite_role = ?, invite_enabled = 1 WHERE id = ?'
        );
        $stmt->execute([$token, $role, $id]);
        return $token;
    }

    public static function disableInviteLink(int $id): bool
    {
        $stmt = Database::connection()->prepare('UPDATE tenants SET invite_enabled = 0 WHERE id = ?');
        return $stmt->execute([$id]);
    }
}
