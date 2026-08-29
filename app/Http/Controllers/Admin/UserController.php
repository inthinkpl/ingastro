<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\RolePermission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;

class UserController extends Controller
{
    /**
     * Wyświetla listę pracowników oraz aktualną macierz uprawnień ról.
     */
    public function index()
    {
        $users = User::orderBy('name', 'asc')->get();

        // Słownik opisów modułów dla komponentu Vue
        $availablePermissions = [
            'pos.access'          => 'Kasa POS (Kelner)',
            'kds.access'          => 'Ekran Kuchenny KDS',
            'orders.view'         => 'Lista Zamówień',
            'dashboard.financial' => 'Dashboard Finansowy',
            'products.manage'     => 'Karty Dań i Receptury BOM',
            'inventory.manage'    => 'Gospodarka Magazynowa Surowców',
            'loyalty.manage'      => 'Program Lojalnościowy',
            'promotions.manage'   => 'Promocje i Gratisy',
            'users.manage'        => 'Zarządzanie Zespołem (Pracownicy)',
            'rcp.view'            => 'Ewidencja Czasu Pracy (RCP)',
            'reconciliation.view' => 'Rozliczenia Kurierów',
            'delivery_zones.manage' => 'Strefy Dostaw',
            'settings.general'    => 'Ustawienia Globalne',
        ];

        // Pobranie obecnych uprawnień z bazy danych zmapowanych na rolę
        $allRolePermissions = RolePermission::all();
        $rolePermissions = [
            'manager' => $allRolePermissions->where('role', 'manager')->pluck('permission')->values()->all(),
            'staff'   => $allRolePermissions->where('role', 'staff')->pluck('permission')->values()->all(),
            'chef'    => $allRolePermissions->where('role', 'chef')->pluck('permission')->values()->all(),
            'driver'  => $allRolePermissions->where('role', 'driver')->pluck('permission')->values()->all(),
        ];

        return Inertia::render('Admin/Users', [
            'users'                => $users,
            'availablePermissions' => $availablePermissions,
            'rolePermissions'      => $rolePermissions,
        ]);
    }

    /**
     * Zapisuje zaktualizowaną macierz uprawnień dla poszczególnych ról.
     */
    public function updatePermissions(Request $request)
    {
        $validated = $request->validate([
            'matrix'           => 'required|array',
            'matrix.manager'   => 'nullable|array',
            'matrix.staff'     => 'nullable|array',
            'matrix.chef'      => 'nullable|array',
            'matrix.driver'    => 'nullable|array',
        ]);

        $matrix = $validated['matrix'];

        foreach (['manager', 'staff', 'chef', 'driver'] as $role) {
            // 1. Czyszczenie dotychczasowych uprawnień roli
            RolePermission::where('role', $role)->delete();

            // 2. Przypisanie nowych uprawnień wybranych z macierzy
            if (isset($matrix[$role]) && is_array($matrix[$role])) {
                foreach ($matrix[$role] as $permissionKey) {
                    RolePermission::create([
                        'role'       => $role,
                        'permission' => $permissionKey,
                    ]);
                }
            }
        }

        return redirect()->back()->with('success', 'Macierz uprawnień ról została pomyślnie zaktualizowana.');
    }

    /**
     * Tworzy nowe konto pracownicze (C z cyklu CRUD).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
            'role'     => 'required|in:admin,manager,chef,waiter,driver,staff',
        ]);

        $user = new User();
        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->role = $validated['role'];
        $user->password = Hash::make($validated['password']);
        $user->save();

        return redirect()->back()->with('success', "Konto pracownika {$user->name} zostało pomyślnie utworzone.");
    }

    /**
     * Aktualizuje profil pracownika (U z cyklu CRUD).
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:6',
            'role'     => 'required|in:admin,manager,chef,waiter,driver,staff',
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->role = $validated['role'];

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return redirect()->back()->with('success', "Dane pracownika {$user->name} zostały zaktualizowane.");
    }

    /**
     * Usuwa konto pracownika z systemu (D z cyklu CRUD).
     */
    public function destroy(User $user)
    {
        if (auth()->id() === $user->id) {
            return redirect()->back()->with('error', 'Błąd: Nie możesz usunąć własnego konta administratora!');
        }

        $user->delete();

        return redirect()->back()->with('success', 'Konto pracownika zostało pomyślnie usunięte z bazy danych.');
    }
}