<?php

namespace App\Providers;

use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL; // 👈 1. Dodaj ten import
use App\Events\OrderStatusUpdated;
use App\Listeners\SendOrderStatusPushListener;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // 👇 2. Wymuś HTTPS na środowisku produkcyjnym, aby uniknąć błędów Mixed Content
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        Vite::prefetch(concurrency: 3);

        Event::listen(
            OrderStatusUpdated::class,
            SendOrderStatusPushListener::class
        );
    }
}