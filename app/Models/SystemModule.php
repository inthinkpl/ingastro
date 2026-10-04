<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemModule extends Model
{
    /**
     * 🛡 Wymuszenie połączenia z bazą centralną dla modelu modułów!
     */
    protected $connection = 'mysql';

    protected $table = 'system_modules';

    protected $fillable = [
        'key',
        'name',
        'description',
        'price_monthly',
        'is_active',
        'requires',
        'sort_order',
    ];

    protected $casts = [
        'price_monthly' => 'float',
        'is_active'     => 'boolean',
        'requires'      => 'array',
    ];
}