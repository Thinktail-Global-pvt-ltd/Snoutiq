<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pets', function (Blueprint $table) {
            if (!Schema::hasColumn('pets', 'vaccine_reminder_status')) {
                $table->json('vaccine_reminder_status')->nullable()->after('vaccination_date');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pets', function (Blueprint $table) {
            if (Schema::hasColumn('pets', 'vaccine_reminder_status')) {
                $table->dropColumn('vaccine_reminder_status');
            }
        });
    }
};
