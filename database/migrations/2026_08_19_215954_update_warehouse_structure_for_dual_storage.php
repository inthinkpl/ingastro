<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1. Modyfikacja tabeli składników
        Schema::table('ingredients', function (Blueprint $table) {
            // Zmiana nazwy dotychczasowego pola 'stock' na 'stock_main' (Magazyn Główny)
            if (Schema::hasColumn('ingredients', 'stock')) {
                $table->renameColumn('stock', 'stock_main');
            }

            // Dodanie kolumn dla Magazynu Lokalnego (w lokalu)
            $table->decimal('stock_local', 10, 3)->default(0)->after('unit');
            $table->decimal('min_stock_local', 10, 3)->default(5.000)->after('stock_local');
        });

        // 2. Tabela rejestrująca historię przesunięć MM
        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ingredient_id')->constrained('ingredients')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('quantity', 10, 3);
            $table->string('unit', 20);
            $table->string('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_transfers');
        Schema::table('ingredients', function (Blueprint $table) {
            $table->renameColumn('stock_main', 'stock');
            $table->dropColumn(['stock_local', 'min_stock_local']);
        });
    }
};