<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Postgres mendukung partial index (WHERE status IN (...)) — index cuma mencakup
     * baris aktif, lebih kecil & efisien untuk cek konflik slot. MySQL tidak punya
     * fitur ini, jadi di sana index dibuat biasa (mencakup semua baris) — tetap
     * mempercepat query yang sama, cuma tidak sekecil versi partial-nya Postgres.
     */
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('CREATE INDEX idx_bookings_active_slot ON bookings (room_id, booking_date, start_time, end_time) WHERE status IN (\'pending\', \'approved\')');
        } else {
            DB::statement('CREATE INDEX idx_bookings_active_slot ON bookings (room_id, booking_date, start_time, end_time, status)');
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS idx_bookings_active_slot');
        } else {
            DB::statement('DROP INDEX idx_bookings_active_slot ON bookings');
        }
    }
};
