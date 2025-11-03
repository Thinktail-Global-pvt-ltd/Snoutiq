<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vet_registerations_temp', function (Blueprint $table) {
            if (!Schema::hasColumn('vet_registerations_temp', 'license_document')) {
                $table->string('license_document')->nullable()->after('license_no');
            }
        });

        Schema::table('doctors', function (Blueprint $table) {
            if (!Schema::hasColumn('doctors', 'doctor_document')) {
                $table->string('doctor_document')->nullable()->after('doctor_image');
            }
        });
    }

    public function down(): void
    {
        Schema::table('vet_registerations_temp', function (Blueprint $table) {
            if (Schema::hasColumn('vet_registerations_temp', 'license_document')) {
                $table->dropColumn('license_document');
            }
        });

        Schema::table('doctors', function (Blueprint $table) {
            if (Schema::hasColumn('doctors', 'doctor_document')) {
                $table->dropColumn('doctor_document');
            }
        });
    }
};
