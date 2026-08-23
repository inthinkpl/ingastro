<?php

namespace App\Actions;

use Illuminate\Support\Str;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use App\Models\Ingredient;
use App\Models\DiscountCode;
use App\Models\LoyaltyAccount;
use App\Models\LoyaltySetting;
use App\Services\PromotionService;
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
     * Wykonuje pełny potok zapisu zamówienia oraz aktualizacji magazynu, promocji i programu lojalnościowego.
     *
     * @param array $data Dane wejściowe
     * @return Order
     * @throws Exception
     */
    public function execute(array $data): Order
    {
        return DB::transaction(function () use ($data) {
            
            $onlineMethods = ['online', 'blik', 'payu'];
            $isOnlinePayment = in_array($data['payment_method'] ?? 'gotówka', $onlineMethods);

            // Stan startowy: zamówienie gotówkowe przechodzi do "nowe"
            $initialStatus = $isOnlinePayment ? 'oczekuje na płatność' : 'nowe';

            // 1. Tworzymy zamówienie w bazie z zachowaniem numeru telefonu
            $order = Order::create([
                'tracking_token'   => Str::random(32),
                'user_id'          => $data['user_id'] ?? null,
                'phone'            => $data['phone'] ?? null,
                'type'             => $data['type'],
                'status'           => $initialStatus,
                'payment_method'   => $data['payment_method'] ?? 'gotówka',
                'payment_status'   => $data['payment_status'] ?? 'nieopłacone',
                'delivery_address' => $data['delivery_address'] ?? null,
                'delivery_zone_id' => $data['delivery_zone_id'] ?? null,
                'total_price'      => 0.00,
            ]);

            $totalPrice = 0.00;
            $itemsForPromo = [];

            // 2. Przetwarzamy pozycje z koszyka
            foreach ($data['items'] as $itemData) {
                $variant = ProductVariant::with('ingredients')->findOrFail($itemData['product_variant_id']);
                
                $subtotal = $variant->price * $itemData['quantity'];
                $totalPrice += $subtotal;

                $orderItem = $order->items()->create([
                    'product_variant_id' => $variant->id,
                    'quantity'           => $itemData['quantity'],
                    'subtotal'           => $subtotal,
                ]);

                // Tablica wygenerowana na potrzeby przeliczania silnika promocji
                $itemsForPromo[] = [
                    'product_variant_id' => $variant->id,
                    'quantity'           => $itemData['quantity'],
                    'price'              => (float) $variant->price,
                ];

                $this->processInventory($variant, $itemData['quantity'], $itemData['modifiers'] ?? [], $orderItem, $data);
            }

            // 3. SILNIK AUTOMATYCZNYCH PROMOCJI (PromotionService)
            $promoService = new PromotionService();
            $promoResult = $promoService->calculatePromotions($itemsForPromo);
            $automaticDiscount = (float) ($promoResult['discount_amount'] ?? 0.00);

            // Jeśli promocja przydziela darmowe pozycje (gratisy), dopisujemy je do bazy zamówienia z kwotą 0.00 zł
            if (!empty($promoResult['free_items'])) {
                foreach ($promoResult['free_items'] as $freeItem) {
                    $order->items()->create([
                        'product_variant_id' => $freeItem['product_variant_id'],
                        'quantity'           => $freeItem['quantity'],
                        'subtotal'           => 0.00,
                    ]);
                }
            }

            // 4. Obsługa ręcznych kodów rabatowych
            $discountAmount = 0.00;

            if (!empty($data['discount_code'])) {
                $code = strtoupper(trim($data['discount_code']));
                $discount = DiscountCode::where('code', $code)
                    ->where('is_active', true)
                    ->first();

                if ($discount && ($totalPrice >= $discount->min_order_amount)) {
                    if (!$discount->expires_at || $discount->expires_at->isFuture()) {
                        $discountAmount = $discount->calculateDiscount($totalPrice);
                        $discount->increment('times_used');
                    }
                }
            }

            // Kwota rabatu lojalnościowego
            $loyaltyDiscount = (float) ($data['loyalty_discount'] ?? 0.00);

            // Ostateczna kwota do zapłaty z uwzględnieniem automatycznych promocji
            $finalPrice = max(0.00, $totalPrice - $automaticDiscount - $discountAmount - $loyaltyDiscount);

            $order->update(['total_price' => $finalPrice]);

            // 5. PROGRAM LOJALNOŚCIOWY – NALICZANIE I REJESTRACJA PUNKTÓW
            if (!empty($data['phone'])) {
                $cleanPhone = preg_replace('/[^0-9]/', '', $data['phone']);
                if (strlen($cleanPhone) >= 9) {
                    $setting = LoyaltySetting::first();
                    $earnRate = $setting ? (float) $setting->earn_rate : 1.0;
                    $pointsEarned = (int) floor($finalPrice * $earnRate);

                    $loyaltyAccount = LoyaltyAccount::firstOrCreate(
                        ['phone' => $cleanPhone],
                        ['points' => 0]
                    );

                    // Jeśli użyto punktów lojalnościowych, odejmujemy je z konta
                    if ($loyaltyDiscount > 0 && $setting && $setting->redemption_rate > 0) {
                        $pointsRedeemed = (int) ceil($loyaltyDiscount * $setting->redemption_rate);
                        $loyaltyAccount->points = max(0, $loyaltyAccount->points - $pointsRedeemed);
                    }

                    // Dodajemy nowe punkty za zrealizowane zamówienie
                    $loyaltyAccount->points += $pointsEarned;
                    $loyaltyAccount->save();
                }
            }

            // 6. OBSŁUGA DOSTAWY, GEOLOKALIZACJI I KIEROWCY
            if ($order->type === 'dostawa' && !empty($order->delivery_address)) {
                $geocoder = new GeocodingService();
                $coords = $geocoder->geocodeAddress($order->delivery_address);

                if ($coords) {
                    $order->update([
                        'lat' => $coords['lat'],
                        'lng' => $coords['lng'],
                    ]);
                }

                // Bez względu na wynik geokodowania przypisujemy strefę i kierowcę
                $routeService = new RouteOptimizationService();
                $routeService->assignZoneAndDriver($order);

                if ($order->driver_id) {
                    $routeService->optimizeDriverRoute($order->driver_id);
                }
            }

            // 7. URUCHOMIENIE PROCESÓW (KDS / Drukarki / Redisa)
            if (!$isOnlinePayment) {
                event(new OrderPlaced($order));
                ProcessOrderJob::dispatch($order);
                PrintKitchenTicketJob::dispatch($order);
            }

            return $order;
        });
    }

    /**
     * Obsługa Silnika Receptur i Blokad Pesymistycznych
     */
    private function processInventory(ProductVariant $variant, int $quantity, array $modifiers, OrderItem $orderItem, array $orderData = []): void
    {
        $excludedIngredientIds = [];
        foreach ($modifiers as $mod) {
            if ($mod['action'] === 'REMOVE') {
                $excludedIngredientIds[] = $mod['ingredient_id'];
                
                $orderItem->modifiers()->create([
                    'ingredient_id' => $mod['ingredient_id'],
                    'action'        => 'REMOVE',
                ]);
            }
        }

        foreach ($variant->ingredients as $ingredient) {
            if (in_array($ingredient->id, $excludedIngredientIds)) {
                continue;
            }

            $lockedIngredient = Ingredient::where('id', $ingredient->id)
                ->lockForUpdate()
                ->first();

            if (!$lockedIngredient) {
                continue;
            }

            $amountNeeded = $ingredient->pivot->amount_needed * $quantity;
            $currentStock = $lockedIngredient->stock_local;

            if ($currentStock < $amountNeeded) {
                $isEcommerce = ($orderData['source'] ?? '') === 'ecommerce' 
                    || ($orderData['type'] ?? '') === 'dostawa'
                    || request()->input('type') === 'dostawa'
                    || request()->routeIs('api.*'); 

                $isForcedByWaiter = request()->input('ignore_stock') === true 
                    || ($orderData['ignore_stock'] ?? false) === true;

                if ($isEcommerce || $isForcedByWaiter) {
                    $lockedIngredient->update(['stock_local' => 0]);
                } else {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'missing_ingredient' => $lockedIngredient->name
                    ]);
                }
            } else {
                $lockedIngredient->decrement('stock_local', $amountNeeded);
            }
        }

        foreach ($modifiers as $mod) {
            if ($mod['action'] === 'ADD') {
                $lockedIngredient = Ingredient::where('id', $mod['ingredient_id'])
                    ->lockForUpdate()
                    ->first();

                $extraAmount = 0.05 * $quantity;

                if ($lockedIngredient->stock_local < $extraAmount) {
                    throw new Exception("Brak surowca na dodatek EXTRA: {$lockedIngredient->name}. Wycofuję zamówienie.");
                }

                $lockedIngredient->decrement('stock_local', $extraAmount);

                $orderItem->modifiers()->create([
                    'ingredient_id' => $mod['ingredient_id'],
                    'action'        => 'ADD',
                ]);
            }
        }
    }
}