<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailTemplate extends Model
{
    protected $connection = 'mysql';

    protected $fillable = [
        'event_key', 'name', 'subject', 'body', 'available_variables', 'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'available_variables' => 'array',
    ];
}