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
     * Wyświetla listę produktów w panelu managera.
     */
    public function index()
    {
        // Pobieramy produkty z ich wariantami oraz surowcami i wagą z pivota (amount_needed)
        $products = Product::with(['variants.ingredients' => function($query) {
            $query->select('ingredients.id', 'ingredients.name', 'ingredients.unit')
                  ->withPivot('amount_needed');
        }])->orderBy('category')->get();

        // Pobieramy wszystkie surowce z magazynu do listy wyboru we Vue
        $ingredients = Ingredient::orderBy('name', 'asc')->get();

        return Inertia::render('Manager/Products', [
            'products'    => $products,
            'ingredients' => $ingredients
        ]);
    }

    /**
     * Zapisuje nowy produkt w bazie danych wraz ze zdjęciem i wariantami.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'                 => 'required|string|max:255|unique:products,name',
            'category'             => 'required|string|max:100',
            'description'          => 'nullable|string|max:1000',
            'image'                => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'is_active'            => 'required|boolean',
            'variants'             => 'required|array|min:1',
            'variants.*.size_name' => 'required|string|max:255',
            'variants.*.price'     => 'required|numeric|min:0',
        ]);

        if ($request->hasFile('image')) {
            $validated['image_path'] = $request->file('image')->store('products', 'public');
        }

        // 1. Zapis produktu głównego
        $product = Product::create([
            'name'        => $validated['name'],
            'category'    => $validated['category'],
            'description' => $validated['description'] ?? null,
            'image_path'  => $validated['image_path'] ?? null,
            'is_active'   => $validated['is_active'],
        ]);

        // 2. Zapis przypisanych wariantów
        foreach ($validated['variants'] as $v) {
            $product->variants()->create([
                'size_name' => $v['size_name'],
                'price'     => $v['price'],
            ]);
        }

        return redirect()->back()->with('success', 'Nowy produkt wraz z wariantami został pomyślnie dodany do karty dań!');
    }

    /**
     * Aktualizuje dane istniejącego produktu oraz jego warianty.
     */
    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name'                 => 'required|string|max:255|unique:products,name,' . $product->id,
            'category'             => 'required|string|max:100',
            'description'          => 'nullable|string|max:1000',
            'image'                => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
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

        // 1. Aktualizacja danych głównych potrawy
        $product->update([
            'name'        => $validated['name'],
            'category'    => $validated['category'],
            'description' => $validated['description'] ?? null,
            'image_path'  => $validated['image_path'] ?? $product->image_path,
            'is_active'   => $validated['is_active'],
        ]);

        // 2. Aktualizacja / Dodawanie / Usuwanie wariantów
        if (isset($validated['variants'])) {
            $updatedVariantIds = [];

            foreach ($validated['variants'] as $v) {
                if (!empty($v['id'])) {
                    $variant = $product->variants()->find($v['id']);
                    if ($variant) {
                        $variant->update([
                            'size_name' => $v['size_name'],
                            'price'     => $v['price'],
                        ]);
                        $updatedVariantIds[] = $variant->id;
                    }
                } else {
                    $newVariant = $product->variants()->create([
                        'size_name' => $v['size_name'],
                        'price'     => $v['price'],
                    ]);
                    $updatedVariantIds[] = $newVariant->id;
                }
            }

            $product->variants()->whereNotIn('id', $updatedVariantIds)->delete();
        }

        return redirect()->back()->with('success', 'Dane produktu wraz z wariantami zostały zaktualizowane.');
    }

    /**
     * Usuwa produkt z bazy danych oraz czyści pliki graficzne z dysku.
     */
    public function destroy(Product $product)
    {
        if ($product->image_path) {
            Storage::disk('public')->delete($product->image_path);
        }

        $product->delete();

        return redirect()->back()->with('success', 'Produkt został bezpowrotnie usunięty z systemu.');
    }

    /**
     * Szybkie dodawanie nowego wariantu rozmiarowego z poziomu modalu BOM.
     */
    public function storeVariant(Request $request, Product $product)
    {
        $validated = $request->validate([
            'size_name' => 'required|string|max:255',
            'price'     => 'required|numeric|min:0',
        ]);

        $product->variants()->create($validated);

        return redirect()->back()->with('success', 'Nowy wariant został dodany!');
    }

    /**
     * Usuwa konkretny wariant rozmiarowy potrawy.
     */
    public function destroyVariant(ProductVariant $variant)
    {
        $variant->delete();

        return redirect()->back()->with('success', 'Wariant został usunięty.');
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

        // Synchronizacja tabeli pivot ingredient_variant
        $variant->ingredients()->sync($syncData);

        return redirect()->back()->with('success', 'Receptura BOM dla wariantu została pomyślnie zapisana!');
    }
}