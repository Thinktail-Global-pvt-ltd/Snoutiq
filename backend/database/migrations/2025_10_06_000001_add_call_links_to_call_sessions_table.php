<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('call_sessions', function (Blueprint $table) {
            if (!Schema::hasColumn('call_sessions', 'call_identifier')) {
                $table->string('call_identifier', 64)->nullable()->after('channel_name')->index();
            }

            if (!Schema::hasColumn('call_sessions', 'doctor_join_url')) {
                $table->text('doctor_join_url')->nullable()->after('call_identifier');
            }

            if (!Schema::hasColumn('call_sessions', 'patient_payment_url')) {
                $table->text('patient_payment_url')->nullable()->after('doctor_join_url');
            }
        });
    }

    public function down(): void
    {
        Schema::table('call_sessions', function (Blueprint $table) {
            if (Schema::hasColumn('call_sessions', 'patient_payment_url')) {
                $table->dropColumn('patient_payment_url');
            }

            if (Schema::hasColumn('call_sessions', 'doctor_join_url')) {
                $table->dropColumn('doctor_join_url');
            }

            if (Schema::hasColumn('call_sessions', 'call_identifier')) {
                $table->dropIndex(['call_identifier']);
                $table->dropColumn('call_identifier');
            }
        });
    }
};
