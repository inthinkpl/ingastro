<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Plan;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        Plan::updateOrCreate(['slug' => 'starter'], [
            'name' => 'Starter',
            'price_monthly' => 149.00,
            'max_menu_items' => 30,
            'features' => ['shop', 'pos'],
            'is_active' => true,
        ]);

        Plan::updateOrCreate(['slug' => 'pro-gastro'], [
            'name' => 'Pro Gastro',
            'price_monthly' => 299.00,
            'max_menu_items' => null,
            'features' => ['shop', 'pos', 'kds', 'delivery', 'inventory_bom', 'loyalty', 'rcp'],
            'is_active' => true,
        ]);

        Plan::updateOrCreate(['slug' => 'enterprise'], [
            'name' => 'Enterprise Multi-Lokal',
            'price_monthly' => 499.00,
            'max_menu_items' => null,
            'features' => ['shop', 'pos', 'kds', 'delivery', 'inventory_bom', 'loyalty', 'rcp', 'multi_location', 'custom_domain'],
            'is_active' => true,
        ]);
    }
}