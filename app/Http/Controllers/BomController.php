<?php

namespace App\Http\Controllers;

use App\Models\ProductVariant;
use Illuminate\Http\Request;

class BomController extends Controller
{
    /**
     * Zapisuje lub aktualizuje recepturę BOM dla wybranego wariantu potrawy.
     */
    public function saveVariantRecipe(Request $request)
    {
        $validated = $request->validate([
            'variant_id'           => 'required|exists:product_variants,id',
            'items'                => 'present|array',
            'items.*.ingredient_id' => 'required|exists:ingredients,id',
            'items.*.amount_needed' => 'required|numeric|min:0.001',
        ]);

        $variant = ProductVariant::findOrFail($validated['variant_id']);

        // Przygotowanie danych dla metody sync()
        $syncData = [];
        foreach ($validated['items'] as $item) {
            $syncData[$item['ingredient_id']] = [
                'amount_needed' => $item['amount_needed']
            ];
        }

        // Zapis/Podmiana surowców w tabeli pivot ingredient_variant
        $variant->ingredients()->sync($syncData);

        return back()->with('success', 'Receptura BOM dla wariantu została zapisana.');
    }
}