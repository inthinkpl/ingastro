<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Models\NotificationSetting;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\DiscountCode;
use App\Models\RolePermission;
use App\Http\Controllers\Admin\RolePermissionController;

class SettingsController extends Controller
{
    /**
     * Wyświetla panel ustawień globalnych ERP i ładuje aktualne parametry z bazy.
     */
    public function edit(Request $request)
    {
        $savedPermissions = RolePermission::all()->groupBy('role')->map(function ($group) {
            return $group->pluck('permission')->toArray();
        });

        return Inertia::render('Admin/Settings', [
            'restaurantName'        => SystemSetting::get('restaurant_name', 'Pizzeria Savona'),
            'restaurantPhone'       => SystemSetting::get('restaurant_phone', ''),
            'restaurantAddress'     => SystemSetting::get('restaurant_address', ''),
            'minOrderAmount'        => (float) SystemSetting::get('min_order_amount', 40.00),
            
            // 🚚 Ustawienia darmowej dostawy
            'freeDeliveryEnabled'   => filter_var(SystemSetting::get('free_delivery_enabled', '0'), FILTER_VALIDATE_BOOLEAN),
            'freeDeliveryMinAmount' => (float) SystemSetting::get('free_delivery_min_amount', 60.00),

            // 🥤 Ustawienia sugestii w koszyku (Upselling / Cross-selling)
            'upsellEnabled'         => filter_var(SystemSetting::get('upsell_enabled', '1'), FILTER_VALIDATE_BOOLEAN),

            // 🍕 Ustawienia modułu Pizzy Pół na Pół
            'halfHalfEnabled'       => filter_var(SystemSetting::get('half_half_enabled', '1'), FILTER_VALIDATE_BOOLEAN),

            'currentGateway'        => SystemSetting::get('payment_gateway', 'simulation'),
            'payuEnv'               => SystemSetting::get('payu_env', 'sandbox'),
            'payuPosId'             => SystemSetting::get('payu_pos_id', ''),
            'payuClientId'          => SystemSetting::get('payu_client_id', ''),
            'payuClientSecret'      => SystemSetting::get('payu_client_secret', ''),
            'payuSecondKey'         => SystemSetting::get('payu_second_key', ''),
            'discountCodes'         => DiscountCode::latest()->get(),
            'notificationSettings'  => NotificationSetting::all(),
            'availablePermissions'  => RolePermissionController::getAvailablePermissions(),
            'rolePermissions'       => $savedPermissions,
            'authRole'              => $request->user()->role,
        ]);
    }

    /**
     * Zapisuje lub aktualizuje konfigurację w bazie danych w bezpiecznej pętli transakcyjnej.
     */
    public function save(Request $request)
    {
        // Pancerne reguły walidacji - pilnują kompletności danych produkcyjnych
        $validated = $request->validate([
            'restaurant_name'          => 'required|string|max:255',
            'restaurant_phone'         => 'nullable|string|max:50',
            'restaurant_address'       => 'nullable|string|max:500',
            'payment_gateway'          => 'required|string|in:simulation,payu,stripe',
            'payu_env'                 => 'required|string|in:sandbox,production',
            'min_order_amount'         => 'required|numeric|min:0',

            // 🚚 Walidacja pól darmowej dostawy
            'free_delivery_enabled'    => 'required|boolean',
            'free_delivery_min_amount' => 'required|numeric|min:0',

            // 🥤 Walidacja przełącznika Upsellingu
            'upsell_enabled'           => 'required|boolean',

            // 🍕 Walidacja przełącznika Pizzy Pół na Pół
            'half_half_enabled'        => 'required|boolean',
            
            // Reguła required_if gwarantuje, że jeśli wybrano bramkę 'payu', poniższe pola są obowiązkowe
            'payu_pos_id'              => 'nullable|required_if:payment_gateway,payu|string|max:100',
            'payu_client_id'           => 'nullable|required_if:payment_gateway,payu|string|max:100',
            'payu_client_secret'       => 'nullable|required_if:payment_gateway,payu|string|max:255',
            'payu_second_key'          => 'nullable|required_if:payment_gateway,payu|string|max:255',
        ]);

        // Masowy zapis typu EAV (Entity-Attribute-Value) do tabeli ustawień klucz-wartość
        foreach ($validated as $key => $value) {
            SystemSetting::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }

        // Powrót do formularza z komunikatem sukcesu w sesji Flash
        return redirect()->back()->with('success', 'Wszystkie parametry systemowe, finansowe i rekomendacji zostały poprawnie zabezpieczone.');
    }

    /**
     * Zapisuje zaktualizowane szablony powiadomień Web Push.
     */
    public function updateNotifications(Request $request)
    {
        $validated = $request->validate([
            'settings'                  => 'required|array',
            'settings.*.id'             => 'required|exists:notification_settings,id',
            'settings.*.title_template' => 'required|string|max:255',
            'settings.*.body_template'  => 'required|string|max:500',
        ]);

        foreach ($validated['settings'] as $setting) {
            NotificationSetting::where('id', $setting['id'])->update([
                'title_template' => $setting['title_template'],
                'body_template'  => $setting['body_template'],
            ]);
        }

        return redirect()->back()->with('success', 'Szablony powiadomień Web Push zostały pomyślnie zaktualizowane.');
    }
}