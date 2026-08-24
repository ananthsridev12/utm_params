<?php

namespace App\Models;

use App\Core\Database;

/**
 * Keyword targeting rows for a Google/Bing Ads Search campaign -- a plain
 * one-to-many child of campaigns (real FK + ON DELETE CASCADE, unlike the
 * polymorphic custom_variable_values table), so no manual cleanup is needed
 * in Campaign::delete().
 */
class CampaignKeyword
{
    private const MATCH_TYPES = ['broad', 'phrase', 'exact'];

    public static function forCampaign(int $campaignId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM campaign_keywords WHERE campaign_id = ? ORDER BY sort_order ASC, id ASC'
        );
        $stmt->execute([$campaignId]);
        return $stmt->fetchAll();
    }

    /** @param array<int, array{keyword:string, match_type:string, is_negative:bool}> $rows */
    public static function saveForCampaign(int $campaignId, array $rows): void
    {
        $db = Database::connection();
        $db->prepare('DELETE FROM campaign_keywords WHERE campaign_id = ?')->execute([$campaignId]);

        $stmt = $db->prepare(
            'INSERT INTO campaign_keywords (campaign_id, keyword, match_type, is_negative, sort_order) VALUES (?, ?, ?, ?, ?)'
        );
        $order = 0;
        foreach ($rows as $row) {
            $keyword = trim((string) ($row['keyword'] ?? ''));
            if ($keyword === '') continue;
            $matchType = in_array($row['match_type'] ?? '', self::MATCH_TYPES, true) ? $row['match_type'] : 'broad';
            $isNegative = !empty($row['is_negative']) ? 1 : 0;
            $stmt->execute([$campaignId, $keyword, $matchType, $isNegative, $order]);
            $order++;
        }
    }
}
