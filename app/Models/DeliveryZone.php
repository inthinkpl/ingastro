<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryZone extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'price',
        'min_order_price',
        'free_delivery_from',
        'max_distance_km',
        'default_driver_id',
        'color_code',
        'is_active',
    ];

    /**
     * Domyślny kierowca przypisany do strefy
     */
    public function defaultDriver()
    {
        return $this->belongsTo(User::class, 'default_driver_id');
    }

    /**
     * Zamówienia przypisane do strefy
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}