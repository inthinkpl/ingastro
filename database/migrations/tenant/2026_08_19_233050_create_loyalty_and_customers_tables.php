<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Tabela Klientów
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 20)->unique()->index(); // Znormalizowany numer telefonu (np. +48785555455)
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->decimal('points_balance', 10, 2)->default(0); // Aktualne saldo punktów
            $table->decimal('total_spent', 10, 2)->default(0);    // Całkowita suma wydata w pizzerii
            $table->integer('total_orders')->default(0);          // Całkowita liczba zamówień
            $table->text('default_address')->nullable();
            $table->timestamp('last_order_at')->nullable();
            $table->timestamps();
        });

        // 2. Tabela Historii Transakcji Punktowych
        Schema::create('loyalty_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->enum('type', ['EARNED', 'SPENT', 'MANUAL_ADD', 'MANUAL_SUBTRACT']);
            $table->decimal('points', 10, 2);                      // Liczba punktów (+ lub -)
            $table->string('description')->nullable();            // Opis, np. "Za zamówienie #1024"
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete(); // Dla ręcznych korekt menedżera
            $table->timestamps();
        });

        // 3. Tabela Ustawień Programu Lojalnościowego
        Schema::create('loyalty_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('enabled')->default(true);
            $table->decimal('earn_rate', 8, 2)->default(1.00);   // Ile punktów klient otrzymuje za 1 zł (np. 1.00 = 1 pkt / 1 zł)
            $table->decimal('point_value', 8, 2)->default(0.10);  // Wartość 1 punktu w złotówkach (np. 0.10 zł = 10 pkt daje 1 zł)
            $table->decimal('min_points_to_redeem', 8, 2)->default(50.00); // Minimum punktów wymagane do użycia rabatu
            $table->timestamps();
        });

        // Wstawiamy domyślny rekord ustawień
        DB::table('loyalty_settings')->insert([
            'enabled' => true,
            'earn_rate' => 1.00,
            'point_value' => 0.10,
            'min_points_to_redeem' => 50.00,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loyalty_transactions');
        Schema::dropIfExists('loyalty_settings');
        Schema::dropIfExists('customers');
    }
};