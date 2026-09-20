<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionInvoice extends Model
{
    protected $fillable = [
        'tenant_id',
        'number',
        'plan_name',
        'amount_net',
        'amount_gross',
        'status',
        'paid_at',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'amount_net' => 'float',
        'amount_gross' => 'float',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}