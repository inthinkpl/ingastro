<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // np. "2x Pizza = Coca-Cola 0.5l Gratis"
            $table->enum('type', ['free_product', 'discount_percent', 'discount_fixed'])->default('free_product');
            
            // Warunki (Triggery)
            $table->integer('min_pizza_count')->default(0); // np. min 2 pizze
            $table->decimal('min_order_amount', 8, 2)->default(0.00); // np. od 80 zł
            
            // Nagroda / Benefit
            $table->foreignId('reward_product_variant_id')->nullable()->constrained('product_variants')->nullOnDelete(); // Domyślny gratis (np. Cola)
            $table->decimal('discount_value', 8, 2)->default(0.00); // np. 50 (%) lub 10 (zł)
            $table->boolean('apply_to_cheapest')->default(false); // Czy rabat dotyczy najtańszej pizzy z koszyka

            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotions');
    }
};