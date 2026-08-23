<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\SystemSetting;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class EnsureEcommerceIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Pobieramy surową wartość klucza is_ecommerce_active z tabeli system_settings
        $settingValue = SystemSetting::get('is_ecommerce_active', '1');

        // 2. Precyzyjne parsowanie boolean (pobiera zarowno "0", "false", 0 jak i "1", "true", 1)
        $isActive = filter_var($settingValue, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        
        // Jeśli klucz nie istniał lub nie dało się go sparsować, domyślnie przyjmujemy true
        if ($isActive === null) {
            $isActive = true;
        }

        // 3. Jeśli sklep jest AKTYWNY -> Przepuszczamy wszystkich klientów
        if ($isActive) {
            return $next($request);
        }

        // 4. Jeśli sklep jest WYŁĄCZONY:
        // Sprawdzamy czy użytkownik jest zalogowany I czy ma rolę 'admin' lub 'manager'
        $user = auth()->user();
        
        if ($user && in_array($user->role, ['admin', 'manager'])) {
            // Admin/Manager przechodzi z żółtym banerem podglądu
            Inertia::share('is_preview_mode', true);
            return $next($request);
        }

        // 5. Dla karty incognito, niezalogowanych oraz zwykłych klientów -> STRONA PRZERWY TECHNICZNEJ
        return Inertia::render('Shop/Maintenance', [
            'message' => 'Nasz sklep internetowy jest obecnie niedostępny. Przepraszamy za utrudnienia i zapraszamy do składania zamówień stacjonarnie oraz telefonicznie!'
        ])->toResponse($request)->setStatusCode(503);
    }
}