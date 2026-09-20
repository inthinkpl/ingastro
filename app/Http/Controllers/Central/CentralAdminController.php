<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\File;
use Inertia\Inertia;

class CentralAdminController extends Controller
{
    public function index()
    {
        $tenants = Tenant::with(['plan', 'domains'])->get()->map(function ($tenant) {
            return [
                'id' => $tenant->id,
                'domain' => $tenant->domains->first()?->domain,
                'plan_name' => $tenant->plan?->name ?? 'Brak planu',
                'plan_id' => $tenant->plan_id,
                'subscription_status' => $tenant->subscription_status,
                'subscription_ends_at' => $tenant->subscription_ends_at 
                    ? (is_string($tenant->subscription_ends_at) 
                        ? $tenant->subscription_ends_at 
                        : $tenant->subscription_ends_at->format('Y-m-d H:i')) 
                    : 'Bezterminowo',
                'created_at' => $tenant->created_at ? $tenant->created_at->format('Y-m-d') : null,
            ];
        });

        $plans = Plan::all();

        return Inertia::render('Central/Admin/Dashboard', [
            'tenants' => $tenants,
            'plans' => $plans,
            'stats' => [
                'total_tenants' => $tenants->count(),
                'active_subscriptions' => $tenants->where('subscription_status', 'active')->count(),
                'monthly_mrr' => Tenant::with('plan')->get()->sum(fn($t) => $t->plan?->price_monthly ?? 0),
            ]
        ]);
    }

    public function storeTenant(Request $request)
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
            'password' => ['required', 'string', 'min:8'],
            'plan_id' => ['required', 'exists:plans,id'],
            'subscription_ends_at' => ['nullable', 'date'],
        ]);

        $subdomain = Str::slug($validated['subdomain']);
        $centralHost = config('app.central_domain', 'localhost');
        $fullDomain = "{$subdomain}.{$centralHost}";

        // 1. Kopiowanie domyślnych zdjęć ze wzorca
        $sourcePath = storage_path('app/public/products');
        $tenantStorageDir = storage_path("tenant_{$subdomain}/app/public/products");

        if (File::exists($sourcePath)) {
            File::makeDirectory($tenantStorageDir, 0755, true, true);
            File::copyDirectory($sourcePath, $tenantStorageDir);
        }

        // 2. Tworzenie Tenanta z planem
        $tenant = Tenant::create([
            'id' => $subdomain,
            'tenancy_db_name' => 'tenant_' . $subdomain,
            'plan_id' => $validated['plan_id'],
            'subscription_status' => 'active',
            'subscription_ends_at' => $validated['subscription_ends_at'] ?: null,
        ]);

        $tenant->createDomain(['domain' => $fullDomain]);

        // 3. Tworzenie konta administratora i nazwy pizzerii w bazie tenanta
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

        return redirect()->back()->with('success', 'Nowa pizzeria została utworzona!');
    }

    public function updateTenantSubscription(Request $request, $tenant)
    {
        $validated = $request->validate([
            'plan_id' => ['required', 'exists:plans,id'],
            'subscription_ends_at' => ['nullable', 'date'],
            'subscription_status' => ['required', 'in:active,expired,cancelled'],
        ]);

        // Pobranie tenanta niezależnie czy przekazano ID (string) czy zaimplementowano Route Model Binding
        $tenantModel = $tenant instanceof Tenant ? $tenant : Tenant::findOrFail($tenant);

        $tenantModel->update([
            'plan_id' => $validated['plan_id'],
            'subscription_ends_at' => $validated['subscription_ends_at'] ?: null,
            'subscription_status' => $validated['subscription_status'],
        ]);

        return redirect()->back()->with('success', 'Plan i subskrypcja lokalu zostały zaktualizowane.');
    }
}