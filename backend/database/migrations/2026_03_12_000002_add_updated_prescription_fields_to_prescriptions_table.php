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
            if (!Schema::hasColumn('prescriptions', 'prognosis')) {
                $table->string('prognosis', 16)->nullable()->after('disease_name');
            }
            if (!Schema::hasColumn('prescriptions', 'follow_up_required')) {
                $table->boolean('follow_up_required')->nullable()->after('home_care');
            }
            if (!Schema::hasColumn('prescriptions', 'system_affected')) {
                $table->string('system_affected', 100)->nullable()->after('follow_up_notes');
            }
            if (!Schema::hasColumn('prescriptions', 'history_snapshot')) {
                $table->text('history_snapshot')->nullable()->after('visit_notes');
            }
            if (!Schema::hasColumn('prescriptions', 'mucous_membrane')) {
                $table->string('mucous_membrane', 32)->nullable()->after('exam_notes');
            }
            if (!Schema::hasColumn('prescriptions', 'dehydration_level')) {
                $table->string('dehydration_level', 16)->nullable()->after('mucous_membrane');
            }
            if (!Schema::hasColumn('prescriptions', 'abdominal_pain_reaction')) {
                $table->string('abdominal_pain_reaction', 16)->nullable()->after('dehydration_level');
            }
            if (!Schema::hasColumn('prescriptions', 'auscultation')) {
                $table->string('auscultation', 16)->nullable()->after('abdominal_pain_reaction');
            }
            if (!Schema::hasColumn('prescriptions', 'physical_exam_other')) {
                $table->text('physical_exam_other')->nullable()->after('auscultation');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('prescriptions')) {
            return;
        }

        Schema::table('prescriptions', function (Blueprint $table) {
            $columns = [
                'prognosis',
                'follow_up_required',
                'system_affected',
                'history_snapshot',
                'mucous_membrane',
                'dehydration_level',
                'abdominal_pain_reaction',
                'auscultation',
                'physical_exam_other',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('prescriptions', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

