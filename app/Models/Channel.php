<?php

namespace App\Models;

class Channel extends BaseModel
{
    protected static string $table = 'channels';
    protected static array $fillable = [
        'name', 'default_utm_source', 'default_utm_medium', 'term_label',
        'extra_param_labels', 'description', 'status',
    ];

    /**
     * Parses the comma-separated extra_param_labels column into a clean
     * list of [key, label] pairs, e.g. "network,device" -> [['network',
     * 'Network'], ['device', 'Device']]. Used to render the Campaign
     * form's dynamic extra-parameter inputs for the selected channel.
     */
    public static function parseExtraParamLabels(?string $csv): array
    {
        if (!$csv || trim($csv) === '') {
            return [];
        }
        $out = [];
        foreach (explode(',', $csv) as $raw) {
            $key = trim($raw);
            if ($key === '') continue;
            $label = ucwords(str_replace(['_', '-'], ' ', $key));
            $out[] = ['key' => $key, 'label' => $label];
        }
        return $out;
    }
}
