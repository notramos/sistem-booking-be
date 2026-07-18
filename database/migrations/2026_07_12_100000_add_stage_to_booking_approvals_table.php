<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_approvals', function (Blueprint $table) {
            $table->dropUnique(['booking_id']);
            $table->string('stage', 20)->default('sekretariat')->after('booking_id');
            $table->unique(['booking_id', 'stage']);
        });
    }

    public function down(): void
    {
        Schema::table('booking_approvals', function (Blueprint $table) {
            $table->dropUnique(['booking_id', 'stage']);
            $table->dropColumn('stage');
            $table->unique('booking_id');
        });
    }
};
