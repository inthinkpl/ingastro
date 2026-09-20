<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_zones', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // np. "Strefa 1 - Centrum", "Strefa 2 - Obrzeża"
            $table->decimal('price', 8, 2)->default(0.00); // Koszt dostawy (np. 8.00 zł)
            $table->decimal('min_order_price', 8, 2)->default(0.00); // Min. wartość koszyka
            $table->decimal('free_delivery_from', 8, 2)->nullable(); // Darmowa dostawa od kwoty (np. 60.00 zł)
            $table->integer('max_distance_km')->nullable(); // Maksymalny promień strefy w km
            
            // Domyślnie przypisany kierowca dla tej strefy (opcjonalny)
            $table->foreignId('default_driver_id')->nullable()->constrained('users')->nullOnDelete();
            
            $table->string('color_code')->default('#f97316'); // Kolor strefy na mapie (np. pomarańczowy, niebieski)
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_zones');
    }
};