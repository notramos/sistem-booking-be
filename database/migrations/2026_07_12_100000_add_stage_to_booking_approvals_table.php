<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL (beda dari Postgres) tidak bisa drop index unique yang masih dipakai
        // foreign key — FK-nya perlu dilepas dulu, baru dipasang lagi setelah index
        // unique lama diganti.
        Schema::table('booking_approvals', function (Blueprint $table) {
            $table->dropForeign(['booking_id']);
            $table->dropUnique(['booking_id']);
            $table->string('stage', 20)->default('sekretariat')->after('booking_id');
            $table->unique(['booking_id', 'stage']);
            $table->foreign('booking_id')->references('id')->on('bookings')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('booking_approvals', function (Blueprint $table) {
            $table->dropForeign(['booking_id']);
            $table->dropUnique(['booking_id', 'stage']);
            $table->dropColumn('stage');
            $table->unique('booking_id');
            $table->foreign('booking_id')->references('id')->on('bookings')->cascadeOnDelete();
        });
    }
};
