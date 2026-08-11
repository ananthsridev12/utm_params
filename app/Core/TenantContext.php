<?php

namespace App\Core;

/**
 * Resolves the "current tenant" for a request. Tenancy is resolved from the
 * logged-in user's tenant_id (not subdomain), so the app works on a single
 * domain with no wildcard DNS/vhost configuration required on shared hosting.
 */
class TenantContext
{
    public static function id(): ?int
    {
        $user = Auth::user();
        if (!$user || $user['tenant_id'] === null) {
            return null;
        }
        return (int) $user['tenant_id'];
    }

    public static function requireTenant(): int
    {
        $id = self::id();
        if ($id === null) {
            http_response_code(403);
            exit('No tenant context for this account.');
        }
        return $id;
    }
}
