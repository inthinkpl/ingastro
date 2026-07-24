<?php

namespace App\Services\Payment;

use App\Models\Order;
use Illuminate\Http\Request;

interface PaymentGatewayInterface
{
    /**
     * Inicjalizuje płatność i zwraca URL, na który należy przekierować klienta (zewnętrzny bank lub nasz BLIK).
     */
    public function purchase(Order $order): string;

    /**
     * Weryfikuje powiadomienie (webhook) z banku i oznacza zamówienie jako opłacone.
     */
    public function verify(Request $request): bool;
}