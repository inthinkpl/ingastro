<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('shop_settings')) {
            Schema::create('shop_settings', function (Blueprint $table) {
                $table->id();
                $table->boolean('is_ecommerce_active')->default(true);
                $table->timestamps();
            });
        } else {
            Schema::table('shop_settings', function (Blueprint $table) {
                if (!Schema::hasColumn('shop_settings', 'is_ecommerce_active')) {
                    $table->boolean('is_ecommerce_active')->default(true)->after('id');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('shop_settings')) {
            Schema::table('shop_settings', function (Blueprint $table) {
                if (Schema::hasColumn('shop_settings', 'is_ecommerce_active')) {
                    $table->dropColumn('is_ecommerce_active');
                }
            });
        }
    }
};