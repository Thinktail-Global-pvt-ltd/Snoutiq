<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('user_pets', function (Blueprint $table) {
            if (!Schema::hasColumn('user_pets', 'weight')) {
                $table->decimal('weight', 6, 2)->nullable()->after('gender');
            }
            if (!Schema::hasColumn('user_pets', 'temprature')) {
                $table->decimal('temprature', 5, 2)->nullable()->after('weight');
            }
            if (!Schema::hasColumn('user_pets', 'vaccenated_yes_no')) {
                $table->boolean('vaccenated_yes_no')->default(false)->after('temprature');
            }
            if (!Schema::hasColumn('user_pets', 'last_vaccenated_date')) {
                $table->date('last_vaccenated_date')->nullable()->after('vaccenated_yes_no');
            }
        });
    }

    public function down(): void
    {
        Schema::table('user_pets', function (Blueprint $table) {
            foreach (['last_vaccenated_date', 'vaccenated_yes_no', 'temprature', 'weight'] as $col) {
                if (Schema::hasColumn('user_pets', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
