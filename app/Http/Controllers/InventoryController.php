<?php

namespace App\Http\Controllers;

use App\Models\Ingredient;
use App\Models\StockTransfer;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InventoryController extends Controller
{
    /**
     * Wyświetla panel managera z listą surowców oraz historią przesunięć MM.
     */
    public function index()
    {
        $ingredients = Ingredient::orderBy('name', 'asc')->get();

        return Inertia::render('Manager/Inventory', [
            'ingredients' => $ingredients,
            'transfers' => StockTransfer::with(['ingredient', 'user'])
                ->latest()
                ->paginate(20)
        ]);
    }

    /**
     * Zwiększa stan w Magazynie Głównym po przyjęciu dostawy zewnętrznej.
     */
    public function restock(Ingredient $ingredient, Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01'
        ]);

        // Dostawa od dostawcy trafia do Magazynu Głównego
        $ingredient->increment('stock_main', $validated['amount']);

        return redirect()->back()->with('success', "Pomyślnie przyjęto dostawę do Magazynu Głównego: +{$validated['amount']} {$ingredient->unit} dla {$ingredient->name}!");
    }

    /**
     * Tworzy nowy surowiec w magazynie (C z cyklu CRUD).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:ingredients,name',
            'stock_main' => 'nullable|numeric|min:0',
            'stock_quantity' => 'nullable|numeric|min:0', // kompatybilność ze starym formularzem
            'stock_local' => 'nullable|numeric|min:0',
            'min_stock_local' => 'nullable|numeric|min:0',
            'min_limit' => 'nullable|numeric|min:0',       // kompatybilność ze starym formularzem
            'unit' => 'required|string|max:10',
            'purchase_price' => 'required|numeric|min:0',
        ]);

        Ingredient::create([
            'name' => $validated['name'],
            'stock_main' => $validated['stock_main'] ?? $validated['stock_quantity'] ?? 0,
            'stock_local' => $validated['stock_local'] ?? 0,
            'min_stock_local' => $validated['min_stock_local'] ?? $validated['min_limit'] ?? 0, // Poprawiono: Domyślnie 0 zamiast 5
            'unit' => $validated['unit'],
            'purchase_price' => $validated['purchase_price'],
        ]);

        return redirect()->back()->with('success', "Surowiec {$validated['name']} został pomyślnie dodany do magazynu!");
    }

    /**
     * Aktualizuje parametry surowca (U z cyklu CRUD).
     */
public function update(Request $request, Ingredient $ingredient)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:ingredients,name,' . $ingredient->id,
            'min_stock_local' => 'nullable|numeric|min:0',
            'min_limit' => 'nullable|numeric|min:0',
            'unit' => 'required|string|max:10',
            'purchase_price' => 'required|numeric|min:0',
        ]);

        // Pobieramy nową wartość minimum (z min_stock_local lub min_limit)
        $newMinStock = $validated['min_stock_local'] ?? $validated['min_limit'] ?? 0;

        $ingredient->update([
            'name' => $validated['name'],
            'min_stock_local' => $newMinStock,
            'min_limit' => $newMinStock, // zapisujemy w obu miejscach dla bezpieczeństwa
            'unit' => $validated['unit'],
            'purchase_price' => $validated['purchase_price'],
        ]);

        return redirect()->back()->with('success', "Parametry surowca {$ingredient->name} zostały zaktualizowane!");
    }

    /**
     * Usuwa surowiec z bazy (D z cyklu CRUD).
     */
    public function destroy(Ingredient $ingredient)
    {
        $ingredient->delete();

        return redirect()->back()->with('success', "Surowiec został bezpowrotnie usunięty z magazynu.");
    }
}