<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('user_observations')) {
            return;
        }

        if (!Schema::hasColumn('user_observations', 'image_blob')) {
            $driver = DB::getDriverName();
            if ($driver === 'mysql') {
                DB::statement('ALTER TABLE user_observations ADD COLUMN image_blob LONGBLOB NULL AFTER notes');
            } elseif ($driver === 'pgsql') {
                DB::statement('ALTER TABLE user_observations ADD COLUMN image_blob BYTEA NULL');
            } else {
                Schema::table('user_observations', function (Blueprint $table) {
                    $table->binary('image_blob')->nullable()->after('notes');
                });
            }
        }

        Schema::table('user_observations', function (Blueprint $table) {
            if (!Schema::hasColumn('user_observations', 'image_mime')) {
                $table->string('image_mime', 100)->nullable()->after('image_blob');
            }
            if (!Schema::hasColumn('user_observations', 'image_name')) {
                $table->string('image_name', 255)->nullable()->after('image_mime');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('user_observations')) {
            return;
        }

        Schema::table('user_observations', function (Blueprint $table) {
            if (Schema::hasColumn('user_observations', 'image_name')) {
                $table->dropColumn('image_name');
            }
            if (Schema::hasColumn('user_observations', 'image_mime')) {
                $table->dropColumn('image_mime');
            }
            if (Schema::hasColumn('user_observations', 'image_blob')) {
                $table->dropColumn('image_blob');
            }
        });
    }
};

