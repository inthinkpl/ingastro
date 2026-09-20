<?php

use App\Http\Controllers\Central\TenantRegisterController;
use App\Http\Controllers\Central\CentralAdminController;
use App\Http\Controllers\Central\CentralSubscriptionController;
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

        // 💳 Webhook Stripe (Wyprowadzony poza grupę super-admin)
        Route::post('/api/webhooks/stripe', [CentralSubscriptionController::class, 'handleStripeWebhook'])->name('central.webhooks.stripe');

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