<?php

namespace App\Actions;

use Illuminate\Support\Str;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use App\Models\Ingredient;
use App\Models\DiscountCode; // 🔥 KROK 3.3: Import modelu kodów rabatowych
use Illuminate\Support\Facades\DB;
use Exception;
use App\Events\OrderPlaced;
use App\Jobs\ProcessOrderJob;
use App\Jobs\PrintKitchenTicketJob;
use App\Services\Delivery\GeocodingService;
use App\Services\Delivery\RouteOptimizationService;

class CreateOrderAction
{
    /**
     * Wykonuje pełny potok zapisu zamówienia oraz aktualizacji magazynu.
     *
     * @param array $data Dane wejściowe (zwalidowane przez Form Request)
     * @return Order
     * @throws Exception
     */
    public function execute(array $data): Order
    {
        // Krok 1: Inicjacja bezpiecznej transakcji bazodanowej
        return DB::transaction(function () use ($data) {
            
            // 🔥 POPRAWKA 1: Sprawdzamy czy metoda płatności to automat online (online, blik, payu)
            $onlineMethods = ['online', 'blik', 'payu'];
            $isOnlinePayment = in_array($data['payment_method'] ?? 'gotówka', $onlineMethods);

            // Jeśli online -> status startowy to 'oczekuje na płatność'. W innym przypadku -> 'nowe'.
            $initialStatus = $isOnlinePayment ? 'oczekuje na płatność' : 'nowe';

            // Tworzymy szkielet zamówienia z dynamicznym statusem
            $order = Order::create([
                'tracking_token'   => Str::random(32),
                'user_id'          => $data['user_id'] ?? null,
                'type'             => $data['type'],
                'status'           => $initialStatus, // Dynamiczny status startowy
                'payment_method'   => $data['payment_method'] ?? 'gotówka',
                'payment_status'   => $data['payment_status'] ?? 'nieopłacone',
                'delivery_address' => $data['delivery_address'] ?? null,
                'delivery_zone_id' => $data['delivery_zone_id'] ?? null,
                'total_price'      => 0.00,
            ]);

            $totalPrice = 0.00;

            // Przetwarzamy każdą pozycję z koszyka
            foreach ($data['items'] as $itemData) {
                // Pobieramy wariant produktu bezpośrednio z bazy
                $variant = ProductVariant::with('ingredients')->findOrFail($itemData['product_variant_id']);
                
                // Wyliczamy sumę częściową
                $subtotal = $variant->price * $itemData['quantity'];
                $totalPrice += $subtotal;

                // Zapisujemy pozycję zamówienia
                $orderItem = $order->items()->create([
                    'product_variant_id' => $variant->id,
                    'quantity'           => $itemData['quantity'],
                    'subtotal'           => $subtotal,
                ]);

                // Silnik Receptur i Magazynu (Inventory Engine)
                $this->processInventory($variant, $itemData['quantity'], $itemData['modifiers'] ?? [], $orderItem);
            }

            // 🔥 KROK 3.3: OBSŁUGA KODÓW RABATOWYCH
            $discountAmount = 0.00;

            if (!empty($data['discount_code'])) {
                $code = strtoupper(trim($data['discount_code']));
                $discount = DiscountCode::where('code', $code)
                    ->where('is_active', true)
                    ->first();

                if ($discount && ($totalPrice >= $discount->min_order_amount)) {
                    // Walidacja daty wygaśnięcia
                    if (!$discount->expires_at || $discount->expires_at->isFuture()) {
                        $discountAmount = $discount->calculateDiscount($totalPrice);
                        $discount->increment('times_used'); // Zwiększamy licznik użyć w bazie
                    }
                }
            }

            // Ostateczna kwota do zapłaty (po uwzględnieniu zniżki)
            $finalPrice = max(0.00, $totalPrice - $discountAmount);

            // Aktualizujemy zamówienie o ostatecznie wyliczoną kwotę
            $order->update(['total_price' => $finalPrice]);

            // 🔥 AUTOMATYZACJA STREF I GEOLOKALIZACJI
            if ($order->type === 'dostawa' && !empty($order->delivery_address)) {
                $geocoder = new GeocodingService();
                $coords = $geocoder->geocodeAddress($order->delivery_address);

                if ($coords) {
                    $order->update([
                        'lat' => $coords['lat'],
                        'lng' => $coords['lng'],
                    ]);

                    $routeService = new RouteOptimizationService();
                    // 1. Wyznaczenie strefy i domyślnego kierowcy
                    $routeService->assignZoneAndDriver($order);

                    // 2. Jeśli zamówienie otrzymało kierowcę, przeliczamy jego ciąg tras (1, 2, 3...)
                    if ($order->driver_id) {
                        $routeService->optimizeDriverRoute($order->driver_id);
                    }
                }
            }

            // 🔥 POPRAWKA 2: Uruchamiamy procesy poboczne TYLKO dla zamówień stacjonarnych/za pobraniem.
            if (!$isOnlinePayment) {
                // Rozgłaszamy zdarzenie real-time do kucharza na KDS
                event(new OrderPlaced($order));

                // WYSTRZELENIE JOBA DO REDISA: Przetwarzanie zamówienia w tle
                ProcessOrderJob::dispatch($order);

                // MOST SPRZĘTOWY: Wystrzelenie zadania druku termicznego w kuchni
                PrintKitchenTicketJob::dispatch($order);
            }

            return $order;
        });
    }

