<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    private const TABLE = 'groomer_bookings';
    private const FOREIGN_KEY = 'groomer_bookings_groomer_employees_id_foreign';

    public function up(): void
    {
        Schema::table(self::TABLE, function (Blueprint $table) {
            $foreignKeyName = $this->resolveForeignKeyName();
            if ($foreignKeyName) {
                $table->dropForeign($foreignKeyName);
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable(self::TABLE) || !Schema::hasColumn(self::TABLE, 'groomer_employees_id')) {
            return;
        }

        if ($this->resolveForeignKeyName()) {
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

        $schemaManager = Schema::getConnection()->getDoctrineSchemaManager();
        $tableDetails = $schemaManager->listTableDetails(self::TABLE);

        /** @var \Doctrine\DBAL\Schema\ForeignKeyConstraint $constraint */
        foreach ($tableDetails->getForeignKeys() as $constraint) {
            if ($constraint->getLocalColumns() === ['groomer_employees_id']) {
                return $constraint->getName();
            }
        }

        return null;
    }
};
