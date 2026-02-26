<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users') && ! Schema::hasColumn('users', 'city')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('city', 120)->nullable()->after('phone');
            });
        }

        if (Schema::hasTable('pets') && ! Schema::hasColumn('pets', 'deworming_yes_no')) {
            Schema::table('pets', function (Blueprint $table) {
                $table->boolean('deworming_yes_no')->nullable()->after('vaccenated_yes_no');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('pets') && Schema::hasColumn('pets', 'deworming_yes_no')) {
            Schema::table('pets', function (Blueprint $table) {
                $table->dropColumn('deworming_yes_no');
            });
        }

        if (Schema::hasTable('users') && Schema::hasColumn('users', 'city')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('city');
            });
        }
    }
};
