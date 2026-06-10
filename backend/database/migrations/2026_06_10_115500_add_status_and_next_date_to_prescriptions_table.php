<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('prescriptions', function (Blueprint $table) {
            if (!Schema::hasColumn('prescriptions', 'deworming_status')) {
                $table->string('deworming_status', 50)->nullable()->after('last_deworming_date');
            }
            if (!Schema::hasColumn('prescriptions', 'next_deworming_date')) {
                $table->date('next_deworming_date')->nullable()->after('deworming_status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prescriptions', function (Blueprint $table) {
            if (Schema::hasColumn('prescriptions', 'deworming_status')) {
                $table->dropColumn('deworming_status');
            }
            if (Schema::hasColumn('prescriptions', 'next_deworming_date')) {
                $table->dropColumn('next_deworming_date');
            }
        });
    }
};
