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
            if (!Schema::hasColumn('groomer_services', 'price_min')) {
                $table->decimal('price_min', 8, 2)->nullable()->after('price');
            }
            if (!Schema::hasColumn('groomer_services', 'price_max')) {
                $table->decimal('price_max', 8, 2)->nullable()->after('price_min');
            }
        });

        // Backfill existing rows so both ends of the range have a value
        DB::table('groomer_services')
            ->whereNull('price_min')
            ->update(['price_min' => DB::raw('price')]);

        DB::table('groomer_services')
            ->whereNull('price_max')
            ->update(['price_max' => DB::raw('price')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('groomer_services', function (Blueprint $table) {
            if (Schema::hasColumn('groomer_services', 'price_max')) {
                $table->dropColumn('price_max');
            }
            if (Schema::hasColumn('groomer_services', 'price_min')) {
                $table->dropColumn('price_min');
            }
        });
    }
};
