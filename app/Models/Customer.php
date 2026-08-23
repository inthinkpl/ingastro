<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    protected $fillable = [
        'phone',
        'name',
        'email',
        'points_balance',
        'total_spent',
        'total_orders',
        'default_address',
        'last_order_at',
    ];

    protected $casts = [
        'points_balance' => 'decimal:2',
        'total_spent' => 'decimal:2',
        'last_order_at' => 'datetime',
    ];

    public function transactions(): HasMany
    {
        return $this->hasMany(LoyaltyTransaction::class);
    }
}