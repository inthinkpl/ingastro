<?php

namespace App\Services\Payment\drivers;

use App\Models\Order;
use App\Services\Payment\PaymentGatewayInterface;
use Illuminate\Http\Request;

class SimulationDriver implements PaymentGatewayInterface
{
    public function purchase(Order $order): string
    {
        // Generujemy unikalny token transakcji i zapisujemy w zamówieniu
        $order->update([
            'transaction_id' => 'SIM-' . strtoupper(uniqid())
        ]);

        // Zwracamy adres URL do naszego lokalnego symulatora BLIK
        return route('payment.simulation.view', ['order' => $order->id]);
    }

    public function verify(Request $request): bool
    {
        // W symulacji autoryzację robimy bezpośrednio na ekranie kodu BLIK
        return true;
    }
}