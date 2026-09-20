<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureFeatureEnabled
{
    /**
     * Obsługuje nadchodzące żądanie i weryfikuje dostępność funkcji w planie subskrypcji.
     */
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $tenant = tenant();

        if (!$tenant || !$tenant->hasFeature($feature)) {
            if ($request->wantsJson()) {
                return response()->json([
                    'message' => "Moduł '{$feature}' nie jest dostępny w Twoim aktualnym planie subskrypcji."
                ], 403);
            }

            return redirect()->route('admin.settings.edit')->with(
                'error', 
                "Ta funkcja ({$feature}) wymaga wyższego planu subskrypcji lub opłacenia abonamentu."
            );
        }

        return $next($request);
    }
}