<?php

namespace App\Models;

use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase, HasDomains;

    /**
     * Rzutowanie typów kolumn (automatyczna konwersja pola daty na instancję Carbon/DateTime)
     */
    protected $casts = [
        'subscription_ends_at' => 'datetime',
    ];

    /**
     * Wprowadź tutaj dodatkowe kolumny, które chcesz przechowywać w bazie centralnej dla pizzerii.
     */
    public static function getCustomColumns(): array
    {
        return [
            'id',
            'plan_id',
            'subscription_ends_at',
            'subscription_status',
            'stripe_customer_id',
            'stripe_subscription_id',
            'p24_recurring_token',
            'created_at',
            'updated_at',
        ];
    }

    /**
     * Relacja do planu subskrypcyjnego w bazie centralnej.
     */
    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * Sprawdza, czy tenant ma aktywną subskrypcję i dostęp do podanej funkcji.
     */
    public function hasFeature(string $feature): bool
    {
        if (!$this->plan || $this->subscription_status !== 'active') {
            return false;
        }

        return in_array($feature, $this->plan->features ?? [], true);
    }

    /**
     * Zwraca URL do pliku ze storage tenanta serwowanego przez /tenantasset/
     */
    public static function asset(string $path): string
    {
        $tenant = tenant();
        if (!$tenant) {
            return asset($path);
        }

        return url("/tenantasset/{$path}");
    }
}