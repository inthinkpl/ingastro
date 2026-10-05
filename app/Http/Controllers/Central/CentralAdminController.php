<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\SystemModule;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\File;
use Inertia\Inertia;
use App\Models\EmailTemplate;
use App\Services\SaaSMailer;

class CentralAdminController extends Controller
{
    public function index()
    {
        $modules = SystemModule::on('mysql')->orderBy('sort_order', 'asc')->get();
        $emailTemplates = EmailTemplate::on('mysql')->get(); // 👈 Pobranie szablonów email

        $tenants = Tenant::on('mysql')->with(['domains'])->get()->map(function ($tenant) {
            $enabledFeatures = $tenant->enabled_features ?? ['pos', 'shop', 'kds'];
            if (is_string($enabledFeatures)) {
                $enabledFeatures = json_decode($enabledFeatures, true) ?? ['pos', 'shop', 'kds'];
            }

            return [
                'id' => $tenant->id,
                'domain' => $tenant->domains->first()?->domain,
                'subscription_status' => $tenant->subscription_status ?? 'active',
                'subscription_ends_at' => $tenant->subscription_ends_at 
                    ? (is_string($tenant->subscription_ends_at) 
                        ? $tenant->subscription_ends_at 
                        : $tenant->subscription_ends_at->format('Y-m-d H:i')) 
                    : 'Bezterminowo',
                'enabled_features' => $enabledFeatures,
                'created_at' => $tenant->created_at ? $tenant->created_at->format('Y-m-d') : null,
            ];
        });

        $totalMrr = $tenants->where('subscription_status', 'active')->sum(function ($t) use ($modules) {
            $features = $t['enabled_features'];
            return $modules->whereIn('key', $features)->sum('price_monthly');
        });

        return Inertia::render('Central/Admin/Dashboard', [
            'tenants' => $tenants,
            'modules' => $modules,
            'emailTemplates' => $emailTemplates, // 👈 Przekazanie do widoku Vue
            'stats' => [
                'total_tenants' => $tenants->count(),
                'active_subscriptions' => $tenants->where('subscription_status', 'active')->count(),
                'monthly_mrr' => (float) $totalMrr,
            ]
        ]);
    }

