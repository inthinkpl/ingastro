<?php

namespace App\Services;

use App\Models\Promotion;
use App\Models\ProductVariant;

class PromotionService
{
    /**
     * Oblicza rabaty oraz przydziela gratisy na podstawie zaawansowanych warunków.
     */
    public function calculatePromotions(array $cartItems): array
    {
        if (empty($cartItems)) {
            return [
                'discount_amount' => 0.00,
                'free_items'      => [],
                'applied_promo'   => null,
            ];
        }

        // Wczytujemy warianty wraz z kategoriami produktów z bazy dla precyzyjnej weryfikacji
        $variantIds = array_column($cartItems, 'product_variant_id');
        $variantsDB = ProductVariant::with('product')->whereIn('id', $variantIds)->get()->keyBy('id');

        $cartSubtotal = 0.00;
        $enrichedItems = [];

        foreach ($cartItems as $item) {
            $vId = $item['product_variant_id'];
            $dbVar = $variantsDB->get($vId);

            $unitPrice = $dbVar ? (float)$dbVar->price : (float)($item['price'] ?? 0);
            $qty = (int)($item['quantity'] ?? 1);
            $categoryName = strtolower($dbVar?->product?->category ?? '');
            $sizeName = strtolower($dbVar?->size_name ?? '');

            $cartSubtotal += ($unitPrice * $qty);

            $enrichedItems[] = [
                'product_variant_id' => $vId,
                'quantity'           => $qty,
                'price'              => $unitPrice,
                'category'           => $categoryName,
                'size_name'          => $sizeName,
                'name'               => $dbVar?->product?->name . ' (' . $dbVar?->size_name . ')',
            ];
        }

        $activePromotions = Promotion::where('is_active', true)->with('rewardVariant.product')->get();

        $bestDiscount = 0.00;
        $bestFreeItems = [];
        $appliedPromo = null;

        foreach ($activePromotions as $promo) {
            // 1. Sprawdzamy minimalną kwotę koszyka
            if ($promo->min_order_amount > 0 && $cartSubtotal < $promo->min_order_amount) {
                continue;
            }

            // 2. Weryfikujemy kryteria kategoryczne i rozmiarowe
            $matchingQuantity = 0;
            $matchingPrices = [];

            foreach ($enrichedItems as $item) {
                $categoryMatches = true;
                $sizeMatches = true;

                if ($promo->required_category && $promo->required_category !== 'all') {
                    $categoryMatches = str_contains($item['category'], strtolower($promo->required_category));
                }

                if ($promo->required_size_name && $promo->required_size_name !== 'Dowolny') {
                    $sizeMatches = str_contains($item['size_name'], strtolower($promo->required_size_name));
                }

                if ($categoryMatches && $sizeMatches) {
                    $matchingQuantity += $item['quantity'];
                    for ($i = 0; $i < $item['quantity']; $i++) {
                        $matchingPrices[] = $item['price'];
                    }
                }
            }

            // 3. Sprawdzamy, czy wymagana ilość spełniających dań znajduje się w koszyku
            $requiredQty = $promo->min_quantity > 0 ? $promo->min_quantity : 1;
            if ($matchingQuantity < $requiredQty) {
                continue;
            }

            // 4. Wyliczamy wartość rabatu/gratisu
            $currentDiscount = 0.00;
            $currentFreeItems = [];

            if ($promo->type === 'amount_discount') {
                $currentDiscount = (float) $promo->value;
            } elseif ($promo->type === 'percent_discount') {
                if ($promo->discount_target === 'cheapest_item' && !empty($matchingPrices)) {
                    sort($matchingPrices); // Bierzemy najtańszą spośród spełniających wymogi
                    $cheapest = $matchingPrices[0];
                    $currentDiscount = round($cheapest * ($promo->value / 100), 2);
                } else {
                    $currentDiscount = round($cartSubtotal * ($promo->value / 100), 2);
                }
            } elseif ($promo->type === 'free_product' && $promo->rewardVariant) {
                $currentFreeItems[] = [
                    'product_variant_id' => $promo->reward_variant_id,
                    'name'               => $promo->rewardVariant->product->name . ' (' . $promo->rewardVariant->size_name . ')',
                    'quantity'           => 1,
                    'price'              => 0.00,
                ];
            }

            // Wybieramy najkorzystniejszą regułę dla klienta
            if ($currentDiscount > $bestDiscount || (!empty($currentFreeItems) && empty($bestFreeItems))) {
                $bestDiscount = $currentDiscount;
                $bestFreeItems = $currentFreeItems;
                $appliedPromo = $promo;
            }
        }

        return [
            'discount_amount' => min($bestDiscount, $cartSubtotal),
            'free_items'      => $bestFreeItems,
            'applied_promo'   => $appliedPromo ? $appliedPromo->name : null,
        ];
    }
}