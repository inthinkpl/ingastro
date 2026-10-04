<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\SystemModule;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CentralSubscriptionController extends Controller
{
    /**
     * Inicjalizacja płatności Stripe Checkout dla skomponowanego zestawu modułów
     */
    public function checkoutStripe(Request $request)
    {
        $validated = $request->validate([
            'modules'   => 'required|array|min:1',
            'modules.*' => 'required|string|exists:mysql.system_modules,key',
        ]);

        $stripeSecret = config('services.stripe.secret');

        // 🛡️ Weryfikacja poprawności klucza Stripe Secret w konfiguracji przed połączeniem
        if (empty($stripeSecret) || !str_starts_with($stripeSecret, 'sk_')) {
            Log::error('Błąd Stripe API: Nieprawidłowy lub brakujący STRIPE_SECRET w pliku .env');
            return response()->json([
                'message' => 'Błąd konfiguracji płatności: Klucz Stripe Secret Key jest nieprawidłowy lub nie został załadowany z pliku .env.'
            ], 500);
        }

        $tenant = tenant();

        // 🛡️ Pobranie wybranych aktywnych modułów z bazy centralnej (mysql)
        $selectedModules = SystemModule::on('mysql')
            ->whereIn('key', $validated['modules'])
            ->where('is_active', true)
            ->get();

        if ($selectedModules->isEmpty()) {
            return response()->json([
                'message' => 'Nie znaleziono aktywnych modułów wybranych do płatności.'
            ], 400);
        }

        \Stripe\Stripe::setApiKey($stripeSecret);

        try {
            // Tworzenie lub pobranie ID klienta w Stripe
            if (!$tenant->stripe_customer_id) {
                $restaurantName = class_exists(SystemSetting::class) 
                    ? SystemSetting::get('restaurant_name', $tenant->id) 
                    : $tenant->id;

                $customer = \Stripe\Customer::create([
                    'email'    => $request->user()?->email ?? 'admin@' . $tenant->id . '.com',
                    'name'     => $restaurantName,
                    'metadata' => ['tenant_id' => $tenant->id],
                ]);

                // Aktualizacja identyfikatora Stripe w bazie centralnej
                Tenant::on('mysql')->where('id', $tenant->id)->update([
                    'stripe_customer_id' => $customer->id
                ]);
                $tenant->stripe_customer_id = $customer->id;
            }

            // Tworzenie pozycji (line_items) dla każdego wybranego modułu
            $lineItems = [];
            foreach ($selectedModules as $module) {
                $lineItems[] = [
                    'price_data' => [
                        'currency'     => 'pln',
                        'product_data' => [
                            'name'        => 'Moduł InGastro: ' . $module->name,
                            'description' => $module->description,
                        ],
                        'unit_amount'  => (int) (round($module->price_monthly, 2) * 100),
                        'recurring'    => ['interval' => 'month'],
                    ],
                    'quantity'   => 1,
                ];
            }

            // Utworzenie sesji Checkout w Stripe z 14-dniowym trialem
            $session = \Stripe\Checkout\Session::create([
                'customer'             => $tenant->stripe_customer_id,
                'payment_method_types' => ['card'],
                'line_items'           => $lineItems,
                'mode'                 => 'subscription',
                'subscription_data'    => [
                    'trial_period_days' => 14,
                ],
                'success_url'          => route('admin.settings.edit') . '?subscription=success',
                'cancel_url'           => route('admin.settings.edit') . '?subscription=cancel',
                'metadata'             => [
                    'tenant_id' => $tenant->id,
                    'modules'   => json_encode($selectedModules->pluck('key')->toArray()),
                ],
            ]);

            return response()->json(['url' => $session->url]);

        } catch (\Stripe\Exception\ApiErrorException $e) {
            Log::error('Stripe API Error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Błąd Stripe API: ' . $e->getMessage()
            ], 400);
        } catch (\Throwable $e) {
            Log::error('Inicjalizacja Checkout Błąd: ' . $e->getMessage());
            return response()->json([
                'message' => 'Wystąpił błąd podczas generowania sesji płatności: ' . $e->getMessage()
            ], 500);
        }
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
                $session  = $event->data->object;
                $tenantId = $session->metadata->tenant_id ?? null;
                $rawModules = $session->metadata->modules ?? null;

                if ($tenantId) {
                    $tenant = Tenant::on('mysql')->find($tenantId);
                    if ($tenant) {
                        $tenantData = [
                            'subscription_status'    => 'trialing',
                            'subscription_ends_at'   => now()->addDays(14),
                            'stripe_subscription_id' => $session->subscription,
                        ];

                        // Jeśli przekazano listę modułów w metadata
                        if ($rawModules) {
                            $tenantData['enabled_features'] = $rawModules;
                        }

                        $tenant->update($tenantData);
                    }
                }
                break;

            case 'customer.subscription.updated':
                $subscription = $event->data->object;
                $tenant = Tenant::on('mysql')->where('stripe_subscription_id', $subscription->id)->first();

                if ($tenant) {
                    $newStatus = $subscription->status === 'active' ? 'active' : $subscription->status;
                    $tenant->update([
                        'subscription_status'  => $newStatus,
                        'subscription_ends_at' => \Carbon\Carbon::createFromTimestamp($subscription->current_period_end),
                    ]);
                }
                break;

            case 'invoice.payment_failed':
            case 'customer.subscription.deleted':
                $invoiceOrSub = $event->data->object;
                $customerId   = $invoiceOrSub->customer;
                
                $tenant = Tenant::on('mysql')->where('stripe_customer_id', $customerId)->first();
                if ($tenant) {
                    $tenant->update(['subscription_status' => 'expired']);
                }
                break;
        }

        return response()->json(['status' => 'success']);
    }
}