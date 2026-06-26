<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users');
            $table->foreignUuid('room_id')->constrained('rooms');
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('booking_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('purpose_type', 50)->nullable()->comment('ibadah,acara_keluarga,latihan_musik,pembinaan,rapat,seminar,publik');
            $table->integer('expected_attendees')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('status', 20)->default('pending')
                ->check("status IN ('pending', 'approved', 'rejected', 'cancelled', 'completed')");
            $table->text('notes')->nullable();
            $table->text('reject_reason')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('room_id');
            $table->index('booking_date');
            $table->index('status');
            $table->index(['room_id', 'booking_date', 'start_time', 'end_time'], 'idx_bookings_slot');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
