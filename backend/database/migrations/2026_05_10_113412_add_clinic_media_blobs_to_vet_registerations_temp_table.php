<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('vet_registerations_temp')) {
            return;
        }

        $this->addBlobColumn('clinic_image', 'clinic_profile');
        $this->addBlobColumn('clinic_video', 'clinic_image');
    }

    public function down(): void
    {
        if (! Schema::hasTable('vet_registerations_temp')) {
            return;
        }

        Schema::table('vet_registerations_temp', function (Blueprint $table) {
            if (Schema::hasColumn('vet_registerations_temp', 'clinic_video')) {
                $table->dropColumn('clinic_video');
            }

            if (Schema::hasColumn('vet_registerations_temp', 'clinic_image')) {
                $table->dropColumn('clinic_image');
            }
        });
    }

    private function addBlobColumn(string $column, string $after): void
    {
        if (Schema::hasColumn('vet_registerations_temp', $column)) {
            return;
        }

        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE vet_registerations_temp ADD COLUMN {$column} LONGBLOB NULL AFTER {$after}");
        } elseif ($driver === 'pgsql') {
            DB::statement("ALTER TABLE vet_registerations_temp ADD COLUMN {$column} BYTEA NULL");
        } else {
            Schema::table('vet_registerations_temp', function (Blueprint $table) use ($column, $after) {
                $table->binary($column)->nullable()->after($after);
            });
        }
    }
};
