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
            $table->date('next_medicine_day')->nullable()->after('content_html');
            $table->date('next_visit_day')->nullable()->after('next_medicine_day');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prescriptions', function (Blueprint $table) {
            if (Schema::hasColumn('prescriptions', 'next_visit_day')) {
                $table->dropColumn('next_visit_day');
            }
            if (Schema::hasColumn('prescriptions', 'next_medicine_day')) {
                $table->dropColumn('next_medicine_day');
            }
        });
    }
};
