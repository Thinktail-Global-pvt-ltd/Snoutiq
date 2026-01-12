<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('pets')) {
            return;
        }

        Schema::table('pets', function (Blueprint $table) {
            if (!Schema::hasColumn('pets', 'pet_type')) {
                $table->string('pet_type')->nullable()->after('pet_gender');
            }
            if (!Schema::hasColumn('pets', 'pet_dob')) {
                $table->date('pet_dob')->nullable()->after('pet_type');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('pets')) {
            return;
        }

        Schema::table('pets', function (Blueprint $table) {
            if (Schema::hasColumn('pets', 'pet_dob')) {
                $table->dropColumn('pet_dob');
            }
            if (Schema::hasColumn('pets', 'pet_type')) {
                $table->dropColumn('pet_type');
            }
        });
    }
};
