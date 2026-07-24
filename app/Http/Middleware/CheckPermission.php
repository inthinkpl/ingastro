<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\RolePermission;

class CheckPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if (!$user) {
            abort(401, 'Brak autoryzacji.');
        }

        // Admin omija sprawdzanie i ma pełen dostęp
        if ($user->role === 'admin') {
            return $next($request);
        }

        // Sprawdzamy czy rola użytkownika posiada uprawnienie
        if (!RolePermission::hasPermission($user->role, $permission)) {
            abort(403, 'Brak uprawnień do tego modułu.');
        }

        return $next($request);
    }
}