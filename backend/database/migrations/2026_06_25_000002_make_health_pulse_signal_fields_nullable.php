<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('health_pulse_entries')) {
            return;
        }

        foreach (['food', 'energy', 'water'] as $column) {
            if (Schema::hasColumn('health_pulse_entries', $column)) {
                DB::statement("ALTER TABLE `health_pulse_entries` MODIFY `{$column}` VARCHAR(40) NULL");
            }
        }

        if (Schema::hasColumn('health_pulse_entries', 'digestion_issue')) {
            DB::statement('ALTER TABLE `health_pulse_entries` MODIFY `digestion_issue` TINYINT(1) NULL');
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('health_pulse_entries')) {
            return;
        }

        foreach (['food', 'energy', 'water'] as $column) {
            if (Schema::hasColumn('health_pulse_entries', $column)) {
                DB::statement("ALTER TABLE `health_pulse_entries` MODIFY `{$column}` VARCHAR(40) NOT NULL");
            }
        }

        if (Schema::hasColumn('health_pulse_entries', 'digestion_issue')) {
            DB::statement('ALTER TABLE `health_pulse_entries` MODIFY `digestion_issue` TINYINT(1) NOT NULL DEFAULT 0');
        }
    }
};
