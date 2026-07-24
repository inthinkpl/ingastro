<?php

namespace database\seeders;

use App\Models\Ingredient;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Seeder;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Dodajemy składniki do magazynu
        $cheese = Ingredient::create([
            'name' => 'Ser Mozzarella',
            'stock_quantity' => 10.00, // 10 kg
            'unit' => 'kg',
            'purchase_price' => 25.00,
            'min_limit' => 2.00,
        ]);

        $sauce = Ingredient::create([
            'name' => 'Sos Pomidorowy',
            'stock_quantity' => 15.00, // 15 litrów
            'unit' => 'l',
            'purchase_price' => 8.00,
            'min_limit' => 3.00,
        ]);

        // 2. Dodajemy produkt (Pizza Margherita)
        $pizza = Product::create([
            'name' => 'Pizza Margherita',
            'category' => 'pizza',
        ]);

        // 3. Tworzymy warianty (Rozmiary)
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

        // 4. Definiujemy receptury (spinamy warianty ze składnikami)
        // Mała pizza: 150g sera (0.15 kg) i 100ml sosu (0.10 l)
        $variantSmall->ingredients()->attach([
            $cheese->id => ['amount_needed' => 0.15],
            $sauce->id => ['amount_needed' => 0.10],
        ]);

        // Duża pizza: 250g sera (0.25 kg) i 150ml sosu (0.15 l)
        $variantBig->ingredients()->attach([
            $cheese->id => ['amount_needed' => 0.25],
            $sauce->id => ['amount_needed' => 0.15],
        ]);

        // 6. Konta testowe personelu restauracji
        User::create([
            'name' => 'Jan Admin',
            'email' => 'admin@savona.test',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        User::create([
            'name' => 'Marek Manager',
            'email' => 'manager@savona.test',
            'password' => Hash::make('password'),
            'role' => 'manager',
        ]);

        User::create([
            'name' => 'Krzysztof Kucharz',
            'email' => 'chef@savona.test',
            'password' => Hash::make('password'),
            'role' => 'chef',
        ]);

        // 5. Globalne ustawienia pizzerii
        Setting::create(['key' => 'restaurant_name', 'value' => 'Pizzeria Savona']);
        Setting::create(['key' => 'restaurant_address', 'value' => 'ul. Legionowa 10, Białystok']);
        Setting::create(['key' => 'restaurant_phone', 'value' => '+48 85 123 45 67']);
    }
}