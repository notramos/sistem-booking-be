<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            DB::statement('CREATE INDEX idx_bookings_active_slot ON bookings (room_id, booking_date, start_time, end_time) WHERE status IN (\'pending\', \'approved\')');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            DB::statement('DROP INDEX IF EXISTS idx_bookings_active_slot');
        });
    }
};
