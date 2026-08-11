<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\TenantContext;
use App\Core\View;

class DashboardController
{
    public function index(): void
    {
        Auth::requireLogin();
        $tenantId = TenantContext::requireTenant();
        $db = Database::connection();

        $counts = [];
        foreach (['landing_pages', 'tracking_configs', 'campaigns', 'verticals', 'services', 'lead_magnets'] as $table) {
            $stmt = $db->prepare("SELECT COUNT(*) FROM {$table} WHERE tenant_id = ?");
            $stmt->execute([$tenantId]);
            $counts[$table] = (int) $stmt->fetchColumn();
        }

        $stmt = $db->prepare(
            'SELECT a.*, u.name AS user_name FROM audit_logs a
             LEFT JOIN users u ON u.id = a.user_id
             WHERE a.tenant_id = ? ORDER BY a.created_at DESC LIMIT 10'
        );
        $stmt->execute([$tenantId]);
        $recentActivity = $stmt->fetchAll();

        View::render('dashboard/index', [
            'title' => 'Dashboard',
            'counts' => $counts,
            'recentActivity' => $recentActivity,
        ]);
    }
}
