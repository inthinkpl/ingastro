<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\File;
use Inertia\Inertia;

class TenantRegisterController extends Controller
{
    public function create()
    {
        return Inertia::render('Central/Register');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'restaurant_name' => ['required', 'string', 'max:255'],
            'subdomain' => [
                'required', 
                'string', 
                'alpha_dash', 
                'max:50', 
                Rule::unique('domains', 'domain')
            ],
            'admin_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $subdomain = Str::slug($validated['subdomain']);
        $centralHost = config('app.central_domain', 'localhost');
        $fullDomain = "{$subdomain}.{$centralHost}";

        // 1. Tworzenie Tenanta i przydzielenie domeny
        $tenant = Tenant::create([
            'id' => $subdomain,
            'tenancy_db_name' => 'tenant_' . $subdomain,
        ]);

        $tenant->createDomain(['domain' => $fullDomain]);

        // 2. Kopiowanie plików z wykorzystaniem kontekstu tenanta lub bezpośredniej ścieżki pakietu
        $sourcePath = storage_path('app/public/products');
        
        // Sprawdzamy oba warianty ścieżki (z podkreślnikiem i bez, zależnie od konfiguracji suffix_base)
        $tenantStorageDir = storage_path("tenant_{$subdomain}/app/public/products");
        if (!File::exists(storage_path("tenant_{$subdomain}"))) {
            $tenantStorageDir = storage_path("tenant{$subdomain}/app/public/products");
        }

        if (File::exists($sourcePath)) {
            File::makeDirectory($tenantStorageDir, 0755, true, true);
            File::copyDirectory($sourcePath, $tenantStorageDir);
        }

        // 3. Inicjalizacja danych w bazie tenanta
        $tenant->run(function () use ($validated) {
            \App\Models\User::create([
                'name' => $validated['admin_name'],
                'email' => $validated['email'],
                'password' => bcrypt($validated['password']),
                'role' => 'admin',
            ]);

            \Illuminate\Support\Facades\DB::table('system_settings')->updateOrInsert(
                ['key' => 'restaurant_name'],
                ['value' => $validated['restaurant_name'], 'created_at' => now(), 'updated_at' => now()]
            );
        });

        $protocol = request()->secure() ? 'https://' : 'http://';
        return Inertia::location($protocol . $fullDomain . '/login');
    }
}