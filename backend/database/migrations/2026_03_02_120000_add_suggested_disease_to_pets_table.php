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
            if (!Schema::hasColumn('pets', 'suggested_disease')) {
                $table->string('suggested_disease', 255)->nullable()->after('reported_symptom');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('pets')) {
            return;
        }

        Schema::table('pets', function (Blueprint $table) {
            if (Schema::hasColumn('pets', 'suggested_disease')) {
                $table->dropColumn('suggested_disease');
            }
        });
    }
};
