<?php

namespace App\Services\Delivery;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeocodingService
{
    /**
     * Zamienia adres tekstowy na współrzędne GPS (Lat, Lng) przy użyciu OpenStreetMap (Nominatim).
     */
    public function geocodeAddress(string $address): ?array
    {
        try {
            // Dodajemy miasto jeśli nie podano, by zwęzić wyszukiwanie
            $searchQuery = trim($address);

            $response = Http::withHeaders([
                'User-Agent' => 'SavonaEcosystemERP/1.0 (contact@pizzeriasavona.pl)'
            ])->get('https://nominatim.openstreetmap.org/search', [
                'q'      => $searchQuery,
                'format' => 'json',
                'limit'  => 1,
            ]);

            if ($response->successful() && count($response->json()) > 0) {
                $data = $response->json()[0];
                return [
                    'lat' => (float) $data['lat'],
                    'lng' => (float) $data['lon'],
                ];
            }
        } catch (\Exception $e) {
            Log::error('Błąd geokodowania adresu: ' . $e->getMessage());
        }

        return null; // Zwraca null jeśli adres nie został znaleziony
    }
}