    /**
     * Rejestracja nowego lokalu gastronomicznego
     */
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
            'enabled_features' => ['required', 'array', 'min:1'],
            'subscription_ends_at' => ['nullable', 'date'],
        ]);

        // 🔒 WYMUSZENIE MODUŁU POS
        $features = $validated['enabled_features'];
        if (!in_array('pos', $features)) {
            $features[] = 'pos';
        }

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

        // 2. Tworzenie Tenanta z zestawem modułów
        $tenant = Tenant::on('mysql')->create([
            'id' => $subdomain,
            'tenancy_db_name' => 'tenant_' . $subdomain,
            'enabled_features' => $features, // używamy zmodyfikowanej tablicy
            'subscription_status' => 'active',
            'subscription_ends_at' => $validated['subscription_ends_at'] ?: null,
        ]);

        $tenant->createDomain(['domain' => $fullDomain]);

        // 3. Tworzenie konta administratora i nazwy lokalu w bazie tenanta
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

        // ✉️ WYSYŁKA E-MAILA POWITALNEGO (Z 14-DNIOWYM TRIALEM)
        SaaSMailer::send('welcome_tenant', $validated['email'], [
            'admin_name'      => $validated['admin_name'],
            'restaurant_name' => $validated['restaurant_name'],
            'login_url'       => 'https://' . $fullDomain . '/login'
        ]);

        return redirect()->back()->with('success', 'Nowy lokal gastronomiczny został utworzony i powiadomiony e-mailem!');
    }

    /**
     * Zmiana statusu i przydzielonych modułów dla wskazanego tenanta
     */
    public function updateTenantSubscription(Request $request, $tenant)
    {
        $validated = $request->validate([
            'enabled_features' => ['nullable', 'array'],
            'subscription_ends_at' => ['nullable', 'date'],
            'subscription_status' => ['required', 'in:active,trialing,expired,cancelled'],
        ]);

        $tenantModel = $tenant instanceof Tenant ? $tenant : Tenant::on('mysql')->findOrFail($tenant);

        // 🔒 WYMUSZENIE MODUŁU POS PRZY AKTUALIZACJI
        $features = $validated['enabled_features'] ?? $tenantModel->enabled_features;
        if (is_array($features) && !in_array('pos', $features)) {
            $features[] = 'pos';
        }

        $tenantModel->update([
            'enabled_features' => $features,
            'subscription_ends_at' => $validated['subscription_ends_at'] ?: null,
            'subscription_status' => $validated['subscription_status'],
        ]);

        // ✉️ WYSYŁKA E-MAILA O ZMIANIE ABONAMENTU
        $modules = SystemModule::on('mysql')->whereIn('key', $features)->get();
        $moduleNames = $modules->pluck('name')->implode(', ');
        $mrrAmount = $modules->sum('price_monthly');

        // Wchodzimy do bazy lokalu, by pobrać email właściciela
        $tenantModel->run(function () use ($tenantModel, $moduleNames, $mrrAmount) {
            $admin = \App\Models\User::where('role', 'admin')->first();
            $restaurantName = \Illuminate\Support\Facades\DB::table('system_settings')->where('key', 'restaurant_name')->value('value') ?? $tenantModel->id;

            if ($admin) {
                SaaSMailer::send('subscription_updated', $admin->email, [
                    'admin_name'      => $admin->name,
                    'restaurant_name' => $restaurantName,
                    'active_modules'  => $moduleNames,
                    'mrr_amount'      => number_format($mrrAmount, 2, ',', ' ')
                ]);
            }
        });

        return redirect()->back()->with('success', 'Moduły i stan subskrypcji lokalu zostały zaktualizowane, powiadomienie zostało wysłane.');
    }

    /**
     * Aktualizacja ceny netto i dostępności modułu systemowego
     */
    public function updateModule(Request $request, SystemModule $module)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'price_monthly' => ['required', 'numeric', 'min:0'],
            'is_active' => ['required', 'boolean'],
            'description' => ['nullable', 'string'],
        ]);

        $module->update($validated);

        return redirect()->back()->with('success', "Cena i parametry modułu {$module->name} zostały pomyślnie zaktualizowane.");
    }

    /**
     * Dodanie nowego modułu systemowego przez Super Admina
     */
    public function storeModule(Request $request)
    {
        $validated = $request->validate([
            'key' => ['required', 'string', 'max:50', Rule::unique('mysql.system_modules', 'key')],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price_monthly' => ['required', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
        ]);

        SystemModule::on('mysql')->create($validated);

        return redirect()->back()->with('success', 'Nowy moduł systemowy został dodany.');
    }

    /**
     * Zapisuje zmiany w szablonie E-mail
     */
    public function updateEmailTemplate(Request $request, $id)
    {
        $template = EmailTemplate::on('mysql')->findOrFail($id);
        
        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'is_active' => ['boolean'],
        ]);

        $template->update($validated);

        return redirect()->back()->with('success', 'Szablon e-mail został zaktualizowany.');
    }

    /**
     * Wysyła testowy E-mail na podany adres
     */
    public function sendTestEmail(Request $request, $id)
    {
        $template = EmailTemplate::on('mysql')->findOrFail($id);
        
        $validated = $request->validate([
            'test_email' => ['required', 'email']
        ]);

        // Generujemy zaślepki z dostępnych zmiennych, żeby podgląd ładnie wyglądał
        $dummyVars = [];
        if (is_array($template->available_variables)) {
            foreach ($template->available_variables as $key => $label) {
                $dummyVars[$key] = "[TEST] " . $label;
            }
        }

        SaaSMailer::send($template->event_key, $validated['test_email'], $dummyVars);

        return redirect()->back()->with('success', 'Testowy e-mail wysłany na: ' . $validated['test_email']);
    }
}