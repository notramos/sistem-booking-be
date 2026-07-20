<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // users.id pakai UUID (HasUuids), bukan bigint auto-increment — kolom
        // sessions.user_id yang dibuat migration sebelumnya salah tipe.
        DB::statement('ALTER TABLE sessions DROP COLUMN IF EXISTS user_id');
        DB::statement('ALTER TABLE sessions ADD COLUMN user_id UUID NULL');
        DB::statement('CREATE INDEX IF NOT EXISTS sessions_user_id_index ON sessions (user_id)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE sessions DROP COLUMN IF EXISTS user_id');
        DB::statement('ALTER TABLE sessions ADD COLUMN user_id BIGINT NULL');
    }
};
