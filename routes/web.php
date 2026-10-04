<?php

use App\Http\Controllers\Central\TenantRegisterController;
use App\Http\Controllers\Central\CentralAdminController;
use App\Http\Controllers\Central\CentralSubscriptionController;
use App\Http\Controllers\SubscriptionController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| CENTRAL ROUTES (Ograniczone do domen centralnych z config/tenancy.php)
|--------------------------------------------------------------------------
*/

$centralDomains = config('tenancy.central_domains', ['localhost', '127.0.0.1']);

foreach ($centralDomains as $index => $domain) {
    Route::domain($domain)->group(function () use ($index) {
        
        // 🏠 Strona Główna SaaS (Landing Page)
        $homeRoute = Route::get('/', function () {
            return Inertia::render('Central/Home');
        });
        if ($index === 0) $homeRoute->name('central.home');

        // 📝 Publiczny Formularz Rejestracji Lokalu Gastronomicznego
        $registerGet = Route::get('/register-restaurant', [TenantRegisterController::class, 'create']);
        $registerPost = Route::post('/register-restaurant', [TenantRegisterController::class, 'store']);
        if ($index === 0) {
            $registerGet->name('central.register');
            $registerPost->name('central.register.store');
        }

        // 💳 Przekierowanie do Stripe Checkout dla Modułów
        $checkoutRoute = Route::post('/subscription/checkout', [CentralSubscriptionController::class, 'checkoutStripe'])
            ->middleware(['auth']);
        if ($index === 0) $checkoutRoute->name('subscription.checkout');

        // 💳 Domyślny Webhook Laravel Cashier / Stripe
        $webhookRoute = Route::post('/stripe/webhook', [CentralSubscriptionController::class, 'handleStripeWebhook']);
        if ($index === 0) $webhookRoute->name('central.webhooks.stripe');

        // 👑 Panel Centralny Super Admina (Central HQ)
        Route::prefix('super-admin')->group(function () use ($index) {
            $dashRoute = Route::get('/', [CentralAdminController::class, 'index']);
            $storeRoute = Route::post('/tenants', [CentralAdminController::class, 'storeTenant']);
            $updateRoute = Route::put('/tenants/{tenant}/subscription', [CentralAdminController::class, 'updateTenantSubscription']);

            if ($index === 0) {
                $dashRoute->name('central.admin.dashboard');
                $storeRoute->name('central.admin.tenants.store');
                $updateRoute->name('central.admin.tenants.subscription.update');
            }
        });

    });
}