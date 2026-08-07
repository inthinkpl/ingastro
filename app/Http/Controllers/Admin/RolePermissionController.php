<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RolePermission;
use Illuminate\Http\Request;

class RolePermissionController extends Controller
{
    /**
     * Słownik wszystkich modułów/funkcji w systemie ERP
     */
    public static function getAvailablePermissions(): array
    {
        return [
            'settings.general'       => 'Wizytówka i Zasady Zamówień',
            'settings.discounts'     => 'Kody Rabatowe (Tworzenie i Edycja)',
            'settings.notifications' => 'Powiadomienia Web Push (Szablony KDS / Kurier)', // <-- DODANY KLUCZ UPRAWNIENIA
            'settings.payments'      => 'Konfiguracja BRAMEK PŁATNOŚCI',
            'users.manage'           => 'Zarządzanie Pracownikami i Zespołem',
            'products.manage'        => 'Karta Dań i Receptury BOM',
            'inventory.manage'       => 'Gospodarka Magazynowa Surowców',
            'reconciliation.view'    => 'Rozliczanie Gotówki Kurierów',
            'delivery_zones.manage'  => 'Strefy Dostaw i Mapy',
        ];
    }

    /**
     * Zapisuje zaktualizowaną macierz uprawnień ról.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'matrix' => 'required|array', // e.g. ['manager' => ['settings.general', 'settings.discounts']]
        ]);

        foreach ($validated['matrix'] as $role => $permissions) {
            if ($role === 'admin') continue; // Omijamy admina

            // Czyszczenie dotychczasowych uprawnień roli i zapis nowych
            RolePermission::where('role', $role)->delete();

            foreach ($permissions as $perm) {
                RolePermission::create([
                    'role'       => $role,
                    'permission' => $perm,
                ]);
            }
        }

        return redirect()->back()->with('success', 'Uprawnienia ról zostały zaktualizowane.');
    }
}