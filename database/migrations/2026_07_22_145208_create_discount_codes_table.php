<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discount_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // np. SAVONA10
            $table->enum('type', ['percent', 'fixed'])->default('percent'); // percent = %, fixed = PLN
            $table->decimal('value', 8, 2); // wartość (np. 10.00 dla 10% lub 10 zł)
            $table->decimal('min_order_amount', 8, 2)->default(0.00); // Wymagany min. koszyk
            $table->dateTime('expires_at')->nullable(); // Data wygaśnięcia
            $table->boolean('is_active')->default(true);
            $table->integer('times_used')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discount_codes');
    }
};