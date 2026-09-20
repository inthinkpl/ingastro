<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_permissions', function (Blueprint $table) {
            $table->id();
            $table->string('role');       // np. manager, staff, chef, driver
            $table->string('permission'); // np. settings.general, settings.discounts, settings.payments
            $table->timestamps();

            $table->unique(['role', 'permission']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_permissions');
    }
};