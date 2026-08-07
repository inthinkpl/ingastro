<?php

namespace App\Http\Controllers;

use App\Events\OrderStatusUpdated; // <-- Ddodany import zdarzenia WebSockets/Push
use App\Models\Order;
use App\Models\User;
use App\Services\Delivery\RouteOptimizationService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DriverDeliveryController extends Controller
{
    /**
     * Wyświetla aktywną trasę kierowcy (zamówienia oczekujące w kuchni + zamówienia w trasie).
     */
    public function index()
    {
        $user = auth()->user();

        $query = Order::with('deliveryZone')
            ->where('type', 'dostawa')
            ->whereIn('status', ['gotowe', 'w_dostawie']);

        // Jeśli zalogowany jest Menedżer lub Admin (testy), widzi WSZYSTKIE aktywne dostawy w lokalu.
        // Jeśli zwykły Kierowca, widzi zamówienia przypisane do siebie LUB jeszcze nieprzypisane do kogoś innego.
        if ($user->role === 'driver') {
            $query->where(function($q) use ($user) {
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
     * KIEROWCA KLIKA: "Odebrałem z kuchni"
     * Zmienia status z 'gotowe' na 'w_dostawie' -> Pizza znika z KDS kuchni!
     */
    public function pickupOrder(Order $order)
    {
        $order->update([
            'status'    => 'w_dostawie',
            'driver_id' => $order->driver_id ?? auth()->id(), // Przypisuje zalogowanego kierowcę jeśli nie było przypisania
        ]);

        // Przeliczamy optymalną trasę po pobraniu nowej paczki
        if ($order->driver_id) {
            $routeService = new RouteOptimizationService();
            $routeService->optimizeDriverRoute($order->driver_id);
        }

        event(new OrderStatusUpdated($order));

        return redirect()->back()->with('success', 'Odebrano pizzę z kuchni. Status zmieniony na: W dostawie.');
    }

    /**
     * KIEROWCA KLIKA: "Dostarczono do klienta"
     * Finalizacja i rozliczenie zamówienia.
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

        return redirect()->back()->with('success', 'Zamówienie dostarczone do klienta.');
    }

    /**
     * MENEDŻER: Ręczne przypisanie lub zmiana kierowcy dla danego zamówienia.
     */
    public function assignDriver(Request $request, Order $order)
    {
        $validated = $request->validate([
            'driver_id' => 'required|exists:users,id',
        ]);

        $order->update([
            'driver_id' => $validated['driver_id']
        ]);

        // Przeliczamy ciąg tras dla nowego kierowcy
        $routeService = new RouteOptimizationService();
        $routeService->optimizeDriverRoute($validated['driver_id']);

        event(new OrderStatusUpdated($order)); // Powiadomienie na żywo o przypisaniu kierowcy

        return redirect()->back()->with('success', "Zamówienie #{$order->id} zostało przypisane do nowego kierowcy.");
    }

    /**
     * MENEDŻER: Widok rozliczania gotówki kierowców (/manager/reconciliation)
     */
    public function managerIndex()
    {
        $drivers = User::where('role', 'driver')
            ->with(['orders' => function($q) {
                $q->where('type', 'dostawa')
                  ->where('status', 'dostarczone')
                  ->where('payment_method', 'gotówka')
                  ->where('payment_status', 'opłacone');
            }])
            ->get()
            ->map(function($driver) {
                $cashAmount = $driver->orders->sum('total_price');
                return [
                    'id'             => $driver->id,
                    'name'           => $driver->name,
                    'email'          => $driver->email,
                    'unsettled_cash' => (float)$cashAmount,
                    'orders_count'   => $driver->orders->count(),
                ];
            });

        return Inertia::render('Manager/Reconciliation', [
            'drivers' => $drivers
        ]);
    }

    /**
     * MENEDŻER: Rozliczenie (zerowanie kasetki) pobranej gotówki od kierowcy
     */
    public function settleDriver(User $driver)
    {
        Order::where('driver_id', $driver->id)
            ->where('type', 'dostawa')
            ->where('status', 'dostarczone')
            ->where('payment_method', 'gotówka')
            ->update(['payment_status' => 'rozliczone_menedżer']);

        return redirect()->back()->with('success', "Gotówka kierowcy {$driver->name} została rozliczona w kasetce.");
    }
}