<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('user_observations')) {
            return;
        }

        Schema::table('user_observations', function (Blueprint $table) {
            if (!Schema::hasColumn('user_observations', 'pet_id')) {
                $table->unsignedBigInteger('pet_id')->nullable()->after('user_id')->index();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('user_observations')) {
            return;
        }

        Schema::table('user_observations', function (Blueprint $table) {
            if (Schema::hasColumn('user_observations', 'pet_id')) {
                $table->dropColumn('pet_id');
            }
        });
    }
};
