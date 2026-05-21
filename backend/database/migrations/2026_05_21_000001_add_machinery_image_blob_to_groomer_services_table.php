<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('groomer_services')) {
            return;
        }

        $this->addBlobColumn('machinery_image_blob', 'service_pic');

        if (! Schema::hasColumn('groomer_services', 'machinery_image_mime')) {
            Schema::table('groomer_services', function (Blueprint $table) {
                $table->string('machinery_image_mime', 120)->nullable()->after('machinery_image_blob');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('groomer_services')) {
            return;
        }

        Schema::table('groomer_services', function (Blueprint $table) {
            if (Schema::hasColumn('groomer_services', 'machinery_image_mime')) {
                $table->dropColumn('machinery_image_mime');
            }

            if (Schema::hasColumn('groomer_services', 'machinery_image_blob')) {
                $table->dropColumn('machinery_image_blob');
            }
        });
    }

    private function addBlobColumn(string $column, string $after): void
    {
        if (Schema::hasColumn('groomer_services', $column)) {
            return;
        }

        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE groomer_services ADD COLUMN {$column} LONGBLOB NULL AFTER {$after}");
        } elseif ($driver === 'pgsql') {
            DB::statement("ALTER TABLE groomer_services ADD COLUMN {$column} BYTEA NULL");
        } else {
            Schema::table('groomer_services', function (Blueprint $table) use ($column, $after) {
                $table->binary($column)->nullable()->after($after);
            });
        }
    }
};
