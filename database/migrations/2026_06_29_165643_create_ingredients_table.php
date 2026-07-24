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
            $table->decimal('stock_quantity', 10, 2)->default(0.00); // aktualna ilość w magazynie
            $table->string('unit'); // kg, l, szt
            $table->decimal('purchase_price', 8, 2); // cena zakupu za jednostkę
            $table->decimal('min_limit', 10, 2)->default(5.00); // minimum logistyczne
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ingredients');
    }
};