<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureFeatureEnabled
{
    /**
     * Słownik czytelnych nazw modułów do komunikatów błędów dla użytkownika
     */
    protected array $featureNames = [
        'shop'           => 'Sklep E-Commerce & Zamówienia Online',
        'pos'            => 'System POS do przyjmowania zamówień w lokalu',
        'kds'            => 'Ekran Kuchenny (KDS)',
        'delivery'       => 'Moduł i Aplikacja dla Kurierów',
        'inventory_bom'  => 'Gospodarka Magazynowa & Receptury BOM',
        'loyalty'        => 'Program Lojalnościowy i Kody Rabatowe',
        'rcp'            => 'Ewidencja i Czas Pracy (RCP)',
        'multi_location' => 'Wsparcie dla wielu lokalizacji',
        'custom_domain'  => 'Własna domena',
    ];

    /**
     * Obsługuje nadchodzące żądanie i weryfikuje dostępność funkcji w planie subskrypcji.
     */
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $tenant = tenant();

        // 1. Jeśli brak kontekstu tenanta (np. domeny centralne), przepuszczamy żądanie dalej
        if (!$tenant) {
            return $next($request);
        }

        // 2. Jeśli model Tenant posiada już własną metodę hasFeature(), używamy jej w pierwszej kolejności
        $hasAccess = false;

        if (method_exists($tenant, 'hasFeature')) {
            $hasAccess = $tenant->hasFeature($feature);
        } else {
            // Fallback: Weryfikacja bezpośrednio z planu przypisanego do tenanta z bazy centralnej
            $tenant->loadMissing('plan');
            $plan = $tenant->plan;

            if ($plan && $plan->features) {
                $features = is_array($plan->features) 
                    ? $plan->features 
                    : (json_decode($plan->features, true) ?? []);

                $hasAccess = in_array($feature, $features, true);
            }
        }

        // 3. Jeśli moduł jest niedostępny w planie
        if (!$hasAccess) {
            $readableName = $this->featureNames[$feature] ?? $feature;
            $message = "Moduł \"{$readableName}\" nie jest dostępny w Twoim aktualnym planie subskrypcji. Zwiększ plan, aby z niego korzystać.";

            // Dla zapytań AJAX / API / Inertia
            if ($request->wantsJson() || $request->expectsJson()) {
                return response()->json([
                    'message' => $message,
                    'feature' => $feature,
                ], 403);
            }

            // Dla standardowych żądań HTML -> Pr przekierowanie do zakładki subskrypcji
            return redirect()
                ->route('admin.settings.edit')
                ->with('error', $message);
        }

        return $next($request);
    }
}