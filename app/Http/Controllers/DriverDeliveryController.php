<?php

namespace App\Http\Controllers;

use App\Events\OrderStatusUpdated;
use App\Models\Order;
use App\Models\User;
use App\Services\Delivery\RouteOptimizationService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DriverDeliveryController extends Controller
{
    /**
     * Wyświetla aktywną trasę kierowcy oraz wszystkie zamówienia z dostawą.
     */
    public function index()
    {
        $user = auth()->user();

        // Pobieramy wszystkie aktywne zamówienia w procesie dostawy
        $query = Order::with('deliveryZone')
            ->where('type', 'dostawa')
            ->whereIn('status', ['nowe', 'w_przygotowaniu', 'gotowe', 'w_dostawie']);

        // Menedżer widzi wszystkie dostawy; Kierowca widzi przypisane do siebie lub jeszcze nieprzypisane
        if ($user->role === 'driver') {
            $query->where(function ($q) use ($user) {
                $q->where('driver_id', $user->id)
                  ->orWhereNull('driver_id');
            });
        }

        $orders = $query->orderBy('route_sequence', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        return Inertia::render('Driver/Dashboard', [
            'orders' => $orders,
            'pizzeria_coords' => [
                'lat' => (float) config('app.pizzeria_lat', 53.1325),
                'lng' => (float) config('app.pizzeria_lng', 23.1533),
            ]
        ]);
    }

    /**
     * Odbiór paczki z pizzerii / rozpoczęcie kursu.
     */
    public function pickupOrder(Order $order)
    {
        $driverId = $order->driver_id ?? auth()->id();

        $order->update([
            'status'    => 'w_dostawie',
            'driver_id' => $driverId,
        ]);

        if ($driverId) {
            $routeService = new RouteOptimizationService();
            $routeService->optimizeDriverRoute($driverId);
        }

        event(new OrderStatusUpdated($order));

        return redirect()->back()->with('success', "Zamówienie #{$order->id} odebrane z kuchni.");
    }

    /**
     * Oznaczenie zamówienia jako dostarczone do klienta.
     */
    public function completeOrder(Order $order)
    {
        $order->update([
            'status'         => 'dostarczone',
            'payment_status' => 'opłacone',
        ]);

        if ($order->driver_id) {
            $routeService = new RouteOptimizationService();
            $routeService->optimizeDriverRoute($order->driver_id);
        }

        event(new OrderStatusUpdated($order));

        return redirect()->back()->with('success', "Zamówienie #{$order->id} zostało pomyślnie dostarczone.");
    }

    /**
     * Ręczne przypisanie kierowcy przez Menedżera.
     */
    public function assignDriver(Request $request, Order $order)
    {
        $validated = $request->validate([
            'driver_id' => 'required|exists:users,id',
        ]);

        $order->update([
            'driver_id' => $validated['driver_id']
        ]);

        $routeService = new RouteOptimizationService();
        $routeService->optimizeDriverRoute($validated['driver_id']);

        event(new OrderStatusUpdated($order));

        return redirect()->back()->with('success', "Zamówienie #{$order->id} zostało przypisane do kierowcy.");
    }

    /**
     * Widok rozliczenia kasetki gotówkowej u Menedżera.
     */
    public function managerIndex()
    {
        $drivers = User::where('role', 'driver')
            ->with(['orders' => function ($q) {
                $q->where('type', 'dostawa')
                  ->where('status', 'dostarczone')
                  ->where('payment_method', 'gotówka')
                  ->where('payment_status', 'opłacone');
            }])
            ->get()
            ->map(function ($driver) {
                $cashAmount = $driver->orders->sum('total_price');
                return [
                    'id'             => $driver->id,
                    'name'           => $driver->name,
                    'email'          => $driver->email,
                    'unsettled_cash' => (float) $cashAmount,
                    'orders_count'   => $driver->orders->count(),
                ];
            });

        return Inertia::render('Manager/Reconciliation', [
            'drivers' => $drivers
        ]);
    }

    /**
     * Zerowanie stanu kasetki kierowcy.
     */
    public function settleDriver(User $driver)
    {
        Order::where('driver_id', $driver->id)
            ->where('type', 'dostawa')
            ->where('status', 'dostarczone')
            ->where('payment_method', 'gotówka')
            ->update(['payment_status' => 'rozliczone_menedżer']);

        return redirect()->back()->with('success', "Gotówka kierowcy {$driver->name} została rozliczona.");
    }
}