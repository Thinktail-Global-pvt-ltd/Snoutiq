<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pets', function (Blueprint $table) {
            if (!Schema::hasColumn('pets', 'pet_age_months')) {
                $table->unsignedTinyInteger('pet_age_months')->nullable()->after('pet_age');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pets', function (Blueprint $table) {
            if (Schema::hasColumn('pets', 'pet_age_months')) {
                $table->dropColumn('pet_age_months');
            }
        });
    }
};
