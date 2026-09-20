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
            if (Schema::hasColumn('ingredients', 'stock') && !Schema::hasColumn('ingredients', 'stock_main')) {
                $table->renameColumn('stock', 'stock_main');
            }

            // Dodanie kolumn dla Magazynu Lokalnego (tylko jeśli jeszcze nie istnieją)
            if (!Schema::hasColumn('ingredients', 'stock_local')) {
                $table->decimal('stock_local', 10, 3)->default(0)->after('unit');
            }

            if (!Schema::hasColumn('ingredients', 'min_stock_local')) {
                $table->decimal('min_stock_local', 10, 3)->default(5.000)->after('stock_local');
            }
        });

        // 2. Tabela rejestrująca historię przesunięć MM
        if (!Schema::hasTable('stock_transfers')) {
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
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_transfers');

        Schema::table('ingredients', function (Blueprint $table) {
            if (Schema::hasColumn('ingredients', 'stock_main')) {
                $table->renameColumn('stock_main', 'stock');
            }

            $columnsToDrop = array_filter(['stock_local', 'min_stock_local'], fn($col) => Schema::hasColumn('ingredients', $col));
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};