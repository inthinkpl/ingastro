<?php

// 1. POPRAWIONY NAMESPACE - wskazuje dokładnie na podfolder Manager
namespace App\Http\Controllers\Manager; 

// 2. IMPORT BAZOWEGO KONTROLERA (ponieważ wyszliśmy z głównego folderu)
use App\Http\Controllers\Controller; 

use App\Models\Product;
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
        // Pobieramy produkty z ich wariantami oraz aktualnymi recepturami z tabeli pośredniczącej
        $products = Product::with(['variants.ingredients' => function($query) {
            $query->select('ingredients.id', 'ingredients.name', 'ingredients.unit');
        }])->orderBy('category')->get();

        // Pobieramy wszystkie surowce z magazynu do listy wyboru we Vue
        $ingredients = \App\Models\Ingredient::orderBy('name', 'asc')->get();

        return Inertia::render('Manager/Products', [
            'products' => $products,
            'ingredients' => $ingredients
        ]);
    }

    /**
     * Zapisuje nowy produkt w bazie danych wraz z przesłanym zdjęciem.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:products,name',
            'category' => 'required|string|max:100',
            'description' => 'nullable|string|max:1000',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048', // Max 2MB
            'is_active' => 'required|boolean',
        ]);

        // Obsługa fizycznego przesyłania pliku na serwer
        if ($request->hasFile('image')) {
            // Plik trafia do storage/app/public/products
            $validated['image_path'] = $request->file('image')->store('products', 'public');
        }

        Product::create($validated);

        return redirect()->back()->with('success', 'Nowy produkt został pomyślnie dodany do karty dań!');
    }

    /**
     * Aktualizuje dane istniejącego produktu (obsługuje Multipart POST z podmienioną metodą PUT).
     */
    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:products,name,' . $product->id,
            'category' => 'required|string|max:100',
            'description' => 'nullable|string|max:1000',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'is_active' => 'required|boolean',
        ]);

        // Jeśli menedżer przesyła nowe zdjęcie potrawy
        if ($request->hasFile('image')) {
            // 1. Bezpieczeństwo dyskowe: jeśli produkt miał stare zdjęcie, bezwzględnie je kasujemy
            if ($product->image_path) {
                Storage::disk('public')->delete($product->image_path);
            }
            
            // 2. Zapisujemy nowy plik w chmurze lokalnej serwera
            $validated['image_path'] = $request->file('image')->store('products', 'public');
        }

        $product->update($validated);

        return redirect()->back()->with('success', 'Dane produktu wraz z multimediami zostały zaktualizowane.');
    }

    /**
     * Usuwa produkt z bazy danych oraz całkowicie czyści pliki graficzne z nim powiązane.
     */
    public function destroy(Product $product)
    {
        // Przed usunięciem rekordu z bazy, czyścimy strukturę plików, by nie śmiecić na dysku VPS
        if ($product->image_path) {
            Storage::disk('public')->delete($product->image_path);
        }

        // Usunięcie produktu automatycznie (kaskadowo) usunie jego warianty z bazy danych
        $product->delete();

        return redirect()->back()->with('success', 'Produkt został bezpowrotnie usunięty z systemu.');
    }

    /**
     * Zapisuje/Aktualizuje recepturę BOM dla konkretnego wariantu produktu.
     */
    public function saveRecipe(\Illuminate\Http\Request $request, $variantId)
    {
        $request->validate([
            'ingredients' => 'required|array',
            'ingredients.*.id' => 'required|exists:ingredients,id',
            'ingredients.*.quantity' => 'nullable|numeric|min:0.001',
            'ingredients.*.amount_needed' => 'nullable|numeric|min:0.001',
        ]);

        // Lokalizujemy wariant produktu w bazie danych
        $variant = \App\Models\ProductVariant::findOrFail($variantId);

        // Mapujemy dane przysłane z formularza Vue na strukturę tabeli pivot
        $syncData = [];
        foreach ($request->input('ingredients') as $ing) {
            $amount = $ing['amount_needed'] ?? $ing['quantity'] ?? 0;
            $syncData[$ing['id']] = ['amount_needed' => $amount];
        }

        // Automatyczna synchronizacja tabeli ingredient_variant (czyści stare, dodaje nowe)
        $variant->ingredients()->sync($syncData);

        return redirect()->back()->with('success', 'Receptura BOM dla wariantu została pomyślnie zapisana!');
    }
}