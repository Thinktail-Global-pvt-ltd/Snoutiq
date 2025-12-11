<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prescriptions', function (Blueprint $table) {
            if (!Schema::hasColumn('prescriptions', 'temperature')) {
                $table->decimal('temperature', 5, 2)->nullable()->after('next_visit_day');
            }
            if (!Schema::hasColumn('prescriptions', 'temperature_unit')) {
                $table->string('temperature_unit', 5)->default('C')->after('temperature');
            }
        });
    }

    public function down(): void
    {
        Schema::table('prescriptions', function (Blueprint $table) {
            if (Schema::hasColumn('prescriptions', 'temperature_unit')) {
                $table->dropColumn('temperature_unit');
            }
            if (Schema::hasColumn('prescriptions', 'temperature')) {
                $table->dropColumn('temperature');
            }
        });
    }
};
