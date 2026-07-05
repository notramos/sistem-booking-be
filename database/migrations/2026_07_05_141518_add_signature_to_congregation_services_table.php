<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('congregation_services', function (Blueprint $table) {
            $table->text('signature_pemohon')->nullable()->after('notes');
            $table->timestamp('signature_pemohon_at')->nullable()->after('signature_pemohon');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('congregation_services', function (Blueprint $table) {
            $table->dropColumn(['signature_pemohon', 'signature_pemohon_at']);
        });
    }
};
