<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('prescriptions')) {
            return;
        }

        Schema::table('prescriptions', function (Blueprint $table) {
            if (!Schema::hasColumn('prescriptions', 'video_appointment_id')) {
                $table->unsignedBigInteger('video_appointment_id')->nullable()->after('pet_id');
                $table->index('video_appointment_id');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('prescriptions')) {
            return;
        }

        Schema::table('prescriptions', function (Blueprint $table) {
            if (Schema::hasColumn('prescriptions', 'video_appointment_id')) {
                $table->dropIndex(['video_appointment_id']);
                $table->dropColumn('video_appointment_id');
            }
        });
    }
};
