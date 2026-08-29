<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ProductVariant extends Model
{
    protected $fillable = [
        'product_id', 
        'size_name', 
        'price', 
        'is_active' // 👈 Dodana kolumna statusu aktywności
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'price' => 'float',
    ];

    // Relacja odwrotna: Wariant należy do jednego produktu
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function ingredients(): BelongsToMany
    {
        return $this->belongsToMany(Ingredient::class, 'ingredient_variant')
                    ->withPivot('amount_needed') // Kolumna z wagą/ilością
                    ->withTimestamps();
    }
}