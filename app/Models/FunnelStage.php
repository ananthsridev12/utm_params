<?php

namespace App\Models;

class FunnelStage extends BaseModel
{
    protected static string $table = 'funnel_stages';
    protected static array $fillable = ['name', 'sort_order', 'description', 'status'];
}
