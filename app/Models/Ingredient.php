<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Ingredient extends Model
{
    protected $fillable = [
        'name', 
        'stock_main', 
        'stock_local', 
        'min_stock_local', 
        'unit', 
        'purchase_price'
    ];

    // Relacja wiele-do-wielu: Składnik należy do wielu wariantów produktów
    public function variants(): BelongsToMany
    {
        return $this->belongsToMany(ProductVariant::class, 'ingredient_variant')
                    ->withPivot('amount_needed')
                    ->withTimestamps();
    }
}