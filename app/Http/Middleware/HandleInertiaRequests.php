<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use App\Models\SystemSetting; 
use App\Models\RolePermission;
use App\Models\WorkShift; // 🔥 Zaimportowany model zmian RCP

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Definiuje dane, które są automatycznie współdzielone z każdym komponentem Vue.
     */
    public function share(Request $request): array
    {
        // 1. DYNAMICZNY ODCZYT: Pobieramy dane z bazy za pomocą Twojej metody EAV
        $restaurantName    = SystemSetting::get('restaurant_name', 'Pizzeria Savona');
        $restaurantPhone   = SystemSetting::get('restaurant_phone', '');
        $restaurantAddress = SystemSetting::get('restaurant_address', '');

        // 2. GLOBALNE NADPISANIE: Zmieniamy nazwę aplikacji w konfiguracji Laravel w locie.
        config(['app.name' => $restaurantName]);

        // 3. 🔐 DYNAMICZNA MACIERZ UPRAWNIEŃ ORAZ STATUS RCP ZALOGOWANEGO UŻYTKOWNIKA
        $user = $request->user();
        $permissions = [];
        $activeShift = null;

        if ($user) {
            // Pobranie aktywnej zmiany roboczej (status 'working' lub 'on_break')
            $activeShift = WorkShift::where('user_id', $user->id)
                ->whereIn('status', ['working', 'on_break'])
                ->latest('clock_in')
                ->first();

            if ($user->role === 'admin') {
                // Administrator ma gwiazdkę (*) – pełny dostęp do wszystkich modułów
                $permissions = ['*'];
            } else {
                // Dla pozostałych ról pobieramy ich aktywne uprawnienia z bazy
                $permissions = RolePermission::where('role', $user->role)
                    ->pluck('permission')
                    ->toArray();
            }
        }

        return array_merge(parent::share($request), [
            // 🔥 Wstrzyknięcie danych autoryzacji z rolą, listą uprawnień i aktywną zmianą RCP
            'auth' => [
                'user'         => $user,
                'role'         => $user?->role,
                'permissions'  => $permissions,
                'active_shift' => $activeShift, // 👈 Przekazanie aktywnej zmiany do Vue
            ],

            // Wstrzyknięcie do globalnych propsów Inertii pod layouty i paski menu
            'restaurant' => [
                'name'    => $restaurantName,
                'address' => $restaurantAddress,
                'phone'   => $restaurantPhone,
            ],

            'errors' => fn () => $request->session()->get('errors', [], function ($value) {
                return (object) $value;
            }),

            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error'   => fn () => $request->session()->get('error'),
            ],
        ]);
    }
}