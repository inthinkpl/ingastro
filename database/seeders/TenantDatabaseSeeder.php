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
        // 1. Składniki w magazynie lokalu
        $cheese = Ingredient::create([
            'name' => 'Ser Mozzarella',
            'unit' => 'kg',
            'stock_main' => 10.000,
            'stock_local' => 5.000,
            'min_stock_local' => 2.000,
            'purchase_price' => 25.00, // 👈 Jawnie przekazujemy cenę zakupu
        ]);

        $sauce = Ingredient::create([
            'name' => 'Sos Pomidorowy',
            'unit' => 'l',
            'stock_main' => 15.000,
            'stock_local' => 8.000,
            'min_stock_local' => 3.000,
            'purchase_price' => 8.00, // 👈 Jawnie przekazujemy cenę zakupu
        ]);

        // 2. Produkt (Pizza Margherita)
        $pizza = Product::create([
            'name' => 'Pizza Margherita',
            'category' => 'pizza',
        ]);

        // 3. Warianty (Rozmiary)
        $variantSmall = ProductVariant::create([
            'product_id' => $pizza->id,
            'size_name' => 'Mała (32cm)',
            'price' => 29.99,
        ]);

        $variantBig = ProductVariant::create([
            'product_id' => $pizza->id,
            'size_name' => 'Duża (42cm)',
            'price' => 39.99,
        ]);

        // 4. Receptury BOM
        $variantSmall->ingredients()->attach([
            $cheese->id => ['amount_needed' => 0.150],
            $sauce->id => ['amount_needed' => 0.100],
        ]);

        $variantBig->ingredients()->attach([
            $cheese->id => ['amount_needed' => 0.250],
            $sauce->id => ['amount_needed' => 0.150],
        ]);

        // 5. Konta personelu lokalu
        $tenantDomain = tenant('id') ?? 'savona';

        User::firstOrCreate(
            ['email' => 'admin@' . $tenantDomain . '.localhost'],
            [
                'name' => 'Jan Admin',
                'password' => Hash::make('password'),
                'role' => 'admin',
            ]
        );

        User::firstOrCreate(
            ['email' => 'manager@' . $tenantDomain . '.localhost'],
            [
                'name' => 'Marek Manager',
                'password' => Hash::make('password'),
                'role' => 'manager',
            ]
        );

        User::firstOrCreate(
            ['email' => 'chef@' . $tenantDomain . '.localhost'],
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