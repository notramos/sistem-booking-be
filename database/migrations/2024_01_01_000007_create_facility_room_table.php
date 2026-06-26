<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facility_room', function (Blueprint $table) {
            $table->foreignUuid('room_id')->constrained('rooms')->cascadeOnDelete();
            $table->foreignUuid('facility_id')->constrained('room_facilities')->cascadeOnDelete();
            $table->primary(['room_id', 'facility_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facility_room');
    }
};
