<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('promotions', function (Blueprint $table) {
            // Zmieniamy typ kolumny type na VARCHAR(255)
            $table->string('type', 255)->change();

            if (!Schema::hasColumn('promotions', 'value')) {
                $table->decimal('value', 8, 2)->default(0.00);
            }
            if (!Schema::hasColumn('promotions', 'min_quantity')) {
                $table->integer('min_quantity')->default(1);
            }
            if (!Schema::hasColumn('promotions', 'required_category')) {
                $table->string('required_category')->nullable();
            }
            if (!Schema::hasColumn('promotions', 'required_size_name')) {
                $table->string('required_size_name')->nullable();
            }
            if (!Schema::hasColumn('promotions', 'discount_target')) {
                $table->string('discount_target')->default('cart_total');
            }
            if (!Schema::hasColumn('promotions', 'reward_variant_id')) {
                $table->foreignId('reward_variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('promotions', function (Blueprint $table) {
            // Revert
        });
    }
};