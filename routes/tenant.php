<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Services\PromotionService;
use App\Models\Promotion;

// IMPORTY KONTROLERÓW GLOBALNYCH
use App\Http\Controllers\ShopController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\DiscountController;
use App\Http\Controllers\Shop\LoyaltyShopController;

// IMPORTY KONTROLERÓW PANELU ERP
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\DiscountCodeController;
use App\Http\Controllers\Admin\RolePermissionController;
use App\Http\Controllers\Admin\OrderAdminController;
use App\Http\Controllers\Admin\WarehouseController;

use App\Http\Controllers\Manager\ProductController;
use App\Http\Controllers\Manager\DeliveryZoneController;
use App\Http\Controllers\Manager\LoyaltyAdminController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\DriverDeliveryController;
use App\Http\Controllers\PushSubscriptionController;
use App\Http\Controllers\RcpController; 
use App\Http\Controllers\BomController;
use App\Http\Controllers\Manager\PromotionController;
use App\Http\Controllers\Central\CentralSubscriptionController;
use App\Http\Controllers\Central\SubscriptionInvoiceController;
use App\Http\Controllers\SubscriptionController;

use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

/*
|--------------------------------------------------------------------------
| TENANT ROUTES (Wszystkie trasy pojedynczego lokalu)
|--------------------------------------------------------------------------
*/
Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | 🔑 TRASY AUTORYZACJI TENANTA (LOGIN, LOGOUT, LOGOWANIE)
    |--------------------------------------------------------------------------
    */
    require __DIR__.'/auth.php';

    /*
    |--------------------------------------------------------------------------
    | 🖼️ WYIZOLOWANY STORAGE ASSETÓW TENANTA
    |--------------------------------------------------------------------------
    */
    Route::get('/tenantasset/{path}', function ($path) {
        $tenantId = tenant('id');
        $filePath = storage_path("tenant_{$tenantId}/app/public/{$path}");

        if (!file_exists($filePath)) {
            abort(404);
        }

        return response()->file($filePath);
    })->where('path', '.*')->name('tenant.asset');

    /*
    |--------------------------------------------------------------------------
    | 1. STRONA PUBLICZNA SKLEPU & FINANSE (ZABEZPIECZONE MODUŁEM E-COMMERCE 'shop')
    |--------------------------------------------------------------------------
    */
    Route::post('/push/subscribe', [PushSubscriptionController::class, 'subscribe'])->name('push.subscribe');

    Route::middleware(['ecommerce.active', 'feature:shop'])->group(function () {
        Route::get('/', [ShopController::class, 'index'])->name('shop.index');
        Route::get('/menu', [ShopController::class, 'menu'])->name('shop.menu');
        Route::post('/order/store', [OrderController::class, 'store'])->name('order.store');

        // 🏷️ AUTOMATYCZNE PROMOCJE W KOSZYKU E-COMMERCE
        Route::get('/promotions', function () {
            return response()->json(Promotion::where('is_active', true)->with('rewardVariant.product')->get());
        })->name('promotions.public');

        Route::post('/promotions/calculate', function (Request $request, PromotionService $promotionService) {
            $cartItems = $request->input('items', []);
            $result = $promotionService->calculatePromotions($cartItems);
            return response()->json($result);
        })->name('promotions.calculate');

        // 🎁 PUBLICZNE TRASY PROGRAMU LOJALNOŚCIOWEGO (WERYFIKACJA W KOSZYKU)
        Route::prefix('loyalty')->name('loyalty.')->group(function () {
            Route::post('/check-points', [LoyaltyShopController::class, 'checkPoints'])->name('check');
            Route::post('/send-otp', [LoyaltyShopController::class, 'sendOtp'])->name('send-otp');
            Route::post('/verify-otp', [LoyaltyShopController::class, 'verifyOtp'])->name('verify-otp');
        });

        Route::prefix('payment')->group(function () {
            Route::get('/process/{order}', [PaymentController::class, 'process'])->name('payment.process');
            Route::get('/simulation/{order}', [PaymentController::class, 'simulationView'])->name('payment.simulation.view');
            Route::post('/simulation/{order}/confirm', [PaymentController::class, 'simulationConfirm'])->name('payment.simulation.confirm');

            // Zunifikowany produkcyjny Webhook
            Route::post('/webhook', [PaymentController::class, 'webhook'])->name('payment.webhook');
            Route::post('/payu/webhook', [\App\Services\Payment\drivers\PayUDriver::class, 'verify'])->name('payment.payu.webhook');

            Route::get('/order/status/{token}', [ShopController::class, 'orderStatus'])->name('order.status');
            Route::post('/discount/validate', [DiscountController::class, 'validateCode'])->name('discount.validate');
        });
    });

    /*
    |--------------------------------------------------------------------------
    | 2. SYSTEM ERP - TRASY ZABEZPIECZONE PANELU PRACOWNICZEGO
    |--------------------------------------------------------------------------
    */
    Route::middleware(['auth', 'verified'])->group(function () {

        Route::get('/dashboard', function () { return Inertia::render('Dashboard'); })->name('dashboard');

        /*
         * ─── 💳 INICJALIZACJA PŁATNOŚCI ZA SUBSKRYPCJĘ SAAS DLA TENANTA (A LA CARTE) ───
         */
        Route::middleware(['role:admin'])->group(function () {
            // Trasa POST dla zapytań Axios/AJAX ze skomponowanym zestawem modułów
            Route::post('/subscription/checkout', [CentralSubscriptionController::class, 'checkoutStripe'])->name('tenant.subscription.checkout');
        });

        /*
         * ─── ⏱️ REJESTRACJA CZASU PRACY (RCP) - SZYBKIE AKCJE DLA UŻYTKOWNIKÓW ───
         */
        Route::middleware(['feature:rcp'])->group(function () {
            Route::post('/rcp/clock-in', [RcpController::class, 'clockIn'])->name('rcp.clock-in');
            Route::post('/rcp/toggle-pause', [RcpController::class, 'togglePause'])->name('rcp.toggle-pause');
            Route::post('/rcp/clock-out', [RcpController::class, 'clockOut'])->name('rcp.clock-out');
        });

        /*
         * ─── MODUŁ ADMINISTRATORA I USTAWIEŃ GLOBALNYCH ───
         */
        Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
            Route::get('/orders', [OrderAdminController::class, 'index'])->middleware('permission:orders.view')->name('orders.index');
            Route::patch('/orders/{order}/status', [OrderAdminController::class, 'updateStatus'])->middleware('permission:orders.view')->name('orders.update-status');
        });

        Route::middleware(['role:admin,manager'])->prefix('admin')->name('admin.')->group(function () {

            // 📄 Pobieranie Faktury Subskrypcyjnej PDF
            Route::get('/subscription/invoice/{invoice}/download', [SubscriptionInvoiceController::class, 'download'])->name('subscription.invoice.download');

            // ⏱️ Rejestracja Czasu Pracy (RCP)
            Route::middleware(['feature:rcp'])->group(function () {
                Route::get('/rcp', [RcpController::class, 'index'])->middleware('permission:rcp.view')->name('rcp.index');
                Route::post('/rcp', [RcpController::class, 'store'])->middleware('permission:rcp.view')->name('rcp.store');
                Route::put('/rcp/{shift}', [RcpController::class, 'update'])->middleware('permission:rcp.view')->name('rcp.update');
            });

            // Główne Ustawienia Globalne
            Route::get('/settings', [SettingsController::class, 'edit'])->middleware('permission:settings.general')->name('settings.edit');
            Route::post('/settings', [SettingsController::class, 'save'])->middleware('permission:settings.general')->name('settings.save');
            Route::put('/settings/notifications', [SettingsController::class, 'updateNotifications'])->middleware('permission:settings.general')->name('settings.notifications.update');

            // 🔐 Zapis Macierzy Uprawnień Ról
            Route::post('/permissions', [UserController::class, 'updatePermissions'])->name('permissions.update');

            // 👥 Zarządzanie Zespołem
            Route::middleware('permission:users.manage')->group(function () {
                Route::get('/users', [UserController::class, 'index'])->name('users.index');
                Route::post('/users', [UserController::class, 'store'])->name('users.store');
                Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
                Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
            });

            // 🎟️ Trasy Zarządzania Kodami Rabatowymi
            Route::middleware('permission:promotions.manage')->group(function () {
                Route::post('/discount-codes', [DiscountCodeController::class, 'store'])->name('discount-codes.store');
                Route::patch('/discount-codes/{discountCode}/toggle', [DiscountCodeController::class, 'toggle'])->name('discount-codes.toggle');
                Route::delete('/discount-codes/{discountCode}', [DiscountCodeController::class, 'destroy'])->name('discount-codes.destroy');
            });
        });

        /*
         * ─── MODUŁ MENEDŻERA ───
         */
        Route::middleware(['role:manager,admin'])->prefix('manager')->name('manager.')->group(function () {

            // 📊 Dashboard BI / Finansowy
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
                        $stock = $ing->stock_main ?? $ing->stock ?? $ing->amount ?? $ing->quantity ?? 0;
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
            })->middleware('permission:dashboard.financial')->name('dashboard');

            // 🏷️ Zarządzanie Promocjami
            Route::middleware('permission:promotions.manage')->group(function () {
                Route::get('/promotions', [PromotionController::class, 'index'])->name('promotions.index');
                Route::post('/promotions', [PromotionController::class, 'store'])->name('promotions.store');
                Route::patch('/promotions/{promotion}/toggle', [PromotionController::class, 'toggle'])->name('promotions.toggle');
                Route::delete('/promotions/{promotion}', [PromotionController::class, 'destroy'])->name('promotions.destroy');
            });

            // 🍕 Kreator produktów karty dań i receptury BOM
            Route::middleware('permission:products.manage')->group(function () {
                Route::get('/products', [ProductController::class, 'index'])->name('products.index');
                Route::post('/products', [ProductController::class, 'store'])->name('products.store');
                Route::put('/products/{product}', [ProductController::class, 'update'])->name('products.update');
                Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');

                Route::post('/products/{product}/variants', [ProductController::class, 'storeVariant'])->name('products.variants.store');
                Route::delete('/products/variants/{variant}', [ProductController::class, 'destroyVariant'])->name('products.variants.destroy');

                Route::middleware(['feature:inventory_bom'])->group(function () {
                    Route::post('/products/variants/{variant}/recipe', [ProductController::class, 'saveRecipe'])->name('products.save_recipe');
                    Route::post('/bom/save-variant-recipe', [BomController::class, 'saveVariantRecipe'])->name('bom.save-variant-recipe');
                });
            });

            // 📦 Gospodarka magazynowa surowców
            Route::middleware(['permission:inventory.manage', 'feature:inventory_bom'])->group(function () {
                Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory');
                Route::post('/inventory', [InventoryController::class, 'store'])->name('inventory.store');
                Route::put('/inventory/{ingredient}', [InventoryController::class, 'update'])->name('inventory.update');
                Route::delete('/inventory/{ingredient}', [InventoryController::class, 'destroy'])->name('inventory.destroy');
                Route::post('/inventory/{ingredient}/restock', [InventoryController::class, 'restock'])->name('restock');

                Route::get('/warehouse', [WarehouseController::class, 'index'])->name('warehouse.index');
                Route::post('/warehouse/transfer', [WarehouseController::class, 'transfer'])->name('warehouse.transfer');
            });

            // 🎁 Program Lojalnościowy i CRM Klientów
            Route::middleware(['permission:loyalty.manage', 'feature:loyalty'])->group(function () {
                Route::get('/loyalty', [LoyaltyAdminController::class, 'index'])->name('loyalty.index');
                Route::put('/loyalty/settings', [LoyaltyAdminController::class, 'updateSettings'])->name('loyalty.settings.update');
                Route::post('/loyalty/customers/{customer}/adjust', [LoyaltyAdminController::class, 'adjustPoints'])->name('loyalty.adjust');
            });

            // 💵 Rozliczanie gotówki kurierów
            Route::middleware(['permission:reconciliation.view', 'feature:delivery'])->group(function () {
                Route::get('/reconciliation', [DriverDeliveryController::class, 'managerIndex'])->name('reconciliation.index');
                Route::post('/reconciliation/settle/{driver}', [DriverDeliveryController::class, 'settleDriver'])->name('reconciliation.settle');
            });

            // 🗺️ Zarządzanie strefami dostaw
            Route::middleware(['permission:delivery_zones.manage', 'feature:delivery'])->group(function () {
                Route::get('/delivery-zones', [DeliveryZoneController::class, 'index'])->name('delivery_zones.index');
                Route::post('/delivery-zones', [DeliveryZoneController::class, 'store'])->name('delivery_zones.store');
                Route::put('/delivery-zones/{deliveryZone}', [DeliveryZoneController::class, 'update'])->name('delivery_zones.update');
                Route::delete('/delivery-zones/{deliveryZone}', [DeliveryZoneController::class, 'destroy'])->name('delivery_zones.destroy');
            });

            Route::post('/orders/{order}/assign-driver', [DriverDeliveryController::class, 'assignDriver'])->middleware(['permission:orders.view', 'feature:delivery'])->name('orders.assign_driver');
        });

        /*
         * ─── MODUŁ KUCHNI (CHEF / KDS) ───
         */
        Route::middleware(['role:chef,admin,manager', 'feature:kds'])->group(function () {
            Route::get('/kds', [OrderController::class, 'kds'])->name('kds.index');
            Route::match(['post', 'put', 'patch'], '/order/{order}/update-status', [OrderController::class, 'updateStatus'])->name('order.updateStatus');
            Route::match(['post', 'put', 'patch'], '/order/{order}/next-status', [OrderController::class, 'updateStatus'])->name('order.nextStatus');
        });

        /*
         * ─── MODUŁ SALI / KASY (STAFF / POS) ───
         */
        Route::middleware(['role:staff,admin,manager', 'feature:pos'])->prefix('pos')->name('pos.')->group(function () {
            Route::get('/', [OrderController::class, 'pos'])->name('index');
        });

        /*
         * ─── ZABEZPIECZONY SEKTOR KIEROWCÓW (DRIVER) ───
         */
        Route::middleware(['role:driver,manager,admin', 'feature:delivery'])->prefix('driver')->name('driver.')->group(function () {
            Route::get('/dashboard', [DriverDeliveryController::class, 'index'])->name('dashboard');
            Route::post('/orders/{order}/take', [DriverDeliveryController::class, 'takeOrder'])->name('take');
            Route::post('/orders/{order}/pickup', [DriverDeliveryController::class, 'pickupOrder'])->name('pickup');
            Route::post('/orders/{order}/complete', [DriverDeliveryController::class, 'completeOrder'])->name('complete');
        });

        Route::get('/pos-panel-compatibility-alias', function() { return redirect()->route('pos.index'); })->name('order.pos');
    });

});