<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('mysql')->create('email_templates', function (Blueprint $table) {
            $table->id();
            $table->string('event_key')->unique(); // np. 'welcome_tenant'
            $table->string('name'); // Nazwa dla Super Admina
            $table->string('subject'); // Temat maila
            $table->text('body'); // Treść HTML z tagami {zmienna}
            $table->text('available_variables'); // JSON z opisem dostepnych zmiennych
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('mysql')->dropIfExists('email_templates');
    }
};