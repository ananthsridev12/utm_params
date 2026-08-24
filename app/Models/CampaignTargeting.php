<?php

namespace App\Models;

use App\Core\Database;

/**
 * Audience/targeting criterion rows for a Meta or LinkedIn Ads campaign, e.g.
 * ('location', 'United States') or ('job_title', 'VP of Marketing'). One
 * generic table shared by both platforms -- the Campaign form only offers
 * the criterion_type options relevant to the selected channel's platform.
 */
class CampaignTargeting
{
    public static function forCampaign(int $campaignId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM campaign_targeting WHERE campaign_id = ? ORDER BY sort_order ASC, id ASC'
        );
        $stmt->execute([$campaignId]);
        return $stmt->fetchAll();
    }

    /** @param array<int, array{criterion_type:string, criterion_value:string}> $rows */
    public static function saveForCampaign(int $campaignId, array $rows): void
    {
        $db = Database::connection();
        $db->prepare('DELETE FROM campaign_targeting WHERE campaign_id = ?')->execute([$campaignId]);

        $stmt = $db->prepare(
            'INSERT INTO campaign_targeting (campaign_id, criterion_type, criterion_value, sort_order) VALUES (?, ?, ?, ?)'
        );
        $order = 0;
        foreach ($rows as $row) {
            $type = trim((string) ($row['criterion_type'] ?? ''));
            $value = trim((string) ($row['criterion_value'] ?? ''));
            if ($type === '' || $value === '') continue;
            $stmt->execute([$campaignId, $type, $value, $order]);
            $order++;
        }
    }
}
