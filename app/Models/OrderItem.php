<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderItem extends Model
{
    protected $fillable = ['order_id', 'product_variant_id', 'quantity', 'subtotal'];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    // Relacja: Pozycja zamówienia może mieć wiele modyfikacji (np. ta konkretna pizza ma dodatkowy ser)
    public function modifiers(): HasMany
    {
        return $this->hasMany(OrderModifier::class);
    }
}
