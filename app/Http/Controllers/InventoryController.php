<?php

namespace App\Http\Controllers;

use App\Models\Ingredient;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InventoryController extends Controller
{
    /**
     * Wyświetla panel managera z listą surowców.
     */
    public function index()
    {
        $ingredients = Ingredient::orderBy('name', 'asc')->get();

        return Inertia::render('Manager/Inventory', [
            'ingredients' => $ingredients
        ]);
    }

    /**
     * Zwiększa stan magazynowy surowca po przyjęciu dostawy.
     */
    public function restock(Ingredient $ingredient, Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01'
        ]);

        $ingredient->increment('stock_quantity', $validated['amount']);

        return redirect()->back()->with('success', "Pomyślnie przyjęto dostawę: +{$validated['amount']} {$ingredient->unit} dla {$ingredient->name}!");
    }

    /**
     * Tworzy nowy surowiec w magazynie (C z cyklu CRUD).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:ingredients,name',
            'stock_quantity' => 'required|numeric|min:0',
            'min_limit' => 'required|numeric|min:0',
            'unit' => 'required|string|max:10',
            'purchase_price' => 'required|numeric|min:0',
        ]);

        Ingredient::create($validated);

        return redirect()->back()->with('success', "Surowiec {$validated['name']} został pomyślnie dodany do magazynu!");
    }

    /**
     * Aktualizuje parametry surowca (U z cyklu CRUD).
     */
    public function update(Request $request, Ingredient $ingredient)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:ingredients,name,' . $ingredient->id,
            'min_limit' => 'required|numeric|min:0',
            'unit' => 'required|string|max:10',
            'purchase_price' => 'required|numeric|min:0',
        ]);

        $ingredient->update($validated);

        return redirect()->back()->with('success', "Parametry surowca {$ingredient->name} zostały zaktualizowane!");
    }

    /**
     * Usuwa surowiec z bazy (D z cyklu CRUD).
     */
    public function destroy(Ingredient $ingredient)
    {
        // W przyszłości dodamy warunek sprawdzający, czy surowiec nie jest częścią jakiejś receptury
        $ingredient->delete();

        return redirect()->back()->with('success', "Surowiec został bezpowrotnie usunięty z magazynu.");
    }
}