<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use App\Models\SystemSetting; 
use App\Models\RolePermission;
use App\Models\WorkShift;

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
        $restaurantName    = 'Pizzeria Savona';
        $restaurantPhone   = '';
        $restaurantAddress = '';

        // 🛡️ Bezpieczne pobieranie ustawień lokalu (chroni przed błędami podczas auth/login)
        try {
            if (function_exists('tenant') && tenant()) {
                $restaurantName    = SystemSetting::get('restaurant_name', 'Pizzeria Savona');
                $restaurantPhone   = SystemSetting::get('restaurant_phone', '');
                $restaurantAddress = SystemSetting::get('restaurant_address', '');
            }
        } catch (\Throwable $e) {
            // W razie braku tabeli lub błędu bazy używamy wartości domyślnych
        }

        config(['app.name' => $restaurantName]);

        // 💳 PEŁNE DANE SUBSKRYPCJI TENANTA
        $tenantFeatures = [];
        $subscriptionInfo = [
            'plan_name' => 'Brak Planu',
            'ends_at'   => 'Bezterminowo',
            'status'    => 'expired',
        ];

        try {
            $tenant = function_exists('tenant') ? tenant() : null;
            if ($tenant) {
                $tenant->loadMissing('plan');
                if ($tenant->subscription_status === 'active' && $tenant->plan) {
                    $tenantFeatures = $tenant->plan->features ?? [];
                }

                $endsAt = 'Bezterminowo';
                if ($tenant->subscription_ends_at) {
                    $endsAt = is_string($tenant->subscription_ends_at)
                        ? $tenant->subscription_ends_at
                        : $tenant->subscription_ends_at->format('Y-m-d');
                }

                $subscriptionInfo = [
                    'plan_name' => $tenant->plan?->name ?? 'Brak planu',
                    'ends_at'   => $endsAt,
                    'status'    => $tenant->subscription_status ?? 'expired',
                ];
            }
        } catch (\Throwable $e) {
            // Ignorujemy błąd pobierania subskrypcji na czas zapytania /login
        }

        $user = $request->user();
        $permissions = [];
        $activeShift = null;

        if ($user) {
            try {
                $activeShift = WorkShift::where('user_id', $user->id)
                    ->whereIn('status', ['working', 'on_break'])
                    ->latest('clock_in')
                    ->first();

                if (isset($user->role) && $user->role === 'admin') {
                    $permissions = ['*'];
                } else {
                    $permissions = RolePermission::where('role', $user->role ?? '')
                        ->pluck('permission')
                        ->toArray();
                }
            } catch (\Throwable $e) {
                $permissions = [];
            }
        }

        return array_merge(parent::share($request), [
            'auth' => [
                'user'            => $user,
                'role'            => $user?->role,
                'permissions'     => $permissions,
                'active_shift'    => $activeShift,
                'tenant_features' => $tenantFeatures,
            ],
            // 🍕 DANE LOKALU ORAZ SUBSKRYPCJI
            'restaurant' => [
                'name'         => $restaurantName,
                'address'      => $restaurantAddress,
                'phone'        => $restaurantPhone,
                'subscription' => $subscriptionInfo,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error'   => fn () => $request->session()->get('error'),
            ],
        ]);
    }
}