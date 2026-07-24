<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Obsługuje nadchodzące żądanie HTTP.
     * * Wykorzystujemy operator splat (...$roles), aby móc przekazać 
     * do middleware wiele ról naraz, np. 'role:admin,manager'
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        // 1. Sprawdzamy, czy użytkownik w ogóle jest zalogowany
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        // 2. Pobieramy rolę zalogowanego użytkownika
        $userRole = auth()->user()->role;

        // 3. Sprawdzamy, czy rola użytkownika znajduje się na liście dozwolonych ról
        if (in_array($userRole, $roles)) {
            return $next($request); // Wpuszczamy dalej
        }

        // 4. Jeśli brak uprawnień - odbijamy żądanie z kodem 403 (Forbidden)
        // W Inertii możemy przekierować na bezpieczną stronę z błędem flash
        return redirect()->route('shop.index')->withErrors([
            'error' => 'Dostęp zabroniony! Twój profil pracowniczy nie ma uprawnień do tego modułu.'
        ]);
    }
}