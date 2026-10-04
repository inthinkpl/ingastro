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

foreach ($centralDomains as $domain) {
    Route::domain($domain)->group(function () {
        
        // 🏠 Strona Główna SaaS (Landing Page)
        Route::get('/', function () {
            return Inertia::render('Central/Home');
        })->name('central.home');

        // 📝 Publiczny Formularz Rejestracji Pizzerii
        Route::get('/register-restaurant', [TenantRegisterController::class, 'create'])->name('central.register');
        Route::post('/register-restaurant', [TenantRegisterController::class, 'store'])->name('central.register.store');

        // 💳 Przekierowanie do Stripe Checkout (z 14-dniowym trialem)
        Route::get('/subscription/checkout/{plan}', [SubscriptionController::class, 'checkout'])
            ->middleware(['auth'])
            ->name('subscription.checkout');

        // 💳 Domyślny Webhook Laravel Cashier / Stripe
        Route::post('/stripe/webhook', [CentralSubscriptionController::class, 'handleStripeWebhook'])
            ->name('central.webhooks.stripe');

        // 👑 Panel Centralny Super Admina (Central HQ)
        Route::prefix('super-admin')->name('central.admin.')->group(function () {
            // Podgląd listy klientów, statystyk MRR i planów
            Route::get('/', [CentralAdminController::class, 'index'])->name('dashboard');

            // Ręczne tworzenie nowego lokalu (tenanta)
            Route::post('/tenants', [CentralAdminController::class, 'storeTenant'])->name('tenants.store');

            // Aktualizacja planu i statusu subskrypcji
            Route::put('/tenants/{tenant}/subscription', [CentralAdminController::class, 'updateTenantSubscription'])->name('tenants.subscription.update');
        });

    });
}