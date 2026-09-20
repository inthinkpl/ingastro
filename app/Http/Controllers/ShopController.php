<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Ingredient;
use App\Models\SystemSetting;
use Inertia\Inertia;

class ShopController extends Controller
{
    private function formatProductImage($product)
    {
        if ($product->image) {
            $path = ltrim($product->image, '/');
            if (str_starts_with($path, 'storage/')) {
                $path = substr($path, 8);
            }
            // Zwraca pełny URL np. http://napoli.localhost/tenantasset/products/pizza.jpg
            $product->image = tenant_asset($path);
        }
        return $product;
    }

    public function index()
    {
        $products = Product::with(['variants' => function($query) {
                $query->where('is_active', true);
            }, 'variants.ingredients'])
            ->where('is_active', true)
            ->orderBy('category')
            ->get()
            ->map(fn($p) => $this->formatProductImage($p));

        return Inertia::render('Shop/Index', [
            'products'             => $products,
            'minOrderAmount'       => (float) SystemSetting::get('min_order_amount', 40.00),
            'freeDeliverySettings' => [
                'enabled'   => filter_var(SystemSetting::get('free_delivery_enabled', '1'), FILTER_VALIDATE_BOOLEAN),
                'minAmount' => (float) SystemSetting::get('free_delivery_min_amount', 60.00),
            ],
            'upsellSettings'       => [
                'enabled'   => filter_var(SystemSetting::get('upsell_enabled', '1'), FILTER_VALIDATE_BOOLEAN),
            ],
            'halfHalfSettings'     => [
                'enabled'   => filter_var(SystemSetting::get('half_half_enabled', '1'), FILTER_VALIDATE_BOOLEAN),
            ],
        ]);
    }

    public function menu()
    {
        $products = Product::with(['variants' => function($query) {
                $query->where('is_active', true);
            }, 'variants.ingredients'])
            ->where('is_active', true)
            ->orderBy('category')
            ->get()
            ->map(fn($p) => $this->formatProductImage($p));

        return Inertia::render('Shop/Menu', [
            'products'             => $products,
            'ingredients'          => Ingredient::all(),
            'minOrderAmount'       => (float) SystemSetting::get('min_order_amount', 40.00),
            'freeDeliverySettings' => [
                'enabled'   => filter_var(SystemSetting::get('free_delivery_enabled', '1'), FILTER_VALIDATE_BOOLEAN),
                'minAmount' => (float) SystemSetting::get('free_delivery_min_amount', 60.00),
            ],
            'upsellSettings'       => [
                'enabled'   => filter_var(SystemSetting::get('upsell_enabled', '1'), FILTER_VALIDATE_BOOLEAN),
            ],
            'halfHalfSettings'     => [
                'enabled'   => filter_var(SystemSetting::get('half_half_enabled', '1'), FILTER_VALIDATE_BOOLEAN),
            ],
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

        $order->items->each(function ($item) {
            if (optional($item->variant->product)->image) {
                $item->variant->product = $this->formatProductImage($item->variant->product);
            }
        });

        return Inertia::render('Shop/OrderStatus', [
            'order'           => $order,
            'restaurantPhone' => SystemSetting::get('restaurant_phone', '+48 500 600 700'),
        ]);
    }
}