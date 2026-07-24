<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;

class UserController extends Controller
{
    /**
     * Wyświetla listę pracowników.
     */
    public function index()
    {
        $users = User::orderBy('name', 'asc')->get();

        return Inertia::render('Admin/Users', [
            'users' => $users
        ]);
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
            'role'     => 'required|in:admin,manager,chef,waiter,driver',
        ]);

        // Używamy jawnego przypisania obiektowego dla 100% pewności zapisu roli
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
            'password' => 'nullable|string|min:6', // Hasło opcjonalne przy edycji
            'role'     => 'required|in:admin,manager,chef,waiter,driver',
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
        // Blokujemy usunięcie samego siebie (zalogowanego admina)
        if (auth()->id() === $user->id) {
            return redirect()->back()->with('error', 'Błąd: Nie możesz usunąć własnego konta administratora!');
        }

        $user->delete();

        return redirect()->back()->with('success', "Konto pracownika zostało pomyślnie usunięte z bazy danych.");
    }

}