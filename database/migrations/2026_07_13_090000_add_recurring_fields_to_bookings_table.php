<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('booking_type', 20)->default('reguler')->after('status');
            $table->uuid('recurring_group_id')->nullable()->after('booking_type');
            $table->string('recurring_pattern', 10)->nullable()->after('recurring_group_id');
            $table->unsignedSmallInteger('recurring_sequence')->nullable()->after('recurring_pattern');

            $table->index('recurring_group_id');
            $table->index('booking_type');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex(['recurring_group_id']);
            $table->dropIndex(['booking_type']);
            $table->dropColumn(['booking_type', 'recurring_group_id', 'recurring_pattern', 'recurring_sequence']);
        });
    }
};
