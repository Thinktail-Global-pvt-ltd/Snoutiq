<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            if (! Schema::hasColumn('transactions', 'doctor_id')) {
                $table->unsignedBigInteger('doctor_id')->nullable()->after('clinic_id')->index();
            }

            if (! Schema::hasColumn('transactions', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('doctor_id')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            if (Schema::hasColumn('transactions', 'user_id')) {
                $table->dropColumn('user_id');
            }

            if (Schema::hasColumn('transactions', 'doctor_id')) {
                $table->dropColumn('doctor_id');
            }
        });
    }
};
