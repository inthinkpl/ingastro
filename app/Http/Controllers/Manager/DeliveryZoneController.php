<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\DeliveryZone;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DeliveryZoneController extends Controller
{
    /**
     * Lista stref dostaw i formularz zarządczy dla menedżera.
     */
    public function index()
    {
        $zones = DeliveryZone::with('defaultDriver')->orderBy('id', 'asc')->get();
        
        // Pobieramy wyłącznie kierowców, których menedżer może przypisać do stref
        $drivers = User::where('role', 'driver')->orderBy('name', 'asc')->get();

        return Inertia::render('Manager/DeliveryZones', [
            'zones'   => $zones,
            'drivers' => $drivers,
        ]);
    }

    /**
     * Zapis nowej strefy dostaw.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'               => 'required|string|max:255',
            'price'              => 'required|numeric|min:0',
            'min_order_price'    => 'required|numeric|min:0',
            'free_delivery_from' => 'nullable|numeric|min:0',
            'max_distance_km'    => 'nullable|integer|min:1',
            'default_driver_id'  => 'nullable|exists:users,id',
            'color_code'         => 'required|string|max:7',
            'is_active'          => 'boolean',
        ]);

        DeliveryZone::create($validated);

        return redirect()->back()->with('success', 'Strefa dostaw została pomyślnie utworzona.');
    }

    /**
     * Aktualizacja parametrów strefy.
     */
    public function update(Request $request, DeliveryZone $deliveryZone)
    {
        $validated = $request->validate([
            'name'               => 'required|string|max:255',
            'price'              => 'required|numeric|min:0',
            'min_order_price'    => 'required|numeric|min:0',
            'free_delivery_from' => 'nullable|numeric|min:0',
            'max_distance_km'    => 'nullable|integer|min:1',
            'default_driver_id'  => 'nullable|exists:users,id',
            'color_code'         => 'required|string|max:7',
            'is_active'          => 'boolean',
        ]);

        $deliveryZone->update($validated);

        return redirect()->back()->with('success', 'Strefa dostaw została zaktualizowana.');
    }

    /**
     * Usunięcie strefy.
     */
    public function destroy(DeliveryZone $deliveryZone)
    {
        $deliveryZone->delete();

        return redirect()->back()->with('success', 'Strefa dostaw została usunięta.');
    }
}