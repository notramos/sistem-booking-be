<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex(['recurring_group_id']);
            $table->dropColumn(['recurring_group_id', 'recurring_sequence']);
            $table->json('recurring_dates')->nullable()->after('recurring_pattern');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('recurring_dates');
            $table->uuid('recurring_group_id')->nullable()->after('booking_type');
            $table->unsignedSmallInteger('recurring_sequence')->nullable()->after('recurring_pattern');
            $table->index('recurring_group_id');
        });
    }
};
