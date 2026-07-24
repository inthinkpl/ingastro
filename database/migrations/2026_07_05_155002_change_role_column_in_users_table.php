<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            // 🔥 POPRAWKA: Zmieniamy typ z ENUM na standardowy string z domyślną wartością
            $table->string('role')->default('waiter')->change();
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            // W razie powrotu przywracamy stare ustawienie
            $table->string('role')->change();
        });
    }
};