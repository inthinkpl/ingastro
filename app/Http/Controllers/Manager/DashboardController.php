<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Ingredient;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        // 1. Całkowity utarg (zamówienia gotowe/zrealizowane)
        $totalRevenue = Order::sum('total_price');

        // 2. Liczba zamówień według statusu
        $ordersCount = Order::count();
        
        // 3. Statystyki typów zamówień (lokal, wynos, dostawa)
        $orderTypes = Order::select('type', DB::raw('count(*) as count'))
            ->groupBy('type')
            ->get()
            ->pluck('count', 'type')
            ->toArray();

        // 4. Finansowa wycena aktualnego magazynu (stan * cena zakupu)
        $ingredients = Ingredient::all();
        $warehouseValue = $ingredients->reduce(function ($carry, $ingredient) {
            return $carry + ($ingredient->stock_quantity * $ingredient->purchase_price);
        }, 0);

        // 5. TOP 5 najlepiej sprzedających się wariantów produktów
        $topProducts = OrderItem::select('product_variant_id', DB::raw('sum(quantity) as total_qty'))
            ->with('variant.product')
            ->groupBy('product_variant_id')
            ->orderBy('total_qty', 'desc')
            ->take(5)
            ->get()
            ->map(function ($item) {
                return [
                    'name' => ($item->variant && $item->variant->product) 
                        ? $item->variant->product->name . ' (' . $item->variant->size_name . ')' 
                        : 'Produkt usunięty',
                    'qty' => $item->total_qty
                ];
            });

        return Inertia::render('Manager/Dashboard', [
            'stats' => [
                'total_revenue' => round($totalRevenue, 2),
                'total_orders' => $ordersCount,
                'warehouse_value' => round($warehouseValue, 2),
                'types' => [
                    'lokal' => $orderTypes['lokal'] ?? 0,
                    'wynos' => $orderTypes['wynos'] ?? 0,
                    'dostawa' => $orderTypes['dostawa'] ?? 0,
                ],
                'top_products' => $topProducts
            ]
        ]);
    }
}