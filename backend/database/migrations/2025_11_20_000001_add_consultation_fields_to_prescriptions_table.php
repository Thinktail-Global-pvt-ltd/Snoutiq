<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prescriptions', function (Blueprint $table) {
            if (!Schema::hasColumn('prescriptions', 'visit_category')) {
                $table->string('visit_category')->nullable()->after('user_id');
            }
            if (!Schema::hasColumn('prescriptions', 'case_severity')) {
                $table->string('case_severity')->nullable()->after('visit_category');
            }
            if (!Schema::hasColumn('prescriptions', 'visit_notes')) {
                $table->text('visit_notes')->nullable()->after('case_severity');
            }
            if (!Schema::hasColumn('prescriptions', 'weight')) {
                $table->decimal('weight', 8, 2)->nullable()->after('temperature_unit');
            }
            if (!Schema::hasColumn('prescriptions', 'heart_rate')) {
                $table->decimal('heart_rate', 8, 2)->nullable()->after('weight');
            }
            if (!Schema::hasColumn('prescriptions', 'exam_notes')) {
                $table->text('exam_notes')->nullable()->after('heart_rate');
            }
            if (!Schema::hasColumn('prescriptions', 'diagnosis')) {
                $table->string('diagnosis')->nullable()->after('exam_notes');
            }
            if (!Schema::hasColumn('prescriptions', 'diagnosis_status')) {
                $table->string('diagnosis_status')->nullable()->after('diagnosis');
            }
            if (!Schema::hasColumn('prescriptions', 'treatment_plan')) {
                $table->text('treatment_plan')->nullable()->after('diagnosis_status');
            }
            if (!Schema::hasColumn('prescriptions', 'home_care')) {
                $table->text('home_care')->nullable()->after('treatment_plan');
            }
            if (!Schema::hasColumn('prescriptions', 'follow_up_date')) {
                $table->date('follow_up_date')->nullable()->after('home_care');
            }
            if (!Schema::hasColumn('prescriptions', 'follow_up_type')) {
                $table->string('follow_up_type')->nullable()->after('follow_up_date');
            }
            if (!Schema::hasColumn('prescriptions', 'follow_up_notes')) {
                $table->text('follow_up_notes')->nullable()->after('follow_up_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('prescriptions', function (Blueprint $table) {
            foreach ([
                'visit_category',
                'case_severity',
                'visit_notes',
                'weight',
                'heart_rate',
                'exam_notes',
                'diagnosis',
                'diagnosis_status',
                'treatment_plan',
                'home_care',
                'follow_up_date',
                'follow_up_type',
                'follow_up_notes',
            ] as $column) {
                if (Schema::hasColumn('prescriptions', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
