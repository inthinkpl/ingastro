<?php

namespace App\Http\Controllers\Manager; 

use App\Http\Controllers\Controller; 
use App\Models\Product;
use App\Models\Ingredient;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ProductController extends Controller
{
    /**
     * Wyświetla listę wszystkich produktów (aktywnych i ukrytych) w panelu managera.
     */
    public function index()
    {
        $products = Product::with(['variants' => function($query) {
                $query->where('is_active', true);
            }, 'variants.ingredients' => function($query) {
                $query->select('ingredients.id', 'ingredients.name', 'ingredients.unit')
                      ->withPivot('amount_needed');
            }])
            ->orderBy('category')
            ->get();

        $ingredients = Ingredient::orderBy('name', 'asc')->get();
        $categories = Product::distinct()->pluck('category')->filter()->values()->all();

        return Inertia::render('Manager/Products', [
            'products'    => $products,
            'ingredients' => $ingredients,
            'categories'  => $categories
        ]);
    }

    /**
     * Zapisuje nowy produkt wraz ze zdjęciem i wariantami w bazie danych.
     */
   public function store(Request $request)
{
    // Jeśli warianty zostały przesłane jako ciąg tekstu przez FormData, dekodujemy je do tablicy
    if (is_string($request->variants)) {
        $request->merge([
            'variants' => json_decode($request->variants, true)
        ]);
    }

    $validated = $request->validate([
        'name'                 => 'required|string|max:255|unique:products,name',
        'category'             => 'required|string|max:100',
        'description'          => 'nullable|string|max:1000',
        'image'                => 'nullable|image|mimes:jpeg,png,jpg,webp|max:10240', // Do 10MB
        'is_active'            => 'nullable|boolean',
        'variants'             => 'required|array|min:1',
        'variants.*.size_name' => 'required|string|max:255',
        'variants.*.price'     => 'required|numeric|min:0',
    ]);

    if ($request->hasFile('image')) {
        $validated['image_path'] = $request->file('image')->store('products', 'public');
    }

    $product = Product::create([
        'name'        => $validated['name'],
        'category'    => $validated['category'],
        'description' => $validated['description'] ?? null,
        'image_path'  => $validated['image_path'] ?? null,
        'is_active'   => $validated['is_active'] ?? true,
    ]);

    foreach ($validated['variants'] as $v) {
        $product->variants()->create([
            'size_name' => $v['size_name'],
            'price'     => $v['price'],
            'is_active' => true,
        ]);
    }

    return redirect()->back()->with('success', 'Nowy produkt wraz z wariantami i zdjęciem został pomyślnie dodany!');
}

    /**
     * Aktualizuje dane istniejącego produktu oraz jego warianty (w tym zmianę statusu is_active).
     */
    public function update(Request $request, Product $product)
{
    // Jeśli warianty przyszły jako ciąg JSON przez FormData
    if (is_string($request->variants)) {
        $request->merge([
            'variants' => json_decode($request->variants, true)
        ]);
    }

    $validated = $request->validate([
        'name'                 => 'required|string|max:255|unique:products,name,' . $product->id,
        'category'             => 'required|string|max:100',
        'description'          => 'nullable|string|max:1000',
        'image'                => 'nullable|image|mimes:jpeg,png,jpg,webp|max:10240',
        'is_active'            => 'required|boolean',
        'variants'             => 'nullable|array',
        'variants.*.id'        => 'nullable|exists:product_variants,id',
        'variants.*.size_name' => 'required_with:variants|string|max:255',
        'variants.*.price'     => 'required_with:variants|numeric|min:0',
    ]);

    if ($request->hasFile('image')) {
        if ($product->image_path) {
            Storage::disk('public')->delete($product->image_path);
        }
        $validated['image_path'] = $request->file('image')->store('products', 'public');
    }

    $product->update([
        'name'        => $validated['name'],
        'category'    => $validated['category'],
        'description' => $validated['description'] ?? null,
        'image_path'  => $validated['image_path'] ?? $product->image_path,
        'is_active'   => $validated['is_active'],
    ]);

    if (isset($validated['variants'])) {
        $updatedVariantIds = [];

        foreach ($validated['variants'] as $v) {
            if (!empty($v['id'])) {
                $variant = $product->variants()->find($v['id']);
                if ($variant) {
                    $variant->update([
                        'size_name' => $v['size_name'],
                        'price'     => $v['price'],
                        'is_active' => true,
                    ]);
                    $updatedVariantIds[] = $variant->id;
                }
            } else {
                $newVariant = $product->variants()->create([
                    'size_name' => $v['size_name'],
                    'price'     => $v['price'],
                    'is_active' => true,
                ]);
                $updatedVariantIds[] = $newVariant->id;
            }
        }

        $product->variants()->whereNotIn('id', $updatedVariantIds)->update(['is_active' => false]);
    }

    return redirect()->back()->with('success', 'Dane produktu i zdjęcie zostały zaktualizowane.');
}

    /**
     * Fizycznie usuwa produkt lub wycofuje go ze sklepu, jeśli ma powiązania z zamówieniami.
     */
    public function destroy(Product $product)
    {
        try {
            // Usuwamy relacje wariantów oraz receptury BOM
            foreach ($product->variants as $variant) {
                $variant->ingredients()->detach();
                $variant->delete();
            }

            // Usuwamy produkt główny
            $product->delete();

            return redirect()->back()->with('success', 'Produkt został trwale usunięty z bazy danych.');
        } catch (\Illuminate\Database\QueryException $e) {
            // Jeśli produkt istnieje w archiwalnych zamówieniach (SQL 23000), wyłączamy go
            $product->update(['is_active' => false]);
            $product->variants()->update(['is_active' => false]);

            return redirect()->back()->with('success', 'Produkt posiada archiwalne zamówienia — został ukryty i wycofany z oferty.');
        }
    }

    /**
     * Dodawanie nowego wariantu rozmiarowego z poziomu modalu BOM lub edycji.
     */
    public function storeVariant(Request $request, Product $product)
    {
        $validated = $request->validate([
            'size_name' => 'required|string|max:255',
            'price'     => 'required|numeric|min:0',
        ]);

        $validated['is_active'] = true;

        $product->variants()->create($validated);

        return redirect()->back()->with('success', 'Nowy wariant został dodany!');
    }

    /**
     * Wyłącza (dezaktywuje) konkretny wariant rozmiarowy potrawy.
     */
    public function destroyVariant(ProductVariant $variant)
    {
        $activeVariantsCount = $variant->product->variants()->where('is_active', true)->count();
        if ($activeVariantsCount <= 1) {
            return redirect()->back()->with('error', 'Błąd: Produkt musi posiadać przynajmniej jeden aktywny wariant cenowy!');
        }

        $variant->update(['is_active' => false]);

        return redirect()->back()->with('success', 'Wariant został pomyślnie wyłączony.');
    }

    /**
     * Zapisuje/Aktualizuje recepturę BOM dla konkretnego wariantu produktu.
     */
    public function saveRecipe(Request $request, $variantId)
    {
        $variant = ProductVariant::findOrFail($variantId);

        $rawIngredients = $request->input('ingredients', []);
        
        $syncData = [];
        foreach ($rawIngredients as $ing) {
            $ingId = $ing['id'] ?? null;
            $amount = $ing['amount_needed'] ?? $ing['quantity'] ?? null;

            if ($ingId && $amount !== null && (float)$amount > 0) {
                $syncData[$ingId] = ['amount_needed' => (float)$amount];
            }
        }

        $variant->ingredients()->sync($syncData);

        return redirect()->back()->with('success', 'Receptura BOM dla wariantu została pomyślnie zapisana!');
    }
}