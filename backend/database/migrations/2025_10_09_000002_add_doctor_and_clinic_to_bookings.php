<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (!Schema::hasColumn('bookings', 'assigned_doctor_id')) {
                $table->unsignedBigInteger('assigned_doctor_id')->nullable()->after('assigned_provider_id');
                $table->foreign('assigned_doctor_id')->references('id')->on('doctors')->onDelete('set null');
            }
            if (!Schema::hasColumn('bookings', 'clinic_id')) {
                $table->unsignedInteger('clinic_id')->nullable()->after('assigned_doctor_id');
                // referencing vet_registerations_temp
            }
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (Schema::hasColumn('bookings', 'assigned_doctor_id')) {
                $table->dropForeign(['assigned_doctor_id']);
                $table->dropColumn('assigned_doctor_id');
            }
            if (Schema::hasColumn('bookings', 'clinic_id')) {
                $table->dropColumn('clinic_id');
            }
        });
    }
};

