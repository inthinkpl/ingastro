<?php

namespace App\Services\Payment;

use App\Models\SystemSetting;
use Exception;

class PaymentFactory
{
    /**
     * Dynamicznie tworzy instancję bramki płatniczej na podstawie konfiguracji z bazy.
     */
    public static function make(): PaymentGatewayInterface
    {
        // Pobieramy z bazy klucz: 'simulation', 'stripe' lub 'payu'
        $driver = SystemSetting::get('payment_gateway', 'simulation');

        return match ($driver) {
            'simulation' => new drivers\SimulationDriver(),
            'stripe'     => new drivers\StripeDriver(),
            'payu'       => new drivers\PayUDriver(),
            default      => throw new Exception("Niewspierany sterownik płatności: {$driver}"),
        };
    }
}