<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamp('clock_in');
            $table->timestamp('clock_out')->nullable();
            $table->integer('break_minutes')->default(0);
            $table->timestamp('break_start_at')->nullable();
            $table->enum('status', ['working', 'on_break', 'completed', 'corrected'])->default('working');
            $table->decimal('hourly_rate', 8, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_shifts');
    }
};