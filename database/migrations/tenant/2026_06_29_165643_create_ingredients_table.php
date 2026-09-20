<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ingredients', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // np. Ser Mozzarella
            
            // Stany magazynowe (10, 3 pozwala na dokładność do 1g / 1ml, np. 0.150 kg)
            $table->decimal('stock_main', 10, 3)->default(0.000);     // Stan w Magazynie Głównym
            $table->decimal('stock_local', 10, 3)->default(0.000);    // Stan w Magazynie Lokalnym (kuchnia)
            $table->decimal('min_stock_local', 10, 3)->default(0.000); // Minimum logistyczne dla kuchni
            
            $table->string('unit', 20)->default('kg');                 // kg, l, szt
            $table->decimal('purchase_price', 8, 2)->nullable()->default(0.00); // Zabezpieczenie NOT NULL
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ingredients');
    }
};