    /**
     * Obsługa Silnika Receptur i Blokad Pesymistycznych
     */
    private function processInventory(ProductVariant $variant, int $quantity, array $modifiers, OrderItem $orderItem): void
    {
        // Krok 2.1: Identyfikacja wykluczeń (składniki z flagą REMOVE)
        $excludedIngredientIds = [];
        foreach ($modifiers as $mod) {
            if ($mod['action'] === 'REMOVE') {
                $excludedIngredientIds[] = $mod['ingredient_id'];
                
                // Zapisujemy modyfikator w bazie danych
                $orderItem->modifiers()->create([
                    'ingredient_id' => $mod['ingredient_id'],
                    'action'        => 'REMOVE',
                ]);
            }
        }

        // Krok 2.2 & 2.3: Pobieranie i blokowanie wierszy magazynu (FOR UPDATE)
        foreach ($variant->ingredients as $ingredient) {
            // Jeśli składnik został wykluczony przez klienta, pomijamy go w ściąganiu z magazynu
            if (in_array($ingredient->id, $excludedIngredientIds)) {
                continue;
            }

            // Blokada pesymistyczna: FOR UPDATE na poziomie konkretnego wiersza surowca
            $lockedIngredient = Ingredient::where('id', $ingredient->id)
                ->lockForUpdate()
                ->first();

            if (!$lockedIngredient) {
                continue;
            }

            // Obliczenie zapotrzebowania: gramatura z receptury * ilość zamówionych dań
            $amountNeeded = $ingredient->pivot->amount_needed * $quantity;

            // Pobieramy stan bezpośrednio z Twojej kolumny stock_quantity
            $currentStock = $lockedIngredient->stock_quantity;

            if ($currentStock < $amountNeeded) {
                // 1. Definiujemy, co jest zamówieniem z e-commerce (dostawa / sklep www)
                $isEcommerce = ($orderData['source'] ?? '') === 'ecommerce' 
                    || ($orderData['type'] ?? '') === 'dostawa'
                    || request()->input('type') === 'dostawa'
                    || request()->routeIs('api.*'); 

                // 2. Sprawdzamy, czy kelner na POS kliknął "TAK" (czyli wymusił ignore_stock)
                $isForcedByWaiter = request()->input('ignore_stock') === true 
                    || ($orderData['ignore_stock'] ?? false) === true;

                if ($isEcommerce || $isForcedByWaiter) {
                    // Zgodnie z wytycznymi: zamówienie przechodzi, a surowiec w magazynie spada do 0 (brak)
                    $lockedIngredient->update(['stock_quantity' => 0]);
                } else {
                    // Dla standardowego POS nie crashujemy systemu, tylko wysyłamy kontrolowany błąd do Inertii
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'missing_ingredient' => $lockedIngredient->name
                    ]);
                }
            } else {
                // Jeśli surowca wystarczy, zmniejszamy stan magazynowy zablokowanego wiersza bezpiecznie o amountNeeded
                $lockedIngredient->decrement('stock_quantity', $amountNeeded);
            }
        }

        // Krok 2.5 (cd): Obsługa dodatków (składniki z flagą ADD / EXTRA)
        foreach ($modifiers as $mod) {
            if ($mod['action'] === 'ADD') {
                // Blokujemy surowiec dodany ekstra
                $lockedIngredient = Ingredient::where('id', $mod['ingredient_id'])
                    ->lockForUpdate()
                    ->first();

                // Zakładamy sztywną porcję standardową dla dodatku (np. 0.05 kg / 50g)
                $extraAmount = 0.05 * $quantity;

                if ($lockedIngredient->stock_quantity < $extraAmount) {
                    throw new Exception("Brak surowca na dodatek EXTRA: {$lockedIngredient->name}. Wycofuję zamówienie.");
                }

                $lockedIngredient->decrement('stock_quantity', $extraAmount);

                // Zapisujemy modyfikator w bazie danych
                $orderItem->modifiers()->create([
                    'ingredient_id' => $mod['ingredient_id'],
                    'action'        => 'ADD',
                ]);
            }
        }
    }
}