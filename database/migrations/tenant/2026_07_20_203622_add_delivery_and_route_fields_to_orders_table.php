<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'delivery_zone_id')) {
                $table->foreignId('delivery_zone_id')->nullable()->after('delivery_address')->constrained('delivery_zones')->nullOnDelete();
            }
            if (!Schema::hasColumn('orders', 'lat')) {
                $table->decimal('lat', 10, 8)->nullable()->after('delivery_zone_id');
            }
            if (!Schema::hasColumn('orders', 'lng')) {
                $table->decimal('lng', 11, 8)->nullable()->after('lat');
            }
            if (!Schema::hasColumn('orders', 'driver_id')) {
                $table->foreignId('driver_id')->nullable()->after('lng')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('orders', 'route_sequence')) {
                $table->unsignedInteger('route_sequence')->nullable()->after('driver_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'delivery_zone_id')) {
                $table->dropForeign(['delivery_zone_id']);
                $table->dropColumn('delivery_zone_id');
            }
            if (Schema::hasColumn('orders', 'driver_id')) {
                $table->dropForeign(['driver_id']);
                $table->dropColumn('driver_id');
            }
            $table->dropColumn(array_filter(['lat', 'lng', 'route_sequence'], fn($col) => Schema::hasColumn('orders', $col)));
        });
    }
};