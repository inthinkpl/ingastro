<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use App\Models\SystemSetting; 
use App\Models\RolePermission;
use App\Models\WorkShift;
use App\Models\Plan;
use Illuminate\Support\Carbon;

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
            'plan_id'   => null,
            'plan_name' => 'Brak Planu',
            'ends_at'   => 'Bezterminowo',
            'status'    => 'expired',
        ];

        $permissions = [];
        $activeShift = null;
        $user = $request->user();

        // 🛡️ WYKONUJEMY TYLKO W KONTEKŚCIE TENANTA (SUBDOMENA)
        if (tenant()) {
            
            // Pobieranie ustawień lokalu
            try {
                $restaurantName    = SystemSetting::get('restaurant_name', 'ingastro');
                $restaurantPhone   = SystemSetting::get('restaurant_phone', '');
                $restaurantAddress = SystemSetting::get('restaurant_address', '');
            } catch (\Throwable $e) {}

            // Pobieranie danych subskrypcji i modułów z bazy centralnej
            try {
                $tenant = tenant();
                if ($tenant) {
                    $status = $tenant->subscription_status ?? 'expired';
                    
                    // Weryfikacja daty wygaśnięcia
                    $endsAtRaw = $tenant->subscription_ends_at ?? $tenant->trial_ends_at ?? null;
                    $endsAt = 'Bezterminowo';

                    if ($endsAtRaw) {
                        $endsAtCarbon = $endsAtRaw instanceof \DateTimeInterface 
                            ? Carbon::instance($endsAtRaw) 
                            : Carbon::parse($endsAtRaw);

                        $endsAt = $endsAtCarbon->format('Y-m-d');

                        // Jeśli data wygaśnięcia minęła, zmień status na expired
                        if ($endsAtCarbon->isPast() && $status !== 'active') {
                            $status = 'expired';
                        }
                    }

                    // Sprawdzamy status okresu próbnego
                    $isOnTrial = in_array($status, ['on_trial', 'trialing'], true);
                    $isActive = $status === 'active' || $isOnTrial;

                    // 1. POBIERANIE AKTYWNYCH MODUŁÓW (FEATURES)
                    if ($isActive) {
                        // Ładujemy plan z bazy centralnej
                        $plan = $tenant->plan_id ? Plan::on('mysql')->find($tenant->plan_id) : null;
                        $rawFeatures = $plan?->features ?? [];
                        
                        if (is_string($rawFeatures)) {
                            $rawFeatures = json_decode($rawFeatures, true) ?? [];
                        }

                        // Jeśli to trial ALBO brak zdefiniowanych funkcji w planie — udostępniamy PEŁNY PAKIET
                        if ($isOnTrial || empty($rawFeatures)) {
                            $tenantFeatures = [
                                'shop', 'pos', 'kds', 'delivery', 
                                'inventory_bom', 'loyalty', 'rcp', 
                                'multi_location', 'custom_domain'
                            ];
                        } else {
                            $tenantFeatures = $rawFeatures;
                        }
                    }

                    $subscriptionInfo = [
                        'plan_id'   => $tenant->plan_id ?? 1,
                        'plan_name' => $plan?->name ?? ($isOnTrial ? 'Pakiet Próbny (14 Dni)' : 'Brak planu'),
                        'ends_at'   => $endsAt,
                        'status'    => $isOnTrial ? 'trialing' : $status,
                    ];
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('Błąd w middleware HandleInertiaRequests: ' . $e->getMessage());
            }

            // Pobieranie uprawnień i aktywnej zmiany (RCP)
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
        
        // Aktualizacja nazwy aplikacji
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