<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Nama perumahan/kompleks tempat lingkungan berada (mis. "Vila Mutiara
     * Gading" untuk St. Alfonsus 1) — dipakai sebagai keterangan di dropdown
     * karena daftar lingkungan ditampilkan datar lintas wilayah.
     */
    public function up(): void
    {
        Schema::table('lingkungan', function (Blueprint $table) {
            $table->string('area')->nullable()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('lingkungan', function (Blueprint $table) {
            $table->dropColumn('area');
        });
    }
};
