<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            // UUID Primary Key (AMAN untuk PostgreSQL + Neon)
            $table->uuid('id')->primary();

            $table->string('name');

            $table->string('email')->unique();

            $table->timestamp('email_verified_at')->nullable();

            $table->string('password');

            $table->string('phone', 50)->nullable();

            $table->string('avatar')->nullable();

            $table->string('department', 100)->nullable();

            $table->string('position', 100)->nullable();

            $table->string('nip', 50)->nullable()->unique();

            $table->boolean('is_active')->default(true);

            $table->timestamp('last_login_at')->nullable();

            $table->rememberToken();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('personal_access_tokens');
        Schema::dropIfExists('password_reset_tokens');
    }
};