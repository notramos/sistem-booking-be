<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE congregation_services ALTER COLUMN description DROP NOT NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("UPDATE congregation_services SET description = '' WHERE description IS NULL");
        DB::statement('ALTER TABLE congregation_services ALTER COLUMN description SET NOT NULL');
    }
};
