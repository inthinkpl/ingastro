<?php

namespace App\Services\Delivery;

use App\Models\DeliveryZone;
use App\Models\Order;

class RouteOptimizationService
{
    private float $pizzeriaLat;
    private float $pizzeriaLng;

    public function __construct()
    {
        $this->pizzeriaLat = (float) config('app.pizzeria_lat', 53.1325);
        $this->pizzeriaLng = (float) config('app.pizzeria_lng', 23.1533);
    }

    public function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371;

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round($earthRadius * $c, 2);
    }

    public function assignZoneAndDriver(Order $order): void
    {
        if (!$order->lat || !$order->lng) {
            return;
        }

        $distanceKm = $this->calculateDistance($this->pizzeriaLat, $this->pizzeriaLng, (float) $order->lat, (float) $order->lng);

        $zone = DeliveryZone::where('is_active', true)
            ->where('max_distance_km', '>=', $distanceKm)
            ->orderBy('max_distance_km', 'asc')
            ->first();

        if ($zone) {
            $order->delivery_zone_id = $zone->id;
            
            if ($zone->default_driver_id && !$order->driver_id) {
                $order->driver_id = $zone->default_driver_id;
            }
            
            $order->save();
        }
    }

    public function optimizeDriverRoute(int $driverId): void
    {
        $orders = Order::where('driver_id', $driverId)
            ->where('type', 'dostawa')
            ->whereIn('status', ['nowe', 'w_przygotowaniu', 'gotowe', 'w_dostawie'])
            ->get();

        if ($orders->isEmpty()) {
            return;
        }

        $withCoords = [];
        $withoutCoords = [];

        foreach ($orders as $ord) {
            if ($ord->lat && $ord->lng) {
                $withCoords[] = $ord;
            } else {
                $withoutCoords[] = $ord;
            }
        }

        $sequence = 1;
        $currentLat = $this->pizzeriaLat;
        $currentLng = $this->pizzeriaLng;

        while (!empty($withCoords)) {
            $nearestKey = null;
            $minDistance = INF;

            foreach ($withCoords as $key => $ord) {
                $dist = $this->calculateDistance($currentLat, $currentLng, (float) $ord->lat, (float) $ord->lng);
                if ($dist < $minDistance) {
                    $minDistance = $dist;
                    $nearestKey = $key;
                }
            }

            if ($nearestKey !== null) {
                $nextOrder = $withCoords[$nearestKey];
                $nextOrder->update(['route_sequence' => $sequence]);
                
                $currentLat = (float) $nextOrder->lat;
                $currentLng = (float) $nextOrder->lng;

                unset($withCoords[$nearestKey]);
                $sequence++;
            }
        }

        foreach ($withoutCoords as $ord) {
            $ord->update(['route_sequence' => $sequence]);
            $sequence++;
        }
    }
}