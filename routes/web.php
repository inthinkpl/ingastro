<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// IMPORTY KONTROLERÓW GLOBALNYCH
use App\Http\Controllers\ShopController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\DiscountController;

// IMPORTY KONTROLERÓW PANELU ERP
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\DiscountCodeController;
use App\Http\Controllers\Admin\RolePermissionController;
use App\Http\Controllers\Admin\OrderAdminController;

use App\Http\Controllers\Manager\ProductController;
use App\Http\Controllers\Manager\DeliveryZoneController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\DriverDeliveryController;
use App\Http\Controllers\PushSubscriptionController;
use App\Http\Controllers\RcpController; // 🔥 Import kontrolera RCP


/*
|--------------------------------------------------------------------------
| 1. STRONA PUBLICZNA SKLEPU & FINANSE
|--------------------------------------------------------------------------
*/
Route::post('/push/subscribe', [PushSubscriptionController::class, 'subscribe'])->name('push.subscribe');
Route::get('/', [ShopController::class, 'index'])->name('shop.index');
Route::post('/order/store', [OrderController::class, 'store'])->name('order.store');

Route::prefix('payment')->group(function () {
    Route::get('/process/{order}', [PaymentController::class, 'process'])->name('payment.process');
    Route::get('/simulation/{order}', [PaymentController::class, 'simulationView'])->name('payment.simulation.view');
    Route::post('/simulation/{order}/confirm', [PaymentController::class, 'simulationConfirm'])->name('payment.simulation.confirm');
    
    // Zunifikowany produkcyjny Webhook (Dla PayU, Tpay oraz automatyzacji procesów w tle)
    Route::post('/webhook', [PaymentController::class, 'webhook'])->name('payment.webhook');
    Route::post('/payu/webhook', [\App\Services\Payment\drivers\PayUDriver::class, 'verify'])->name('payment.payu.webhook');
    
    Route::get('/order/status/{token}', [ShopController::class, 'orderStatus'])->name('order.status');
    Route::post('/discount/validate', [DiscountController::class, 'validateCode'])->name('discount.validate');
});

