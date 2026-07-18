<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignUuid('wilayah_id')->nullable()->after('nip')->constrained('wilayah')->nullOnDelete();
            $table->foreignUuid('lingkungan_id')->nullable()->after('wilayah_id')->constrained('lingkungan')->nullOnDelete();
            $table->string('parish')->nullable()->after('lingkungan_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('wilayah_id');
            $table->dropConstrainedForeignId('lingkungan_id');
            $table->dropColumn('parish');
        });
    }
};
