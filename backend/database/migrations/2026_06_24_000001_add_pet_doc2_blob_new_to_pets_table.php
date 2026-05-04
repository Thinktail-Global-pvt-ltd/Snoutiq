<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('pets') || Schema::hasColumn('pets', 'pet_doc2_blob_new')) {
            return;
        }

        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            $after = Schema::hasColumn('pets', 'pet_doc2_blob') ? ' AFTER pet_doc2_blob' : '';
            DB::statement("ALTER TABLE pets ADD COLUMN pet_doc2_blob_new LONGBLOB NULL{$after}");
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE pets ADD COLUMN pet_doc2_blob_new BYTEA NULL');
        } else {
            Schema::table('pets', function (Blueprint $table) {
                $table->binary('pet_doc2_blob_new')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('pets') || !Schema::hasColumn('pets', 'pet_doc2_blob_new')) {
            return;
        }

        Schema::table('pets', function (Blueprint $table) {
            $table->dropColumn('pet_doc2_blob_new');
        });
    }
};
