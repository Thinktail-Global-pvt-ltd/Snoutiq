<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('health_pulse_entries', function (Blueprint $table) {
            $table->string('quick_check_status', 40)->nullable()->after('entry_date');
        });
    }

    public function down(): void
    {
        Schema::table('health_pulse_entries', function (Blueprint $table) {
            $table->dropColumn('quick_check_status');
        });
    }
};
