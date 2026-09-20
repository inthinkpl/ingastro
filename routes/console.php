<?php

use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| CONSOLE ROUTES & SCHEDULER (Laravel 11)
|--------------------------------------------------------------------------
*/

// Uruchamianie weryfikacji subskrypcji codziennie o północy
Schedule::command('app:check-subscriptions')
    ->daily()
    ->onOneServer()
    ->runInBackground();