<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    /**
     * 🛡️ Wymuszenie połączenia z bazą centralną dla modelu Planów!
     */
    protected $connection = 'mysql';

    protected $guarded = [];

    protected $casts = [
        'features'      => 'array',
        'is_active'     => 'boolean',
        'price_monthly' => 'float',
    ];

    /**
     * Relacja do lokali w bazie centralnej
     */
    public function tenants()
    {
        return $this->hasMany(Tenant::class);
    }
}