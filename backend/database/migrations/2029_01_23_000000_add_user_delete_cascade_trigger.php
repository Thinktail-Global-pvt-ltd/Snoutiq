<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TRIGGER_NAME = 'trg_users_before_delete_cascade';

    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql' || !Schema::hasTable('users')) {
            return;
        }

        DB::unprepared('DROP TRIGGER IF EXISTS ' . self::TRIGGER_NAME);

        $statements = [];

        $hasPets = Schema::hasTable('pets');
        $hasPetsUserId = $hasPets && Schema::hasColumn('pets', 'user_id');
        $hasUserObservations = Schema::hasTable('user_observations');
        $hasPrescriptions = Schema::hasTable('prescriptions');

        if ($hasUserObservations && Schema::hasColumn('user_observations', 'pet_id') && $hasPetsUserId) {
            $statements[] = 'DELETE FROM user_observations WHERE pet_id IN (SELECT id FROM pets WHERE user_id = OLD.id)';
        }
        if ($hasUserObservations && Schema::hasColumn('user_observations', 'user_id')) {
            $statements[] = 'DELETE FROM user_observations WHERE user_id = OLD.id';
        }
        if ($hasPrescriptions && Schema::hasColumn('prescriptions', 'pet_id') && $hasPetsUserId) {
            $statements[] = 'DELETE FROM prescriptions WHERE pet_id IN (SELECT id FROM pets WHERE user_id = OLD.id)';
        }
        if ($hasPrescriptions && Schema::hasColumn('prescriptions', 'user_id')) {
            $statements[] = 'DELETE FROM prescriptions WHERE user_id = OLD.id';
        }
        if ($hasPetsUserId) {
            $statements[] = 'DELETE FROM pets WHERE user_id = OLD.id';
        }

        if (empty($statements)) {
            return;
        }

        $sql = 'CREATE TRIGGER ' . self::TRIGGER_NAME . ' BEFORE DELETE ON users FOR EACH ROW BEGIN '
            . implode('; ', $statements)
            . '; END';

        DB::unprepared($sql);
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::unprepared('DROP TRIGGER IF EXISTS ' . self::TRIGGER_NAME);
    }
};

