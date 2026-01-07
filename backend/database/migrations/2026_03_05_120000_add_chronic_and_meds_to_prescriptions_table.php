<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('prescriptions')) {
            return;
        }

        Schema::table('prescriptions', function (Blueprint $table) {
            if (!Schema::hasColumn('prescriptions', 'is_chronic')) {
                $table->boolean('is_chronic')->nullable()->after('diagnosis_status');
            }
            if (!Schema::hasColumn('prescriptions', 'medications_json')) {
                $table->json('medications_json')->nullable()->after('treatment_plan');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('prescriptions')) {
            return;
        }

        Schema::table('prescriptions', function (Blueprint $table) {
            if (Schema::hasColumn('prescriptions', 'medications_json')) {
                $table->dropColumn('medications_json');
            }
            if (Schema::hasColumn('prescriptions', 'is_chronic')) {
                $table->dropColumn('is_chronic');
            }
        });
    }
};
