<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // pracownik (POS) lub zalogowany klient (WWW)
            $table->enum('type', ['lokal', 'wynos', 'dostawa']);
            $table->enum('status', ['nowe', 'w trakcie', 'gotowe', 'w drodze', 'dostarczone'])->default('nowe');
            $table->string('payment_method'); // np. BLIK, karta, gotowka
            $table->enum('payment_status', ['nieopłacone', 'opłacone'])->default('nieopłacone');
            $table->text('delivery_address')->nullable(); // adres wymagany tylko przy dostawie
            $table->decimal('total_price', 10, 2)->default(0.00);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};