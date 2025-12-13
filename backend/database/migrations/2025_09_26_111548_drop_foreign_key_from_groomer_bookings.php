<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    private const TABLE = 'groomer_bookings';
    private const FOREIGN_KEY = 'groomer_bookings_groomer_employees_id_foreign';

    public function up(): void
    {
        $foreignKeyName = $this->resolveForeignKeyName();
        if (!$foreignKeyName) {
            return;
        }

        Schema::table(self::TABLE, function (Blueprint $table) use ($foreignKeyName) {
            $table->dropForeign($foreignKeyName);
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable(self::TABLE) || !Schema::hasColumn(self::TABLE, 'groomer_employees_id')) {
            return;
        }

        if ($this->hasForeignKey()) {
            return;
        }

        Schema::table(self::TABLE, function (Blueprint $table) {
            $table->foreign('groomer_employees_id')
                ->references('id')
                ->on('groomer_employees')
                ->onDelete('cascade');
        });
    }

    private function hasForeignKey(): bool
    {
        return (bool) $this->resolveForeignKeyName();
    }

    private function resolveForeignKeyName(): ?string
    {
        if (!Schema::hasTable(self::TABLE)) {
            return null;
        }

        $database = DB::connection()->getDatabaseName();
        if (!$database) {
            return null;
        }

        $result = DB::selectOne(
            'SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ? AND REFERENCED_TABLE_NAME = ?',
            [$database, self::TABLE, 'groomer_employees_id', 'groomer_employees']
        );

        return $result->CONSTRAINT_NAME ?? null;
    }
};
