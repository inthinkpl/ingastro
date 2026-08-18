<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Ingredient;
use App\Models\SystemSetting;
use Inertia\Inertia;

class ShopController extends Controller
{
    public function index()
    {
        $products = Product::with('variants.ingredients')
            ->where('is_active', true)
            ->orderBy('category')
            ->get();

        $minOrderAmount = (float) SystemSetting::get('min_order_amount', 40.00);

        $freeDeliverySettings = [
            'enabled'   => filter_var(SystemSetting::get('free_delivery_enabled', '0'), FILTER_VALIDATE_BOOLEAN),
            'minAmount' => (float) SystemSetting::get('free_delivery_min_amount', 60.00),
        ];

        $upsellSettings = [
            'enabled'   => filter_var(SystemSetting::get('upsell_enabled', '1'), FILTER_VALIDATE_BOOLEAN),
        ];

        return Inertia::render('Shop/Index', [
            'products'             => $products,
            'minOrderAmount'       => $minOrderAmount,
            'freeDeliverySettings' => $freeDeliverySettings,
            'upsellSettings'       => $upsellSettings,
        ]);
    }

    public function orderStatus(string $token)
    {
        $order = \App\Models\Order::with([
                'items.variant.product',
                'items.modifiers.ingredient',
                'deliveryZone'
            ])
            ->where('tracking_token', $token)
            ->firstOrFail();

        $phone = SystemSetting::get('restaurant_phone', '+48 500 600 700');

        return Inertia::render('Shop/OrderStatus', [
            'order'           => $order,
            'restaurantPhone' => $phone,
        ]);
    }

    public function menu()
    {
        $products = Product::with(['variants.ingredients'])
            ->where('is_active', true)
            ->orderBy('category')
            ->get();

        $ingredients = Ingredient::all();
        $minOrderAmount = (float) SystemSetting::get('min_order_amount', 40.00);

        $freeDeliverySettings = [
            'enabled'   => filter_var(SystemSetting::get('free_delivery_enabled', '0'), FILTER_VALIDATE_BOOLEAN),
            'minAmount' => (float) SystemSetting::get('free_delivery_min_amount', 60.00),
        ];

        $upsellSettings = [
            'enabled'   => filter_var(SystemSetting::get('upsell_enabled', '1'), FILTER_VALIDATE_BOOLEAN),
        ];

        return Inertia::render('Shop/Menu.vue', [
            'products'             => $products,
            'ingredients'          => $ingredients,
            'minOrderAmount'       => $minOrderAmount,
            'freeDeliverySettings' => $freeDeliverySettings,
            'upsellSettings'       => $upsellSettings,
        ]);
    }
}