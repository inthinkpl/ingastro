<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ingredient;
use App\Models\StockTransfer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class WarehouseController extends Controller
{
    /**
     * Wyświetla stan obu magazynów oraz historię przesunięć MM.
     */
    public function index(): Response
    {
        return Inertia::render('Admin/Warehouse/Index', [
            'ingredients' => Ingredient::orderBy('name')->get(),
            'transfers' => StockTransfer::with(['ingredient', 'user'])
                ->latest()
                ->paginate(20)
        ]);
    }

    /**
     * Wykonuje przesunięcie surowca: Magazyn Główny -> Magazyn Lokalny.
     */
    public function transfer(Request $request)
    {
        $validated = $request->validate([
            'ingredient_id' => 'required|exists:ingredients,id',
            'quantity' => 'required|numeric|min:0.001',
            'notes' => 'nullable|string|max:255',
        ]);

        try {
            DB::transaction(function () use ($validated, $request) {
                // Blokujemy wiersz surowca do odczytu/zapisu na czas transakcji (ochrona przed race condition)
                $ingredient = Ingredient::lockForUpdate()->findOrFail($validated['ingredient_id']);

                // Walidacja dostępności surowca w Magazynie Głównym
                if ($ingredient->stock_main < $validated['quantity']) {
                    throw new \Exception("Brak wystarczającej ilości w Magazynie Głównym! Dostępne: {$ingredient->stock_main} {$ingredient->unit}");
                }

                // 1. Odejmij z Głównego, Dodaj do Lokalnego
                $ingredient->decrement('stock_main', $validated['quantity']);
                $ingredient->increment('stock_local', $validated['quantity']);

                // 2. Zapisz wpis dokumentu MM w historii
                StockTransfer::create([
                    'ingredient_id' => $ingredient->id,
                    'user_id' => $request->user()?->id,
                    'quantity' => $validated['quantity'],
                    'unit' => $ingredient->unit,
                    'notes' => $validated['notes'] ?? null,
                ]);
            });

            return redirect()->back()->with('success', 'Pomyślnie przekazano surowiec do Magazynu Lokalnego.');

        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['quantity' => $e->getMessage()]);
        }
    }
}