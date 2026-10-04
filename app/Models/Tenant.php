<?php

namespace App\Models;

use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Laravel\Cashier\Billable;
use Laravel\Cashier\Subscription;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use Billable;
    use HasDatabase, HasDomains;

    /**
     * 🛡️ Wymuszenie połączenia z bazą centralną dla modelu Tenanta i Cashiera!
     */
    protected $connection = 'mysql';

    /**
     * Rzutowanie typów kolumn
     */
    protected $casts = [
        'subscription_ends_at' => 'datetime',
    ];

    /**
     * Wprowadź tutaj dodatkowe kolumny przechowywane w bazie centralnej.
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
        return $this->belongsTo(Plan::class, 'plan_id');
    }

    /**
     * 📄 Relacja do faktur subskrypcyjnych w bazie centralnej
     */
    public function invoices()
    {
        return $this->hasMany(SubscriptionInvoice::class, 'tenant_id')
                    ->setConnection('mysql')
                    ->orderBy('created_at', 'desc');
    }

    /**
     * 🛡️ Nadpisanie relacji Cashiera dla subskrypcji — zawsze wymuszamy bazę centralną (mysql)
     */
    public function subscriptions()
    {
        return $this->hasMany(Subscription::class, $this->getForeignKey())
                    ->setConnection('mysql')
                    ->orderBy('created_at', 'desc');
    }

    /**
     * 🛡️ Nadpisanie domyślnego połączenia Cashiera
     */
    public function getConnectionName()
    {
        return 'mysql';
    }

    /**
     * Sprawdza, czy tenant ma dostęp do podanej funkcji (wspiera okres próbny i datę wygaśnięcia).
     */
    public function hasFeature(string $feature): bool
    {
        $status = $this->subscription_status ?? 'expired';
        $isOnTrial = in_array($status, ['on_trial', 'trialing'], true);

        // Jeśli subskrypcja nie jest aktywna ani w trakcie okresu próbnego -> brak dostępu
        if ($status !== 'active' && !$isOnTrial) {
            return false;
        }

        // 🛡️ W trakcie okresu próbnego (trial) udostępniamy pełny pakiet funkcji
        if ($isOnTrial) {
            return true;
        }

        // 🛡️ Automatyczne dociągnięcie planu, jeśli relacja nie została wcześniej załadowana
        $this->loadMissing('plan');

        if (!$this->plan) {
            return false;
        }

        $features = $this->plan->features ?? [];

        if (is_string($features)) {
            $features = json_decode($features, true) ?? [];
        }

        return is_array($features) && in_array($feature, $features, true);
    }

    /**
     * Zwraca URL do pliku ze storage tenanta
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