<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SystemModule;

class SystemModuleSeeder extends Seeder
{
    public function run(): void
    {
        $modules = [
            [
                'key'           => 'pos',
                'name'          => 'Kasa POS & Sprzedaż w Lokalu',
                'description'   => 'Szybkie przyjmowanie zamówień, obsługa stolików i bonowanie.',
                'price_monthly' => 69.00,
                'sort_order'    => 1,
            ],
            [
                'key'           => 'shop',
                'name'          => 'Sklep E-Commerce Online',
                'description'   => 'Własny system zamówień online bez prowizji od sprzedaży.',
                'price_monthly' => 79.00,
                'sort_order'    => 2,
            ],
            [
                'key'           => 'kds',
                'name'          => 'Ekran Kuchenny (KDS)',
                'description'   => 'Elektroniczne bony dla kucharzy z czasem przygotowania dań.',
                'price_monthly' => 49.00,
                'sort_order'    => 3,
            ],
            [
                'key'           => 'delivery',
                'name'          => 'Moduł & Aplikacja dla Kurierów',
                'description'   => 'Strefy dostaw, GPS i rozliczanie gotówki kierowców.',
                'price_monthly' => 59.00,
                'sort_order'    => 4,
            ],
            [
                'key'           => 'inventory_bom',
                'name'          => 'Magazyn & Receptury BOM',
                'description'   => 'Automatyczne schodzenie surowców ze stanu po sprzedaży.',
                'price_monthly' => 49.00,
                'sort_order'    => 5,
            ],
            [
                'key'           => 'loyalty',
                'name'          => 'Program Lojalnościowy & Kody',
                'description'   => 'Zbieranie punktów przez klientów i automatyczne rabaty.',
                'price_monthly' => 39.00,
                'sort_order'    => 6,
            ],
            [
                'key'           => 'rcp',
                'name'          => 'Rejestracja Czasu Pracy (RCP)',
                'description'   => 'Ewidencja godzin pracy, pauzy i wyliczanie wypłat.',
                'price_monthly' => 29.00,
                'sort_order'    => 7,
            ],
        ];

        foreach ($modules as $module) {
            SystemModule::updateOrCreate(
                ['key' => $module['key']],
                $module
            );
        }
    }
}