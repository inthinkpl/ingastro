<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionInvoice extends Model
{
    /**
     * 🛡️️ Wymuszenie połączenia z bazą centralną dla modeli faktur subskrypcyjnych!
     */
    protected $connection = 'mysql';

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
        'paid_at'      => 'datetime',
        'amount_net'   => 'float',
        'amount_gross' => 'float',
    ];

    /**
     * Relacja do Tenanta w bazie centralnej
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }
}