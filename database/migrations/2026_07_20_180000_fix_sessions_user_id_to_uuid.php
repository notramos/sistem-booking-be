<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // users.id pakai UUID (HasUuids), bukan bigint auto-increment — kolom
        // sessions.user_id yang dibuat migration sebelumnya salah tipe. Blueprint
        // (bukan raw SQL) supaya portable Postgres maupun MySQL.
        Schema::table('sessions', function (Blueprint $table) {
            $table->dropColumn('user_id');
        });
        Schema::table('sessions', function (Blueprint $table) {
            $table->uuid('user_id')->nullable()->index()->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('sessions', function (Blueprint $table) {
            $table->dropColumn('user_id');
        });
        Schema::table('sessions', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->index();
        });
    }
};
