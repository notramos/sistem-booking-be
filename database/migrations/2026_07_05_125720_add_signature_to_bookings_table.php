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
        Schema::table('bookings', function (Blueprint $table) {
            $table->text('signature_pemohon')->nullable()->after('service_details');
            $table->timestamp('signature_pemohon_at')->nullable()->after('signature_pemohon');
            $table->text('signature_petugas')->nullable()->after('signature_pemohon_at');
            $table->timestamp('signature_petugas_at')->nullable()->after('signature_petugas');
            $table->foreignUuid('signed_petugas_by')->nullable()->after('signature_petugas_at')
                ->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropForeign(['signed_petugas_by']);
            $table->dropColumn([
                'signature_pemohon',
                'signature_pemohon_at',
                'signature_petugas',
                'signature_petugas_at',
                'signed_petugas_by',
            ]);
        });
    }
};
