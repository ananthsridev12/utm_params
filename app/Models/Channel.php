<?php

namespace App\Models;

class Channel extends BaseModel
{
    protected static string $table = 'channels';
    protected static array $fillable = [
        'name', 'short_code', 'default_utm_source', 'default_utm_medium',
        'recommended_sources', 'recommended_mediums', 'term_label', 'requires_term',
        'extra_param_labels', 'description', 'status',
    ];

    /**
     * Parses a comma-separated list into clean value/label pairs, e.g.
     * "cpc,ppc,paidsearch" -> [['value'=>'cpc','label'=>'cpc'], ...]. Used for
     * both extra_param_labels (as key/label) and recommended_sources/mediums
     * (as clickable suggestion chips) on the Campaign form.
     */
    public static function parseCsvList(?string $csv): array
    {
        if (!$csv || trim($csv) === '') {
            return [];
        }
        $out = [];
        foreach (explode(',', $csv) as $raw) {
            $value = trim($raw);
            if ($value === '') continue;
            $out[] = $value;
        }
        return $out;
    }

    /**
     * Parses the comma-separated extra_param_labels column into a clean
     * list of [key, label] pairs, e.g. "network,device" -> [['network',
     * 'Network'], ['device', 'Device']]. Used to render the Campaign
     * form's dynamic extra-parameter inputs for the selected channel.
     */
    public static function parseExtraParamLabels(?string $csv): array
    {
        $out = [];
        foreach (self::parseCsvList($csv) as $key) {
            $out[] = ['key' => $key, 'label' => ucwords(str_replace(['_', '-'], ' ', $key))];
        }
        return $out;
    }
}
