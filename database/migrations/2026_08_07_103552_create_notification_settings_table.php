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
    Schema::create('notification_settings', function (Blueprint $table) {
        $table->id();
        $table->string('status_key')->unique(); // np. 'in_kitchen', 'in_oven', 'delivering', 'ready'
        $table->string('status_label');         // Czytelna nazwa w panelu np. 'W przygotowaniu'
        $table->string('title_template');       // np. "Pizza w piecu! 🔥"
        $table->text('body_template');          // np. "Cześć {name}, Twoje zamówienie #{order_id} wlasnie trafiło do pieca."
        $table->timestamps();
    });

    // Wypełnienie domyślnymi wartościami
    DB::table('notification_settings')->insert([
        [
            'status_key' => 'in_kitchen',
            'status_label' => 'W przygotowaniu (KDS)',
            'title_template' => 'Kucharz przyjął zamówienie! 👨‍🍳',
            'body_template' => 'Cześć {name}! Zamówienie #{order_id} trafiło na blat kuchenny.',
        ],
        [
            'status_key' => 'in_oven',
            'status_label' => 'W piecu',
            'title_template' => 'Twój placek piecze się w piecu! 🔥',
            'body_template' => 'Zamówienie #{order_id} nabiera chrupkości. Już niedługo wyjeżdża!',
        ],
        [
            'status_key' => 'delivering',
            'status_label' => 'W dostawie (Kurier)',
            'title_template' => 'Kurier w drodze! 🚚',
            'body_template' => 'Jedzenie jedzie pod adres: {address}. Szykuj talerze!',
        ],
        [
            'status_key' => 'ready',
            'status_label' => 'Gotowe do odbioru',
            'title_template' => 'Zamówienie gotowe! 🍕',
            'body_template' => 'Twoja pizza czeka na odbiór w pizzerii.',
        ],
    ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_settings');
    }
};
