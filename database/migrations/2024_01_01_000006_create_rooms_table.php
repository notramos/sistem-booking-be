<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('slug')->unique();
            $table->foreignUuid('category_id')->constrained('room_categories');
            $table->text('description')->nullable();
            $table->integer('capacity')->check('capacity > 0');
            $table->string('floor', 50)->nullable();
            $table->string('building')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('status', 20)->default('available')
                ->check("status IN ('available', 'maintenance', 'unavailable')");
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('category_id');
            $table->index('status');
            $table->index('capacity');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
