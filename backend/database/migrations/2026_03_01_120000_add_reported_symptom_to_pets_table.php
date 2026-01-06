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
            if (!Schema::hasColumn('pets', 'reported_symptom')) {
                $table->text('reported_symptom')->nullable()->after('pet_gender');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('pets')) {
            return;
        }

        Schema::table('pets', function (Blueprint $table) {
            if (Schema::hasColumn('pets', 'reported_symptom')) {
                $table->dropColumn('reported_symptom');
            }
        });
    }
};
