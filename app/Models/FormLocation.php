<?php

namespace App\Models;

class FormLocation extends BaseModel
{
    protected static string $table = 'form_locations';
    protected static array $fillable = ['name', 'description', 'status'];
}
