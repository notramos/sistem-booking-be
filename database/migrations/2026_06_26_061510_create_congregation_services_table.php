<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('congregation_services', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->string('service_type');
            $table->string('applicant_name');
            $table->text('address')->nullable();
            $table->string('contact');
            $table->date('service_date')->nullable();
            $table->text('description');
            $table->string('status')->default('pending');
            $table->text('notes')->nullable();
            $table->json('dynamic_fields')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('congregation_services');
    }
};
