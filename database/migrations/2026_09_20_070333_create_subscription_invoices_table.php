<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->string('number')->unique(); // np. FV/2026/09/001
            $table->string('plan_name');
            $table->decimal('amount_net', 10, 2);
            $table->decimal('amount_gross', 10, 2);
            $table->string('status')->default('paid'); // paid, refunded, pending
            $table->timestamp('paid_at')->useCurrent();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_invoices');
    }
};