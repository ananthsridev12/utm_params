<?php

namespace App\Models;

use App\Core\Database;
use App\Core\TenantContext;
use Throwable;

class Campaign extends BaseModel
{
    protected static string $table = 'campaigns';
    protected static array $fillable = [
        'landing_page_id', 'channel_id', 'traffic_type_id', 'seq_number', 'name', 'target_url', 'utm_source', 'utm_medium',
        'utm_campaign', 'utm_term', 'utm_content', 'extra_params', 'generated_url', 'status',
    ];

    public static function allWithRelations(): array
    {
        $tenantId = TenantContext::requireTenant();
        $stmt = Database::connection()->prepare(
            'SELECT c.*, ch.name AS channel_name, ch.short_code AS channel_short_code, lp.name AS landing_page_name,
                    tt.name AS traffic_type_name, tt.code AS traffic_type_code
             FROM campaigns c
             LEFT JOIN channels ch ON ch.id = c.channel_id
             LEFT JOIN landing_pages lp ON lp.id = c.landing_page_id
             LEFT JOIN traffic_types tt ON tt.id = c.traffic_type_id
             WHERE c.tenant_id = ? ORDER BY c.created_at DESC'
        );
        $stmt->execute([$tenantId]);
        return $stmt->fetchAll();
    }

    /**
     * Read-only preview of the tenant's persisted campaign-sequence counter,
     * for the {{seq}} naming-convention token shown while the form is open.
     * NOT incremented here -- the number actually assigned to a saved
     * campaign is reserved transactionally in create() below, so two people
     * opening the create form at the same moment may preview the same
     * number but only one will actually receive it when they save.
     */
    public static function nextSeq(): int
    {
        $tenantId = TenantContext::requireTenant();
        $stmt = Database::connection()->prepare('SELECT next_campaign_seq FROM tenants WHERE id = ?');
        $stmt->execute([$tenantId]);
        $seq = $stmt->fetchColumn();
        return $seq !== false ? (int) $seq : 1;
    }

    /**
     * Reserves the tenant's next campaign sequence number and assigns it to
     * this campaign's seq_number, atomically and without ever reusing a
     * number (even after deletes). Locks the tenant row for the duration of
     * the reservation so two concurrent creates for the same tenant can't
     * receive the same number.
     */
    public static function create(array $data, ?int $userId): int
    {
        $tenantId = TenantContext::requireTenant();
        $pdo = Database::connection();
        $ownsTransaction = !$pdo->inTransaction();
        if ($ownsTransaction) {
            $pdo->beginTransaction();
        }
        try {
            $stmt = $pdo->prepare('SELECT next_campaign_seq FROM tenants WHERE id = ? FOR UPDATE');
            $stmt->execute([$tenantId]);
            $seq = (int) $stmt->fetchColumn();
            if ($seq < 1) {
                $seq = 1;
            }

            $data['seq_number'] = $seq;
            $id = parent::create($data, $userId);

            $update = $pdo->prepare('UPDATE tenants SET next_campaign_seq = ? WHERE id = ?');
            $update->execute([$seq + 1, $tenantId]);

            if ($ownsTransaction) {
                $pdo->commit();
            }
            return $id;
        } catch (Throwable $e) {
            if ($ownsTransaction) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    public static function delete(int $id): bool
    {
        CustomVariableValue::deleteForEntity('campaign', $id);
        return parent::delete($id);
    }
}
