<?php

namespace App\Services\Payment\drivers;

use App\Models\Order;
use App\Services\Payment\PaymentGatewayInterface;
use App\Models\SystemSetting;
use Illuminate\Http\Request;

class StripeDriver implements PaymentGatewayInterface
{
    public function purchase(Order $order): string
    {
        // Pobierasz sekretny klucz API wstrzyknięty z ustawień administratora
        $secretKey = SystemSetting::get('stripe_secret_key');

        // W tym miejscu wywołujesz oficjalną bibliotekę Stripe:
        // \Stripe\Stripe::setApiKey($secretKey);
        // $session = \Stripe\Checkout\Session::create([...]);
        // return $session->url;

        return 'https://checkout.stripe.com/mock-pay-url-for-order-' . $order->id;
    }

    public function verify(Request $request): bool
    {
        // Tutaj weryfikujesz podpis cyfrowy webhooka ze Stripe
        return true;
    }
}