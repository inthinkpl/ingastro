<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'name',
        'category',
        'description', // <-- DODAJ TO
        'image_path',  // <-- DODAJ TO
        'is_active',
    ];

    // Relacja: Produkt ma wiele wariantów
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }
}