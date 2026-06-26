<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users');
            $table->string('action', 50);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index('booking_id');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_logs');
    }
};
