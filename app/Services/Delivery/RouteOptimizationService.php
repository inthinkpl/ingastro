<?php

namespace App\Services\Delivery;

use App\Models\DeliveryZone;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class RouteOptimizationService
{
    private float $pizzeriaLat;
    private float $pizzeriaLng;

    public function __construct()
    {
        $this->pizzeriaLat = (float) config('app.pizzeria_lat', 53.1325);
        $this->pizzeriaLng = (float) config('app.pizzeria_lng', 23.1533);
    }

    /**
     * Wzór Haversine: Wylicza odległość w kilometrach między dwoma punktami GPS.
     */
    public function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371; // Promień Ziemi w km

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round($earthRadius * $c, 2); // Zwraca dystans w km z dokładnością do 2 miejsc
    }

    /**
     * Automatycznie przypisuje Strefę Dostaw oraz domyślnego Kierowcę na podstawie współrzędnych zamówienia.
     */
    public function assignZoneAndDriver(Order $order): void
    {
        if (!$order->lat || !$order->lng) {
            return;
        }

        $distanceKm = $this->calculateDistance($this->pizzeriaLat, $this->pizzeriaLng, $order->lat, $order->lng);

        // Szukamy najmniejszej pasującej strefy dla wyliczonego dystansu
        $zone = DeliveryZone::where('is_active', true)
            ->where('max_distance_km', '>=', $distanceKm)
            ->orderBy('max_distance_km', 'asc')
            ->first();

        if ($zone) {
            $order->delivery_zone_id = $zone->id;
            
            // Jeśli strefa ma przypisanego domyślnego kierowcę, przypisujemy go
            if ($zone->default_driver_id) {
                $order->driver_id = $zone->default_driver_id;
            }
            
            $order->save();
        }
    }

    /**
     * ALGORYTM TRASY (Nearest Neighbor): Sortuje aktywne zamówienia kierowcy w kolejności 1, 2, 3...
     */
    public function optimizeDriverRoute(int $driverId): void
    {
        // Pobieramy nieukończone zamówienia dostawcze przypisane do tego kierowcy
        $orders = Order::where('driver_id', $driverId)
            ->where('type', 'dostawa')
            ->whereIn('status', ['nowe', 'w_przygotowaniu', 'gotowe', 'w_dostawie'])
            ->whereNotNull('lat')
            ->whereNotNull('lng')
            ->get();

        if ($orders->isEmpty()) {
            return;
        }

        $unvisited = $orders->all();
        $currentLat = $this->pizzeriaLat;
        $currentLng = $this->pizzeriaLng;
        $sequence = 1;

        // Dopóki mamy nieodwiedzone punkty, szukamy najbliższego względem aktualnej pozycji
        while (!empty($unvisited)) {
            $nearestKey = null;
            $minDistance = INF;

            foreach ($unvisited as $key => $ord) {
                $dist = $this->calculateDistance($currentLat, $currentLng, (float)$ord->lat, (float)$ord->lng);
                if ($dist < $minDistance) {
                    $minDistance = $dist;
                    $nearestKey = $key;
                }
            }

            if ($nearestKey !== null) {
                $nextOrder = $unvisited[$nearestKey];
                
                // Zapisujemy kolejna pozycję w sekwencji tras
                $nextOrder->update(['route_sequence' => $sequence]);
                
                // Przesuwamy wirtualny punkt startowy na adres tego doręczonego właśnie zamówienia
                $currentLat = (float) $nextOrder->lat;
                $currentLng = (float) $nextOrder->lng;

                unset($unvisited[$nearestKey]);
                $sequence++;
            }
        }
    }
}