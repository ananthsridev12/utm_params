<?php

namespace App\Models;

class PageType extends BaseModel
{
    protected static string $table = 'page_types';
    protected static array $fillable = ['name', 'short_code', 'description', 'status'];
}
