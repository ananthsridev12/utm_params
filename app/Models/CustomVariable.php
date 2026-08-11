<?php

namespace App\Models;

use App\Core\Database;
use App\Core\TenantContext;

/**
 * Tenant-defined extra data-layer keys beyond the built-in taxonomy (Vertical,
 * Form Type, ...). Makes the app's snippet/naming-convention token set
 * extensible per tenant without a code change -- the "universal" part.
 */
class CustomVariable extends BaseModel
{
    protected static string $table = 'custom_variables';
    protected static array $fillable = [
        'key_name', 'label', 'source_type', 'applies_to_tracking_config', 'applies_to_campaign',
        'description', 'sort_order', 'status',
    ];

    /**
     * @param string|null $appliesTo Pass 'tracking_config' or 'campaign' to only return
     *   variables scoped to that form; null returns all of them (e.g. for the index page).
     */
    public static function allWithOptions(bool $activeOnly = false, ?string $appliesTo = null): array
    {
        $tenantId = TenantContext::requireTenant();
        $sql = 'SELECT * FROM custom_variables WHERE tenant_id = ?';
        if ($activeOnly) $sql .= " AND status = 'active'";
        if ($appliesTo === 'tracking_config') $sql .= ' AND applies_to_tracking_config = 1';
        if ($appliesTo === 'campaign') $sql .= ' AND applies_to_campaign = 1';
        $sql .= ' ORDER BY sort_order ASC, label ASC';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([$tenantId]);
        $variables = $stmt->fetchAll();

        if (empty($variables)) {
            return [];
        }

        $ids = array_column($variables, 'id');
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $optStmt = Database::connection()->prepare(
            "SELECT * FROM custom_variable_options WHERE custom_variable_id IN ($placeholders) ORDER BY sort_order ASC, label ASC"
        );
        $optStmt->execute($ids);
        $optionsByVariable = [];
        foreach ($optStmt->fetchAll() as $opt) {
            $optionsByVariable[$opt['custom_variable_id']][] = $opt;
        }

        foreach ($variables as &$v) {
            $v['options'] = $optionsByVariable[$v['id']] ?? [];
        }
        return $variables;
    }

    /**
     * Replaces all options for a variable (delete + re-insert), scoped
     * implicitly by tenant since $variableId only reaches here after
     * CustomVariable::find()/create()/update() already enforced tenant_id.
     *
     * @param array<int, array{value:string,label:string}> $options
     */
    public static function saveOptions(int $variableId, array $options): void
    {
        $db = Database::connection();
        $db->prepare('DELETE FROM custom_variable_options WHERE custom_variable_id = ?')->execute([$variableId]);
        if (empty($options)) {
            return;
        }
        $stmt = $db->prepare('INSERT INTO custom_variable_options (custom_variable_id, value, label, sort_order) VALUES (?, ?, ?, ?)');
        foreach (array_values($options) as $i => $opt) {
            $stmt->execute([$variableId, $opt['value'], $opt['label'], $i]);
        }
    }
}
