<?php

namespace App\Models;

class TrafficType extends BaseModel
{
    protected static string $table = 'traffic_types';
    protected static array $fillable = ['code', 'name', 'description', 'status'];
}
