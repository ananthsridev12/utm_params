<?php

namespace App\Models;

class FormType extends BaseModel
{
    protected static string $table = 'form_types';
    protected static array $fillable = ['name', 'description', 'status'];
}
