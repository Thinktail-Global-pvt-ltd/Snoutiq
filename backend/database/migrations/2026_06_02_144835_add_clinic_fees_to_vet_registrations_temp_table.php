<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('vet_registerations_temp')) {
            return;
        }

        Schema::table('vet_registerations_temp', function (Blueprint $table) {
            if (! Schema::hasColumn('vet_registerations_temp', 'clinic_day_fee')) {
                $table->decimal('clinic_day_fee', 10, 2)->nullable()->after('mobile');
            }
            if (! Schema::hasColumn('vet_registerations_temp', 'clinic_night_fee')) {
                $table->decimal('clinic_night_fee', 10, 2)->nullable()->after('clinic_day_fee');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('vet_registerations_temp')) {
            return;
        }

        Schema::table('vet_registerations_temp', function (Blueprint $table) {
            if (Schema::hasColumn('vet_registerations_temp', 'clinic_day_fee')) {
                $table->dropColumn('clinic_day_fee');
            }
            if (Schema::hasColumn('vet_registerations_temp', 'clinic_night_fee')) {
                $table->dropColumn('clinic_night_fee');
            }
        });
    }
};
