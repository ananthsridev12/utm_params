<?php

namespace App\Models;

class Vertical extends BaseModel
{
    protected static string $table = 'verticals';
    protected static array $fillable = ['name', 'short_code', 'description', 'status'];
}
