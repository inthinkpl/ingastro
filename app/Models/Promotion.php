<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Promotion extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',               // 'amount_discount', 'percent_discount', 'free_product'
        'discount_target',    // 'cart_total', 'cheapest_item'
        'value',
        'min_order_amount',
        'min_quantity',
        'required_category',  // 'pizza', 'napoj', 'all'
        'required_size_name', // 'Duża', 'Średnia', 'Dowolny'
        'reward_variant_id',
        'is_active',
    ];

    protected $casts = [
        'value'            => 'float',
        'min_order_amount' => 'float',
        'min_quantity'     => 'integer',
        'is_active'        => 'boolean',
    ];

    /**
     * Relacja do wariantu produktu przyznawanego jako gratis.
     */
    public function rewardVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'reward_variant_id');
    }
}