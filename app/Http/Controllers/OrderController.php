<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\Ingredient;
use App\Actions\CreateOrderAction;
use App\Events\OrderPlaced;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OrderController extends Controller
{
    /**
     * Wyświetla panel kasy dotykowej POS dla kelnera.
     */
    public function pos()
    {
        $products = Product::with('variants')->where('is_active', true)->orderBy('category')->get();
        $ingredients = Ingredient::orderBy('name', 'asc')->get();

        return Inertia::render('Waiter/POS', [
            'products' => $products,
            'ingredients' => $ingredients
        ]);
    }

    /**
     * Wyświetla monitor kuchenny KDS dla szefa kuchni.
     */
    public function kds()
    {
        // Pobieramy zamówienia w toku (nowe, w przygotowaniu, gotowe).
        // Gdy status zmieni się na 'w_dostawie' (np. po kliknięciu "Odebrałem z kuchni"), zamówienie automatycznie zniknie z KDS!
        $orders = Order::with([
                'items.variant.product', 
                'items.modifiers.ingredient', 
                'driver' // 🔥 Dodane: Kucharz widzi na KDS przypisanego kierowcę
            ])
            ->whereIn('status', ['nowe', 'w_przygotowaniu', 'gotowe'])
            ->where(function ($query) {
                // 🔥 POPRAWKA BAZY: Zamówienia automatyczne (online, blik, payu) muszą być opłacone.
                // Zamówienia stacjonarne / za pobraniem (gotówka, karta) wchodzą od razu.
                $query->whereNotIn('payment_method', ['online', 'blik', 'payu'])
                      ->orWhere('payment_status', 'opłacone');
            })
            ->orderBy('created_at', 'asc')
            ->get();

        return Inertia::render('Chef/KDS', [
            'orders' => $orders
        ]);
    }

    /**
     * Składanie nowego zamówienia (Obsługuje POS kelnera oraz koszyk E-commerce WWW).
     */
    public function store(Request $request, CreateOrderAction $createOrderAction)
    {
        $validated = $request->validate([
            'type' => 'required|in:lokal,wynos,dostawa',
            // 🔥 POPRAWKA 1: Rozszerzamy dozwolone metody płatności o 'blik' oraz 'payu'
            'payment_method' => 'nullable|in:gotówka,karta,online,blik,payu',
            'table_number' => 'nullable|integer',
            'delivery_address' => 'nullable|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.product_variant_id' => 'required|exists:product_variants,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.modifiers' => 'nullable|array',
            'items.*.modifiers.*.ingredient_id' => 'required|exists:ingredients,id',
            'items.*.modifiers.*.action' => 'required|in:ADD,REMOVE',
        ]);

        // Uruchamiamy potok biznesowy (tworzenie rekordu zamówienia)
        $order = $createOrderAction->execute($validated);

        // Definiujemy, które metody traktujemy jako e-płatności automatyczne
        $onlineMethods = ['online', 'blik', 'payu'];

        // 🔥 POPRAWKA 2: Transmisję WebSocket (KDS kuchni) odpalamy tylko dla płatności fizycznych.
        // Żadne zamówienie opłacane przez BLIK czy PayU nie ma prawa wskoczyć kucharzom przed zaksięgowaniem kasy.
        if (!in_array($request->input('payment_method'), $onlineMethods)) {
            if (class_exists('\App\Events\OrderPlaced')) {
                broadcast(new \App\Events\OrderPlaced($order))->toOthers();
            }
        }

        // 🔥 POPRAWKA 3: Obsługa automatycznych bramek płatniczych
        if (in_array($request->input('payment_method'), $onlineMethods)) {
            // Przekazujemy wybraną metodę do fabryki (np. żeby wiedziała czy wygenerować formatkę BLIK, czy przekierować do PayU)
            $gateway = \App\Services\Payment\PaymentFactory::make($request->input('payment_method'));
            $redirectUrl = $gateway->purchase($order);

            if (str_starts_with($redirectUrl, 'http') && !str_contains($redirectUrl, request()->getHost())) {
                return Inertia::location($redirectUrl);
            }

            return redirect($redirectUrl);
        }

        // Gwarantujemy, że zamówienie posiada token śledzenia
            if (empty($order->tracking_token)) {
            $order->update([
                'tracking_token' => \Illuminate\Support\Str::random(32)
            ]);
        }

            // Przekierowanie na nową stronę śledzenia z wygenerowanym tokenem
            return redirect()->route('order.status', ['token' => $order->tracking_token]);
    }

    /**
     * Aktualizacja statusu zamówienia przez kucharza na monitorze KDS.
     */
/**
     * Aktualizacja statusu zamówienia przez kucharza na monitorze KDS.
     */
    public function updateStatus(Request $request, Order $order)
    {
        $validated = $request->validate([
            'status' => 'required|in:nowe,w_przygotowaniu,gotowe,w drodze,dostarczone,wydane'
        ]);

        $oldStatus = $order->status;
        $newStatus = $validated['status'];

        $order->update([
            'status' => $newStatus
        ]);

        // 📦 AUTOMATYKA BOM: Zdejmij surowce z magazynu, gdy status zmienia się na 'gotowe'
        if ($newStatus === 'gotowe' && $oldStatus !== 'gotowe') {
            $order->load(['items.variant.ingredients', 'items.modifiers']);

            foreach ($order->items as $item) {
                $variant = $item->variant;
                if (!$variant) continue;

                // 1. Analiza bazowej receptury dania z tabeli ingredient_variant
                foreach ($variant->ingredients as $ingredient) {
                    $recipeQty = $ingredient->pivot->amount_needed;

                    // Modyfikator "BEZ" (np. bez cebuli)
                    $isRemoved = $item->modifiers
                        ->where('ingredient_id', $ingredient->id)
                        ->where('action', 'REMOVE')
                        ->isNotEmpty();

                    if ($isRemoved) {
                        continue; 
                    }

                    // Łączne zużycie (z przepisu × liczba zamówionych dań)
                    $totalDeduction = $recipeQty * $item->quantity;

                    // 🔥 POPRAWKA: Wskazujemy wprost Twoją kolumnę stock_quantity zamiast zgadywania
                    $ingredient->decrement('stock_quantity', $totalDeduction);
                }

                // 2. Modyfikatory "DODATKOWO" (np. ekstra ser)
                $addModifiers = $item->modifiers->where('action', 'ADD');
                foreach ($addModifiers as $modifier) {
                    $extraIngredient = \App\Models\Ingredient::find($modifier->ingredient_id);
                    if ($extraIngredient) {
                        $extraQty = $modifier->quantity ?? $modifier->amount ?? 0.05; 
                        $totalExtraDeduction = $extraQty * $item->quantity;

                        // 🔥 POPRAWKA: Tutaj również wskazujemy stock_quantity
                        $extraIngredient->decrement('stock_quantity', $totalExtraDeduction);
                    }
                }
            }
        }

        if (class_exists('\App\Events\OrderPlaced')) {
            broadcast(new \App\Events\OrderPlaced($order))->toOthers();
        }

        return redirect()->back()->with('success', "Status zmieniony. Surowce rozliczone w bazie danych.");
    }

    public function saveRecipe(Request $request, $variantId)
    {
        $request->validate([
            'ingredients' => 'required|array',
            'ingredients.*.id' => 'required|exists:ingredients,id',
            // Akceptujemy zarówno klucz quantity, jak i amount_needed z frontendu
            'ingredients.*.quantity' => 'nullable|numeric|min:0.001',
            'ingredients.*.amount_needed' => 'nullable|numeric|min:0.001',
        ]);

        $variant = \App\Models\ProductVariant::findOrFail($variantId);

        $syncData = [];
        foreach ($request->input('ingredients') as $ing) {
            // Pobieramy wartość niezależnie od formatu wysłanego przez Vue
            $amount = $ing['amount_needed'] ?? $ing['quantity'] ?? 0;
            
            // 🔥 ZMIANA: mapujemy na Twoją kolumnę z migracji
            $syncData[$ing['id']] = ['amount_needed' => $amount];
        }

        // Synchronizacja automatycznie wpisze dane do tabeli ingredient_variant
        $variant->ingredients()->sync($syncData);

        return redirect()->back()->with('success', 'Receptura dla wariantu została zapisana!');
    }
}