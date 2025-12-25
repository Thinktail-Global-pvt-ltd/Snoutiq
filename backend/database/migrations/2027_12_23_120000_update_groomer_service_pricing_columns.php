<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('groomer_services', function (Blueprint $table) {
            if (!Schema::hasColumn('groomer_services', 'price_after_service')) {
                $table->boolean('price_after_service')->default(false)->after('price_max');
            }
        });

        try {
            Schema::table('groomer_services', function (Blueprint $table) {
                $table->decimal('price', 8, 2)->nullable()->change();
            });
        } catch (\Throwable $e) {
            // Fallback for drivers that do not support change() without DBAL
            $driver = Schema::getConnection()->getDriverName();
            if ($driver === 'mysql') {
                DB::statement('ALTER TABLE groomer_services MODIFY price DECIMAL(8,2) NULL');
            } elseif ($driver === 'pgsql') {
                DB::statement('ALTER TABLE groomer_services ALTER COLUMN price DROP NOT NULL');
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('groomer_services', function (Blueprint $table) {
            if (Schema::hasColumn('groomer_services', 'price_after_service')) {
                $table->dropColumn('price_after_service');
            }
        });

        try {
            Schema::table('groomer_services', function (Blueprint $table) {
                $table->decimal('price', 8, 2)->nullable(false)->change();
            });
        } catch (\Throwable $e) {
            $driver = Schema::getConnection()->getDriverName();
            if ($driver === 'mysql') {
                DB::statement('ALTER TABLE groomer_services MODIFY price DECIMAL(8,2) NOT NULL');
            } elseif ($driver === 'pgsql') {
                DB::statement('ALTER TABLE groomer_services ALTER COLUMN price SET NOT NULL');
            }
        }
    }
};
