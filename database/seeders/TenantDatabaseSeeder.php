<?php

namespace Database\Seeders;

use App\Models\Ingredient;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class TenantDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $tenantDomain = tenant('id') ?? 'savona';

        // 1. Składniki w magazynie lokalu (używamy firstOrCreate, żeby nie tworzyć duplikatów)
        $cheese = Ingredient::firstOrCreate(
            ['name' => 'Ser Mozzarella'],
            [
                'unit' => 'kg',
                'stock_main' => 10.000,
                'stock_local' => 5.000,
                'min_stock_local' => 2.000,
                'purchase_price' => 25.00,
            ]
        );

        $sauce = Ingredient::firstOrCreate(
            ['name' => 'Sos Pomidorowy'],
            [
                'unit' => 'l',
                'stock_main' => 15.000,
                'stock_local' => 8.000,
                'min_stock_local' => 3.000,
                'purchase_price' => 8.00,
            ]
        );

        // 2. Produkt (Pizza Margherita)
        $pizza = Product::firstOrCreate(
            ['name' => 'Pizza Margherita'],
            ['category' => 'pizza']
        );

        // 3. Warianty (Rozmiary)
        $variantSmall = ProductVariant::firstOrCreate(
            ['product_id' => $pizza->id, 'size_name' => 'Mała (32cm)'],
            ['price' => 29.99]
        );

        $variantBig = ProductVariant::firstOrCreate(
            ['product_id' => $pizza->id, 'size_name' => 'Duża (42cm)'],
            ['price' => 39.99]
        );

        // 4. Receptury BOM (sync zamiast attach zapobiega błędom powiązań przy ponownym seedowaniu)
        $variantSmall->ingredients()->sync([
            $cheese->id => ['amount_needed' => 0.150],
            $sauce->id => ['amount_needed' => 0.100],
        ]);

        $variantBig->ingredients()->sync([
            $cheese->id => ['amount_needed' => 0.250],
            $sauce->id => ['amount_needed' => 0.150],
        ]);

        // 5. Konta personelu lokalu (dopasowane do realnej domeny tenanta)
        $domainSuffix = tenant()->domains()->first()?->domain ?? ($tenantDomain . '.ingastro.pl');

        User::firstOrCreate(
            ['email' => 'admin@' . $tenantDomain . '.pl'],
            [
                'name' => 'Jan Admin',
                'password' => Hash::make('password'),
                'role' => 'admin',
            ]
        );

        User::firstOrCreate(
            ['email' => 'manager@' . $tenantDomain . '.pl'],
            [
                'name' => 'Marek Manager',
                'password' => Hash::make('password'),
                'role' => 'manager',
            ]
        );

        User::firstOrCreate(
            ['email' => 'chef@' . $tenantDomain . '.pl'],
            [
                'name' => 'Krzysztof Kucharz',
                'password' => Hash::make('password'),
                'role' => 'chef',
            ]
        );

        // 6. Ustawienia lokalu
        $settings = [
            ['key' => 'restaurant_name', 'value' => 'Pizzeria ' . ucfirst($tenantDomain)],
            ['key' => 'restaurant_address', 'value' => 'ul. Legionowa 10, Białystok'],
            ['key' => 'restaurant_phone', 'value' => '+48 85 123 45 67'],
            ['key' => 'payment_gateway', 'value' => 'simulation'],
            ['key' => 'currency', 'value' => 'PLN'],
            ['key' => 'tax_rate', 'value' => '8'],
            ['key' => 'is_open', 'value' => '1'],
        ];

        foreach ($settings as $setting) {
            DB::table('system_settings')->updateOrInsert(
                ['key' => $setting['key']],
                ['value' => $setting['value'], 'created_at' => now(), 'updated_at' => now()]
            );
        }
    }
}