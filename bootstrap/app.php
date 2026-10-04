<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // 🔓 Ustawienie dozwolonych hostów dla sesji i zapytań cross-origin (CORS)
        $middleware->trustHosts(at: ['localhost', '127.0.0.1', '.*\.localhost', '.*\.ingastro\.pl']);

        // 🛡️ Włączamy obsługę domen stanowych (Sanctum / Inertia) dla subdomen .localhost
        $middleware->statefulApi();

        // 💳 Wykluczenie webhooków Stripe oraz płatności z weryfikacji CSRF
        $middleware->validateCsrfTokens(except: [
            'stripe/*',
            'stripe/webhook',
            'api/webhooks/stripe',
            'payment/webhook',
            'payment/payu/webhook',
        ]);

        // Rejestracja aliasów middleware dla ról, uprawnień, modułów oraz planów subskrypcji
        $middleware->alias([
            'role'             => \App\Http\Middleware\RoleMiddleware::class,
            'permission'       => \App\Http\Middleware\CheckPermission::class,
            'ecommerce.active' => \App\Http\Middleware\EnsureEcommerceIsActive::class,
            'feature'          => \App\Http\Middleware\EnsureFeatureEnabled::class,
        ]);

        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();