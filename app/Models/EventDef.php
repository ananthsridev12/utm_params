<?php

namespace App\Models;

class EventDef extends BaseModel
{
    protected static string $table = 'events';
    protected static array $fillable = ['name', 'description', 'status'];
}
