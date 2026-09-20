<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ingredient_variant', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ingredient_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount_needed', 10, 2); // np. 0.15 dla 150g sera na pizzę 32cm
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ingredient_variant');
    }
};
