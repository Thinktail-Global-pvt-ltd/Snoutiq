<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('pets')) {
            return;
        }

        if (! Schema::hasColumn('pets', 'vaccination_image_blob')) {
            $driver = DB::getDriverName();
            if ($driver === 'mysql') {
                $after = Schema::hasColumn('pets', 'dog_disease_payload') ? ' AFTER dog_disease_payload' : '';
                DB::statement("ALTER TABLE pets ADD COLUMN vaccination_image_blob LONGBLOB NULL{$after}");
            } elseif ($driver === 'pgsql') {
                DB::statement('ALTER TABLE pets ADD COLUMN vaccination_image_blob BYTEA NULL');
            } else {
                Schema::table('pets', function (Blueprint $table) {
                    $table->binary('vaccination_image_blob')->nullable();
                });
            }
        }

        Schema::table('pets', function (Blueprint $table) {
            if (! Schema::hasColumn('pets', 'vaccination_image_mime')) {
                $table->string('vaccination_image_mime', 120)->nullable()->after('vaccination_image_blob');
            }
            if (! Schema::hasColumn('pets', 'vaccination_image_name')) {
                $table->string('vaccination_image_name')->nullable()->after('vaccination_image_mime');
            }
            if (! Schema::hasColumn('pets', 'vaccination_image_size')) {
                $table->unsignedInteger('vaccination_image_size')->nullable()->after('vaccination_image_name');
            }
            if (! Schema::hasColumn('pets', 'vaccination_image_uploaded_at')) {
                $table->timestamp('vaccination_image_uploaded_at')->nullable()->after('vaccination_image_size');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('pets')) {
            return;
        }

        Schema::table('pets', function (Blueprint $table) {
            if (Schema::hasColumn('pets', 'vaccination_image_uploaded_at')) {
                $table->dropColumn('vaccination_image_uploaded_at');
            }
            if (Schema::hasColumn('pets', 'vaccination_image_size')) {
                $table->dropColumn('vaccination_image_size');
            }
            if (Schema::hasColumn('pets', 'vaccination_image_name')) {
                $table->dropColumn('vaccination_image_name');
            }
            if (Schema::hasColumn('pets', 'vaccination_image_mime')) {
                $table->dropColumn('vaccination_image_mime');
            }
            if (Schema::hasColumn('pets', 'vaccination_image_blob')) {
                $table->dropColumn('vaccination_image_blob');
            }
        });
    }
};
