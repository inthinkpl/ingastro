<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique(); // np. 'payment_gateway'
            $table->text('value')->nullable(); // np. 'simulation' lub klucze API w JSON
            $table->timestamps();
        });

        // Wstrzykujemy domyślne ustawienie startowe na symulację
        DB::table('system_settings')->insert([
            'key' => 'payment_gateway',
            'value' => 'simulation',
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};