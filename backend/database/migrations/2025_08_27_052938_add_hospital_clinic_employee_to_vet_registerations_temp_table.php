<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vet_registerations_temp', function (Blueprint $table) {
            $table->string('hospital_profile')->nullable()->after('image');
            $table->string('clinic_profile')->nullable()->after('hospital_profile');
            $table->string('employee_id')->nullable()->after('clinic_profile');
        });
    }

    public function down(): void
    {
        Schema::table('vet_registerations_temp', function (Blueprint $table) {
            $table->dropColumn(['hospital_profile', 'clinic_profile', 'employee_id']);
        });
    }
};
