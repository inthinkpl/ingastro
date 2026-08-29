<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\Ingredient;
use App\Models\ProductVariant;
use App\Actions\CreateOrderAction;
use App\Events\OrderPlaced;
use App\Events\OrderStatusUpdated;
use App\Services\Payment\PaymentFactory;
use App\Services\LoyaltyService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
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
        $orders = Order::with([
                'items.variant.product', 
                'items.modifiers.ingredient', 
                'driver'
            ])
            ->whereIn('status', ['nowe', 'w_przygotowaniu', 'gotowe'])
            ->where(function ($query) {
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

        $order = $createOrderAction->execute($validated);

        $onlineMethods = ['online', 'blik', 'payu'];

        if (!in_array($request->input('payment_method'), $onlineMethods)) {
            if (class_exists('\App\Events\OrderPlaced')) {
                broadcast(new OrderPlaced($order))->toOthers();
            }
        }

        if (in_array($request->input('payment_method'), $onlineMethods)) {
            $gateway = PaymentFactory::make($request->input('payment_method'));
            $redirectUrl = $gateway->purchase($order);

            if (str_starts_with($redirectUrl, 'http') && !str_contains($redirectUrl, request()->getHost())) {
                return Inertia::location($redirectUrl);
            }

            return redirect($redirectUrl);
        }

        if (empty($order->tracking_token)) {
            $order->update([
                'tracking_token' => Str::random(32)
            ]);
        }

        return redirect()->route('order.status', ['token' => $order->tracking_token]);
    }

    /**
     * Aktualizacja statusu zamówienia przez kucharza na monitorze KDS, dostawcę lub managera.
     */
    public function updateStatus(Request $request, Order $order, LoyaltyService $loyaltyService)
    {
        $validated = $request->validate([
            'status' => 'required|string'
        ]);

        $oldStatus = $order->status;
        $requestedStatus = $validated['status'];

        // 🎯 OBSŁUGA PRZEPŁYWU KDS I DOSTAWY:
        // 1. Jeśli to wydanie z kuchni lub odbiór przez dostawcę:
        if (in_array($requestedStatus, ['wydane', 'w_dostawie', 'odbierz_z_kuchni'])) {
            if ($order->type === 'dostawa') {
                $newStatus = 'w_dostawie'; // Zamówienie z dostawą wyrusza w trasę (znika z KDS)
            } else {
                $newStatus = 'zrealizowane'; // Odbiór osobisty / na miejscu zostaje wydany i zrealizowany (znika z KDS)
            }
        } else {
            // Dla pozostałych akcji (np. 'gotowe', 'w_przygotowaniu', 'dostarczone', 'anulowane')
            $newStatus = $requestedStatus;
        }

        $order->update([
            'status' => $newStatus
        ]);

        event(new OrderStatusUpdated($order));

        // 📦 AUTOMATYKA BOM: Zdejmij surowce z Magazynu Lokalnego przy przejściu na 'gotowe', 'w_dostawie' lub 'zrealizowane'
        if (in_array($newStatus, ['gotowe', 'w_dostawie', 'wydane', 'zrealizowane']) && !in_array($oldStatus, ['gotowe', 'w_dostawie', 'wydane', 'zrealizowane'])) {
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

                    // 🎯 Odejmowanie z Magazynu Lokalnego
                    $ingredient->decrement('stock_local', $totalDeduction);
                }

                // 2. Modyfikatory "DODATKOWO" (np. ekstra ser)
                $addModifiers = $item->modifiers->where('action', 'ADD');
                foreach ($addModifiers as $modifier) {
                    $extraIngredient = Ingredient::find($modifier->ingredient_id);
                    if ($extraIngredient) {
                        $extraQty = $modifier->quantity ?? $modifier->amount ?? 0.05; 
                        $totalExtraDeduction = $extraQty * $item->quantity;

                        // 🎯 Odejmowanie z Magazynu Lokalnego
                        $extraIngredient->decrement('stock_local', $totalExtraDeduction);
                    }
                }
            }
        }

        // 🎁 AUTOMATYKA PROGRAMU LOJALNOŚCIOWEGO:
        // Naliczenie punktów po wydaniu / wysłaniu zamówienia w trasę
        if (in_array($newStatus, ['gotowe', 'w_dostawie', 'dostarczone', 'wydane', 'zrealizowane']) && !in_array($oldStatus, ['gotowe', 'w_dostawie', 'dostarczone', 'wydane', 'zrealizowane'])) {
            try {
                $loyaltyService->addPointsForOrder($order);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Błąd naliczania punktów lojalnościowych dla zamówienia #{$order->id}: " . $e->getMessage());
            }
        }

        if (class_exists('\App\Events\OrderPlaced')) {
            broadcast(new OrderPlaced($order))->toOthers();
        }

        return redirect()->back()->with('success', "Status zaktualizowany.");
    }

    /**
     * Zapisuje recepturę (BOM) dla konkretnego wariantu produktu.
     */
    public function saveRecipe(Request $request, $variantId)
    {
        $request->validate([
            'ingredients' => 'required|array',
            'ingredients.*.id' => 'required|exists:ingredients,id',
            'ingredients.*.quantity' => 'nullable|numeric|min:0.001',
            'ingredients.*.amount_needed' => 'nullable|numeric|min:0.001',
        ]);

        $variant = ProductVariant::findOrFail($variantId);

        $syncData = [];
        foreach ($request->input('ingredients') as $ing) {
            $amount = $ing['amount_needed'] ?? $ing['quantity'] ?? 0;
            $syncData[$ing['id']] = ['amount_needed' => $amount];
        }

        $variant->ingredients()->sync($syncData);

        return redirect()->back()->with('success', 'Receptura dla wariantu została zapisana!');
    }
}