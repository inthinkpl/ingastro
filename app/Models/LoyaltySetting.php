<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoyaltySetting extends Model
{
    protected $fillable = [
        'enabled',
        'earn_rate',
        'point_value',
        'min_points_to_redeem',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'earn_rate' => 'decimal:2',
        'point_value' => 'decimal:2',
        'min_points_to_redeem' => 'decimal:2',
    ];
}