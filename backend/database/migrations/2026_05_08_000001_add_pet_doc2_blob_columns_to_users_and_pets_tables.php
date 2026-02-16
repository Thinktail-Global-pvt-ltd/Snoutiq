<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addPetDoc2BlobColumns('users');
        $this->addPetDoc2BlobColumns('pets');
    }

    public function down(): void
    {
        $this->dropPetDoc2BlobColumns('users');
        $this->dropPetDoc2BlobColumns('pets');
    }

    private function addPetDoc2BlobColumns(string $tableName): void
    {
        if (!Schema::hasTable($tableName)) {
            return;
        }

        if (!Schema::hasColumn($tableName, 'pet_doc2_blob')) {
            $driver = DB::getDriverName();
            if ($driver === 'mysql') {
                $after = Schema::hasColumn($tableName, 'pet_doc2') ? ' AFTER pet_doc2' : '';
                DB::statement("ALTER TABLE {$tableName} ADD COLUMN pet_doc2_blob LONGBLOB NULL{$after}");
            } elseif ($driver === 'pgsql') {
                DB::statement("ALTER TABLE {$tableName} ADD COLUMN pet_doc2_blob BYTEA NULL");
            } else {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->binary('pet_doc2_blob')->nullable();
                });
            }
        }

        if (!Schema::hasColumn($tableName, 'pet_doc2_mime')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->string('pet_doc2_mime', 100)->nullable();
            });
        }
    }

    private function dropPetDoc2BlobColumns(string $tableName): void
    {
        if (!Schema::hasTable($tableName)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($tableName) {
            if (Schema::hasColumn($tableName, 'pet_doc2_mime')) {
                $table->dropColumn('pet_doc2_mime');
            }
            if (Schema::hasColumn($tableName, 'pet_doc2_blob')) {
                $table->dropColumn('pet_doc2_blob');
            }
        });
    }
};
