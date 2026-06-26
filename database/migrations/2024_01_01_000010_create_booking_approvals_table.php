<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_approvals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->foreignUuid('approver_id')->constrained('users');
            $table->string('action', 20)->check("action IN ('approved', 'rejected')");
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique('booking_id');
            $table->index('approver_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_approvals');
    }
};
