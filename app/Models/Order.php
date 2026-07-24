<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
    'tracking_token',
    'user_id', 
    'type', 
    'status', 
    'payment_method', 
    'payment_status', 
    'delivery_address', 
    'total_price', 
    'delivery_zone_id',
    'lat',
    'lng',
    'driver_id',
    'route_sequence',];

    // Relacja: Zamówienie ma wiele pozycji (np. 2x Margherita, 1x Cola)
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function driver()
    {
    return $this->belongsTo(User::class, 'driver_id');
    }

    public function deliveryZone()
    {
    return $this->belongsTo(DeliveryZone::class);
    }
}