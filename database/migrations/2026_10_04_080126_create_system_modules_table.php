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
        Schema::connection('mysql')->create('system_modules', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();           // Unikalny klucz modułu, np. 'pos', 'kds', 'delivery'
            $table->string('name');                    // Czytelna nazwa, np. 'Ekran Kuchenny (KDS)'
            $table->text('description')->nullable();   // Opis funkcji modułu dla klienta
            $table->decimal('price_monthly', 8, 2)->default(0.00); // Cena miesięczna netto w PLN
            $table->boolean('is_active')->default(true); // Czy moduł jest dostępny w sprzedaży
            $table->json('requires')->nullable();      // Zależności (np. ['pos'] dla KDS)
            $table->integer('sort_order')->default(0);  // Kolejność wyświetlania w cenniku
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('mysql')->dropIfExists('system_modules');
    }
};