<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ingredient_product_variant', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->foreignId('ingredient_id')->constrained('ingredients')->cascadeOnDelete();
            
            // Ilość surowca zużywana w tym konkretnym wariancie (np. 0.150 kg sera dla Pizzy 32cm)
            $table->decimal('quantity', 10, 3); 
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ingredient_product_variant');
    }
};