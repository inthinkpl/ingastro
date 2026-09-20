<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Starter, Pro Gastro, Enterprise
            $table->string('slug')->unique();
            $table->decimal('price_monthly', 8, 2);
            $table->json('features'); // lista opcji ['kds', 'delivery', 'loyalty', 'inventory_bom', 'rcp']
            $table->integer('max_menu_items')->nullable(); // null = bez limitu
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('tenants', function (Blueprint $table) {
            $table->foreignId('plan_id')->nullable()->after('id');
            $table->timestamp('subscription_ends_at')->nullable()->after('plan_id');
            $table->string('subscription_status')->default('active')->after('subscription_ends_at'); // active, expired, cancelled
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['plan_id', 'subscription_ends_at', 'subscription_status']);
        });
        Schema::dropIfExists('plans');
    }
};