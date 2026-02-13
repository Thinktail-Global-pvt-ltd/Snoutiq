<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('doctors', 'doctor_image_blob')) {
            $driver = DB::getDriverName();

            if ($driver === 'mysql') {
                DB::statement('ALTER TABLE doctors ADD COLUMN doctor_image_blob LONGBLOB NULL AFTER doctor_image');
            } elseif ($driver === 'pgsql') {
                DB::statement('ALTER TABLE doctors ADD COLUMN doctor_image_blob BYTEA NULL');
            } else {
                Schema::table('doctors', function (Blueprint $table) {
                    $table->binary('doctor_image_blob')->nullable()->after('doctor_image');
                });
            }
        }

        if (!Schema::hasColumn('doctors', 'doctor_image_mime')) {
            Schema::table('doctors', function (Blueprint $table) {
                $table->string('doctor_image_mime', 100)->nullable()->after('doctor_image_blob');
            });
        }
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
