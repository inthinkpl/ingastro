<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $guarded = [];

    protected $casts = [
        'features' => 'array',
        'is_active' => 'boolean',
        'price_monthly' => 'float',
    ];
}