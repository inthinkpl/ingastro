<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('ingredients', function (Blueprint $table) {
            // Zmiana nazwy starej kolumny na stock_main
            if (Schema::hasColumn('ingredients', 'stock_quantity')) {
                $table->renameColumn('stock_quantity', 'stock_main');
            } elseif (Schema::hasColumn('ingredients', 'stock')) {
                $table->renameColumn('stock', 'stock_main');
            }

            // Dodanie kolumn dla Magazynu Lokalnego
            if (!Schema::hasColumn('ingredients', 'stock_local')) {
                $table->decimal('stock_local', 10, 3)->default(0)->after('unit');
            }
            if (!Schema::hasColumn('ingredients', 'min_stock_local')) {
                $table->decimal('min_stock_local', 10, 3)->default(5.000)->after('stock_local');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ingredients', function (Blueprint $table) {
            if (Schema::hasColumn('ingredients', 'stock_main')) {
                $table->renameColumn('stock_main', 'stock_quantity');
            }
            $table->dropColumn(['stock_local', 'min_stock_local']);
        });
    }
};