<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('congregation_services', function (Blueprint $table) {
            $table->timestamp('received_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('congregation_services', function (Blueprint $table) {
            $table->dropColumn('received_at');
        });
    }
};
