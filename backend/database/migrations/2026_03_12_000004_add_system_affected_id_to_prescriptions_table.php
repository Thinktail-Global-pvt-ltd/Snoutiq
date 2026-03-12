<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('prescriptions') || !Schema::hasTable('affected_systems')) {
            return;
        }

        if (!Schema::hasColumn('prescriptions', 'system_affected_id')) {
            Schema::table('prescriptions', function (Blueprint $table) {
                $table->unsignedBigInteger('system_affected_id')->nullable()->after('system_affected');
            });
        }

        try {
            DB::statement('ALTER TABLE `prescriptions` ADD INDEX `idx_prescriptions_system_affected_id` (`system_affected_id`)');
        } catch (\Throwable $e) {
            // no-op: index may already exist
        }

        try {
            DB::statement(
                'ALTER TABLE `prescriptions` '
                . 'ADD CONSTRAINT `fk_prescriptions_system_affected_id` '
                . 'FOREIGN KEY (`system_affected_id`) REFERENCES `affected_systems` (`id`) '
                . 'ON DELETE SET NULL'
            );
        } catch (\Throwable $e) {
            // no-op: FK may already exist
        }

        if (!Schema::hasColumn('prescriptions', 'system_affected')) {
            return;
        }

        $systems = DB::table('affected_systems')
            ->select('id', 'code', 'name')
            ->get();

        if ($systems->isEmpty()) {
            return;
        }

        $idByKey = [];
        foreach ($systems as $system) {
            $id = (int) $system->id;
            $idByKey[strtolower((string) $system->code)] = $id;
            $idByKey[strtolower((string) $system->name)] = $id;
            $idByKey[$this->normalizeSystemCode((string) $system->name)] = $id;
        }

        DB::table('prescriptions')
            ->select('id', 'system_affected')
            ->whereNull('system_affected_id')
            ->whereNotNull('system_affected')
            ->orderBy('id')
            ->chunkById(500, function ($rows) use ($idByKey): void {
                foreach ($rows as $row) {
                    $raw = strtolower(trim((string) ($row->system_affected ?? '')));
                    if ($raw === '') {
                        continue;
                    }

                    $normalized = $this->normalizeSystemCode($raw);
                    $matchedId = $idByKey[$raw] ?? $idByKey[$normalized] ?? null;
                    if (!is_numeric($matchedId)) {
                        continue;
                    }

                    DB::table('prescriptions')
                        ->where('id', (int) $row->id)
                        ->update(['system_affected_id' => (int) $matchedId]);
                }
            });
    }

    public function down(): void
    {
        if (!Schema::hasTable('prescriptions') || !Schema::hasColumn('prescriptions', 'system_affected_id')) {
            return;
        }

        try {
            DB::statement('ALTER TABLE `prescriptions` DROP FOREIGN KEY `fk_prescriptions_system_affected_id`');
        } catch (\Throwable $e) {
            // no-op
        }

        try {
            DB::statement('ALTER TABLE `prescriptions` DROP INDEX `idx_prescriptions_system_affected_id`');
        } catch (\Throwable $e) {
            // no-op
        }

        Schema::table('prescriptions', function (Blueprint $table) {
            $table->dropColumn('system_affected_id');
        });
    }

    private function normalizeSystemCode(string $value): string
    {
        $normalized = strtolower(trim($value));
        $normalized = str_replace(['&', '/'], ' ', $normalized);
        $normalized = preg_replace('/[^a-z0-9]+/', '_', $normalized) ?? '';
        return trim($normalized, '_');
    }
};

