<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DiscountCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'type',
        'value',
        'min_order_amount',
        'expires_at',
        'is_active',
        'times_used',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'float',
            'min_order_amount' => 'float',
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
            'times_used' => 'integer',
        ];
    }

    /**
     * Oblicza kwotę rabatu na podstawie wartości koszyka.
     */
    public function calculateDiscount(float $subtotal): float
    {
        if ($this->type === 'percent') {
            return round(($subtotal * ($this->value / 100)), 2);
        }

        return min((float)$this->value, $subtotal);
    }
}