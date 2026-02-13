<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('doctors', function (Blueprint $table) {
            if (!Schema::hasColumn('doctors', 'doctor_image_blob')) {
                $table->longBlob('doctor_image_blob')->nullable()->after('doctor_image');
            }

            if (!Schema::hasColumn('doctors', 'doctor_image_mime')) {
                $table->string('doctor_image_mime', 100)->nullable()->after('doctor_image_blob');
            }
        });
    }

    public function down(): void
    {
        Schema::table('doctors', function (Blueprint $table) {
            if (Schema::hasColumn('doctors', 'doctor_image_mime')) {
                $table->dropColumn('doctor_image_mime');
            }

            if (Schema::hasColumn('doctors', 'doctor_image_blob')) {
                $table->dropColumn('doctor_image_blob');
            }
        });
    }
};
