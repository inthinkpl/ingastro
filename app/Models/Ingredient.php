<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Ingredient extends Model
{
    protected $fillable = ['name', 'stock_quantity', 'unit', 'purchase_price', 'min_limit'];

    // Relacja wiele-do-wielu: Składnik należy do wielu wariantów produktów
    public function variants()
    {
        return $this->belongsToMany(ProductVariant::class, 'ingredient_variant')
                    ->withPivot('amount_needed')
                    ->withTimestamps();
    }
}