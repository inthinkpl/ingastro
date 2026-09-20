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

    public function share(Request $request): array
    {
        $restaurantName    = 'InGastro SaaS';
        $restaurantPhone   = '';
        $restaurantAddress = '';
        
        $tenantFeatures = [];
        $subscriptionInfo = [
            'plan_name' => 'Brak Planu',
            'ends_at'   => 'Bezterminowo',
            'status'    => 'expired',
        ];

        $permissions = [];
        $activeShift = null;
        $user = $request->user();

        // 🛡️ WYKONUJEMY TYLKO NA SUBDOMENIE (TENANT)
        if (tenant()) {
            
            // Pobieranie ustawień lokalu
            try {
                $restaurantName    = SystemSetting::get('restaurant_name', 'Pizzeria Savona');
                $restaurantPhone   = SystemSetting::get('restaurant_phone', '');
                $restaurantAddress = SystemSetting::get('restaurant_address', '');
            } catch (\Throwable $e) {}

            // Pobieranie danych subskrypcji
            try {
                $tenant = tenant();
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
            } catch (\Throwable $e) {}

            // Pobieranie uprawnień i zmian (shift) tylko w bazie tenanta!
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
        }
        
        // Aktualizacja nazwy aplikacji (dla obu trybów)
        config(['app.name' => $restaurantName]);

        return array_merge(parent::share($request), [
            'auth' => [
                'user'            => $user,
                'role'            => $user?->role,
                'permissions'     => $permissions,
                'active_shift'    => $activeShift,
                'tenant_features' => $tenantFeatures,
            ],
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