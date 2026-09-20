<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CentralSubscriptionController extends Controller
{
    /**
     * Inicjalizacja płatności Stripe Checkout dla subskrypcji
     */
    public function checkoutStripe(Request $request)
    {
        $validated = $request->validate([
            'plan_id' => 'required|exists:plans,id',
        ]);

        $tenant = tenant();
        $plan = Plan::findOrFail($validated['plan_id']);

        \Stripe\Stripe::setApiKey(config('services.stripe.secret'));

        // Tworzenie lub pobranie ID klienta w Stripe
        if (!$tenant->stripe_customer_id) {
            $customer = \Stripe\Customer::create([
                'email' => $request->user()->email,
                'name' => SystemSetting::get('restaurant_name', $tenant->id),
                'metadata' => ['tenant_id' => $tenant->id],
            ]);
            $tenant->update(['stripe_customer_id' => $customer->id]);
        }

        // Utworzenie sesji Checkout w Stripe
        $session = \Stripe\Checkout\Session::create([
            'customer' => $tenant->stripe_customer_id,
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'pln',
                    'product_data' => [
                        'name' => 'Subskrypcja Savona SaaS: ' . $plan->name,
                    ],
                    'unit_amount' => (int) ($plan->price_monthly * 100),
                    'recurring' => ['interval' => 'month'],
                ],
                'quantity' => 1,
            ]],
            'mode' => 'subscription',
            'success_url' => route('admin.settings.edit') . '?subscription=success',
            'cancel_url' => route('admin.settings.edit') . '?subscription=cancel',
            'metadata' => [
                'tenant_id' => $tenant->id,
                'plan_id' => $plan->id,
            ],
        ]);

        return response()->json(['url' => $session->url]);
    }

    /**
     * Webhook odbierający zdarzenia płatności od Stripe
     */
    public function handleStripeWebhook(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $endpointSecret = config('services.stripe.webhook_secret');

        try {
            $event = \Stripe\Webhook::constructEvent($payload, $sigHeader, $endpointSecret);
        } catch (\Exception $e) {
            Log::error('Stripe Webhook Error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 400);
        }

        switch ($event->type) {
            case 'checkout.session.completed':
                $session = $event->data->object;
                $tenantId = $session->metadata->tenant_id ?? null;
                $planId = $session->metadata->plan_id ?? null;

                if ($tenantId && $planId) {
                    $tenant = Tenant::find($tenantId);
                    if ($tenant) {
                        $tenant->update([
                            'plan_id' => $planId,
                            'subscription_status' => 'active',
                            'subscription_ends_at' => now()->addMonth(),
                            'stripe_subscription_id' => $session->subscription,
                        ]);
                    }
                }
                break;

            case 'invoice.payment_failed':
                $invoice = $event->data->object;
                $customer = $invoice->customer;
                
                $tenant = Tenant::where('stripe_customer_id', $customer)->first();
                if ($tenant) {
                    $tenant->update(['subscription_status' => 'expired']);
                }
                break;
        }

        return response()->json(['status' => 'success']);
    }
}