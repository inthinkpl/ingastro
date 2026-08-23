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
            $table->decimal('stock_main', 10, 2)->default(0.00);      // Stan w Magazynie Głównym
            $table->decimal('stock_local', 10, 2)->default(0.00);     // Stan w Magazynie Lokalnym (kuchnia)
            $table->decimal('min_stock_local', 10, 2)->default(0.00); // Minimum logistyczne dla kuchni
            $table->string('unit');                                   // kg, l, szt
            $table->decimal('purchase_price', 8, 2);                  // Cena zakupu za jednostkę
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ingredients');
    }
};