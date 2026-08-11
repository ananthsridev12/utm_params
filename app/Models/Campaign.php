<?php

namespace App\Models;

class Campaign extends BaseModel
{
    protected static string $table = 'campaigns';
    protected static array $fillable = [
        'name', 'target_url', 'utm_source', 'utm_medium', 'utm_campaign',
        'utm_term', 'utm_content', 'generated_url', 'status',
    ];
}
