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
        Schema::create('order_modifiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ingredient_id')->constrained();
            $table->enum('action', ['ADD', 'REMOVE']); // typ akcji z Twojej specyfikacji
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_modifiers');
    }
};
