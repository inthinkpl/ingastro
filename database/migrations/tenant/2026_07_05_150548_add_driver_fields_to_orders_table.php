<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
public function up()
{
    Schema::table('orders', function (Blueprint $table) {
        // Powiązanie z tabelą users (kierowca to zalogowany użytkownik o roli 'driver')
        $table->foreignId('driver_id')->nullable()->constrained('users')->onDelete('set null');
        // Flaga rozliczenia finansowego na koniec dnia
        $table->boolean('is_reconciled')->default(false);
    });
}

public function down()
{
    Schema::table('orders', function (Blueprint $table) {
        $table->dropForeign(['driver_id']);
        $table->dropColumn(['driver_id', 'is_reconciled']);
    });
}

};
