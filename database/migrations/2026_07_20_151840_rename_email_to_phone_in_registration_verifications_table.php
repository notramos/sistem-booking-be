<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Raw SQL (bukan Schema::renameColumn) supaya tidak butuh doctrine/dbal.
        DB::statement('ALTER TABLE registration_verifications RENAME COLUMN email TO phone');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE registration_verifications RENAME COLUMN phone TO email');
    }
};
