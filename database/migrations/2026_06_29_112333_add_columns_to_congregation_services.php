<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('congregation_services', function (Blueprint $table) {
            $table->string('applicant_gender')->nullable()->after('applicant_name');
            $table->string('baptismal_name')->nullable()->after('applicant_gender');
            $table->string('birth_place')->nullable()->after('baptismal_name');
            $table->date('birth_date')->nullable()->after('birth_place');
            $table->string('phone')->nullable()->after('contact');
            $table->string('mobile_phone')->nullable()->after('phone');
            $table->string('neighborhood')->nullable()->after('mobile_phone');
            $table->string('region')->nullable()->after('neighborhood');
            $table->string('parish')->nullable()->after('region');
            $table->string('father_name')->nullable()->after('parish');
            $table->string('father_religion')->nullable()->after('father_name');
            $table->string('mother_name')->nullable()->after('father_religion');
            $table->string('mother_religion')->nullable()->after('mother_name');
            $table->string('school')->nullable()->after('mother_religion');
            $table->string('grade')->nullable()->after('school');
            $table->string('occupation')->nullable()->after('grade');
            $table->string('family_card_number')->nullable()->after('occupation');
        });
    }

    public function down(): void
    {
        Schema::table('congregation_services', function (Blueprint $table) {
            $table->dropColumn([
                'applicant_gender', 'baptismal_name', 'birth_place', 'birth_date',
                'phone', 'mobile_phone', 'neighborhood', 'region', 'parish',
                'father_name', 'father_religion', 'mother_name', 'mother_religion',
                'school', 'grade', 'occupation', 'family_card_number',
            ]);
        });
    }
};