/*
|--------------------------------------------------------------------------
| 2. SYSTEM ERP - TRASY ZABEZPIECZONE PANELU PRACOWNICZEGO
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified'])->group(function () {
    
    Route::get('/dashboard', function () { return Inertia::render('Dashboard'); })->name('dashboard');

    /*
     * ─── ⏱️ REJESTRACJA CZASU PRACY (RCP) - SZYBKIE AKCJE DLA KAŻDEGO PRACOWNIKA ───
     */
    Route::post('/rcp/clock-in', [RcpController::class, 'clockIn'])->name('rcp.clock-in');
    Route::post('/rcp/toggle-pause', [RcpController::class, 'togglePause'])->name('rcp.toggle-pause');
    Route::post('/rcp/clock-out', [RcpController::class, 'clockOut'])->name('rcp.clock-out');

    /*
     * ─── MODUŁ ADMINISTRATORA I USTAWIEŃ GLOBALNYCH (Dostęp: Admin + Manager) ───
     */
    Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
        Route::get('/orders', [OrderAdminController::class, 'index'])->name('orders.index');
        Route::patch('/orders/{order}/status', [OrderAdminController::class, 'updateStatus'])->name('orders.update-status');
    });


    Route::middleware(['role:admin,manager'])->prefix('admin')->name('admin.')->group(function () {
        
        // ⏱️ Rejestracja Czasu Pracy (RCP) - Panel Ewidencji i Korekt
        Route::get('/rcp', [RcpController::class, 'index'])->name('rcp.index');
        Route::post('/rcp', [RcpController::class, 'store'])->name('rcp.store');
        Route::put('/rcp/{shift}', [RcpController::class, 'update'])->name('rcp.update');

        // Główne Ustawienia (Wizytówka, Powiadomienia Push, Bramki Płatności)
        Route::get('/settings', [SettingsController::class, 'edit'])->name('settings.edit');
        Route::post('/settings', [SettingsController::class, 'save'])->name('settings.save');
        Route::put('/settings/notifications', [SettingsController::class, 'updateNotifications'])->name('settings.notifications.update');
        
        // 🔐 Zapis Macierzy Uprawnień Ról
        Route::post('/permissions', [RolePermissionController::class, 'update'])->name('permissions.update');

        // 👥 Zarządzanie Zespołem (Zabezpieczone nowym uprawnieniem RBAC)
        Route::middleware('permission:users.manage')->group(function () {
            Route::get('/users', [UserController::class, 'index'])->name('users.index');
            Route::post('/users', [UserController::class, 'store'])->name('users.store');
            Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
            Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
        });

        // 🎟️ Trasy Zarządzania Kodami Rabatowymi (Zabezpieczone nowym uprawnieniem RBAC)
        Route::middleware('permission:settings.discounts')->group(function () {
            Route::post('/discount-codes', [DiscountCodeController::class, 'store'])->name('discount-codes.store');
            Route::patch('/discount-codes/{discountCode}/toggle', [DiscountCodeController::class, 'toggle'])->name('discount-codes.toggle');
            Route::delete('/discount-codes/{discountCode}', [DiscountCodeController::class, 'destroy'])->name('discount-codes.destroy');
        });
    });

    /*
     * ─── MODUŁ MENEDŻERA (Zarządzanie pizzerią) ───
     */
    Route::middleware(['role:manager,admin'])->prefix('manager')->name('manager.')->group(function () {
        
        // 📊 Dashboard BI (Dostępny dla managera z urzędu)
        Route::get('/dashboard', function () { 
            $totalRevenue = 0;
            try {
                $totalRevenue = \App\Models\Order::where('payment_status', 'opłacone')->sum('total_price') 
                    ?? \App\Models\Order::where('payment_status', 'opłacone')->sum('total') 
                    ?? 0;
            } catch (\Exception $e) { $totalRevenue = 0; }

            $totalOrders = 0;
            try { $totalOrders = \App\Models\Order::count(); } catch (\Exception $e) { $totalOrders = 0; }

            $warehouseValue = 0;
            try {
                foreach (\App\Models\Ingredient::all() as $ing) {
                    $stock = $ing->stock ?? $ing->amount ?? $ing->quantity ?? 0;
                    $price = $ing->price ?? $ing->purchase_price ?? $ing->cost ?? 0;
                    $warehouseValue += ($stock * $price);
                }
            } catch (\Exception $e) { $warehouseValue = 0; }

            $types = ['lokal' => 0, 'wynos' => 0, 'dostawa' => 0];
            try {
                $types['lokal'] = \App\Models\Order::where('type', 'lokal')->count();
                $types['wynos'] = \App\Models\Order::where('type', 'wynos')->count();
                $types['dostawa'] = \App\Models\Order::where('type', 'dostawa')->count();
            } catch (\Exception $e) {}

            $topProducts = [];
            try {
                $topItems = \Illuminate\Support\Facades\DB::table('order_items')
                    ->join('product_variants', 'order_items.product_variant_id', '=', 'product_variants.id')
                    ->join('products', 'product_variants.product_id', '=', 'products.id')
                    ->select('products.name', \Illuminate\Support\Facades\DB::raw('SUM(order_items.quantity) as qty'))
                    ->groupBy('products.id', 'products.name')
                    ->orderBy('qty', 'desc')->take(5)->get();
                foreach ($topItems as $item) {
                    $topProducts[] = ['name' => $item->name, 'qty' => (int)$item->qty];
                }
            } catch (\Exception $e) { $topProducts = []; }

            return Inertia::render('Manager/Dashboard', [
                'stats' => [
                    'total_revenue'   => (float)$totalRevenue,
                    'total_orders'    => (int)$totalOrders,
                    'warehouse_value' => (float)$warehouseValue,
                    'types'           => $types,
                    'top_products'    => $topProducts
                ]
            ]); 
        })->name('dashboard');
        
        // 🍕 Kreator produktów karty dań (CRUD) - Wymaga uprawnienia 'products.manage'
        Route::middleware('permission:products.manage')->group(function () {
            Route::get('/products', [ProductController::class, 'index'])->name('products.index');
            Route::post('/products', [ProductController::class, 'store'])->name('products.store');
            Route::put('/products/{product}', [ProductController::class, 'update'])->name('products.update');
            Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');
            Route::post('/products/variants/{variant}/recipe', [ProductController::class, 'saveRecipe'])->name('products.save_recipe');
        });
        
        // 📦 Gospodarka magazynowa surowców - Wymaga uprawnienia 'inventory.manage'
        Route::middleware('permission:inventory.manage')->group(function () {
            Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory');
            Route::post('/inventory', [InventoryController::class, 'store'])->name('inventory.store');
            Route::put('/inventory/{ingredient}', [InventoryController::class, 'update'])->name('inventory.update');
            Route::delete('/inventory/{ingredient}', [InventoryController::class, 'destroy'])->name('inventory.destroy');
            Route::post('/inventory/{ingredient}/restock', [InventoryController::class, 'restock'])->name('restock');
        });

        // 💵 Rozliczanie gotówki kurierów - Wymaga uprawnienia 'reconciliation.view'
        Route::middleware('permission:reconciliation.view')->group(function () {
            Route::get('/reconciliation', [DriverDeliveryController::class, 'managerIndex'])->name('reconciliation.index');
            Route::post('/reconciliation/settle/{driver}', [DriverDeliveryController::class, 'settleDriver'])->name('reconciliation.settle');
        });

        // 🗺️ Zarządzanie strefami dostaw - Wymaga uprawnienia 'delivery_zones.manage'
        Route::middleware('permission:delivery_zones.manage')->group(function () {
            Route::get('/delivery-zones', [DeliveryZoneController::class, 'index'])->name('delivery_zones.index');
            Route::post('/delivery-zones', [DeliveryZoneController::class, 'store'])->name('delivery_zones.store');
            Route::put('/delivery-zones/{deliveryZone}', [DeliveryZoneController::class, 'update'])->name('delivery_zones.update');
            Route::delete('/delivery-zones/{deliveryZone}', [DeliveryZoneController::class, 'destroy'])->name('delivery_zones.destroy');
        });

        // Ręczne przypisywanie kierowcy przez menedżera
        Route::post('/orders/{order}/assign-driver', [DriverDeliveryController::class, 'assignDriver'])->name('orders.assign_driver');
    });

    /*
     * ─── MODUŁ KUCHNI (CHEF / KDS) ───
     */
    Route::middleware(['role:chef,admin,manager'])->group(function () {
        Route::get('/kds', [OrderController::class, 'kds'])->name('kds.index');
        Route::match(['post', 'put', 'patch'], '/order/{order}/update-status', [OrderController::class, 'updateStatus'])->name('order.updateStatus');
        Route::match(['post', 'put', 'patch'], '/order/{order}/next-status', [OrderController::class, 'updateStatus'])->name('order.nextStatus');
    });

    /*
     * ─── MODUŁ SALI / KASY (STAFF / POS) ───
     */
    Route::middleware(['role:staff,admin,manager'])->prefix('pos')->name('pos.')->group(function () {
        Route::get('/', [OrderController::class, 'pos'])->name('index');
    });

    /*
     * ─── ZABEZPIECZONY SEKTOR KIEROWCÓW (DRIVER) ───
     */
    Route::middleware(['role:driver,manager,admin'])->prefix('driver')->name('driver.')->group(function () {
        Route::get('/dashboard', [DriverDeliveryController::class, 'index'])->name('dashboard');
        Route::post('/orders/{order}/take', [DriverDeliveryController::class, 'takeOrder'])->name('take');
        Route::post('/orders/{order}/pickup', [DriverDeliveryController::class, 'pickupOrder'])->name('pickup');
        Route::post('/orders/{order}/complete', [DriverDeliveryController::class, 'completeOrder'])->name('complete');
    });

    Route::get('/pos-panel-compatibility-alias', function() { return redirect()->route('pos.index'); })->name('order.pos');
});

require __DIR__.'/auth.php';