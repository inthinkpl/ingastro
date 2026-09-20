<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        // Wstrzykujemy pełny zestaw startowy ustawień lokalu
        DB::table('system_settings')->insert([
            [
                'key' => 'restaurant_name',
                'value' => 'Pizzeria Savona',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'payment_gateway',
                'value' => 'simulation', // simulation, payu, przelewy24
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'currency',
                'value' => 'PLN',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'tax_rate',
                'value' => '8', // domyślny VAT na gastronomię
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'is_open',
                'value' => '1', // status otwarcia lokalu (1 = otwarty, 0 = zamknięty)
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'delivery_enabled',
                'value' => '1',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'min_order_amount',
                'value' => '40.00',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};