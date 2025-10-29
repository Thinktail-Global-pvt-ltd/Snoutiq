<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('call_sessions', function (Blueprint $table) {
            if (!Schema::hasColumn('call_sessions', 'accepted_at')) {
                $table->timestamp('accepted_at')->nullable()->after('status');
            }

            if (!Schema::hasColumn('call_sessions', 'started_at')) {
                $table->timestamp('started_at')->nullable()->after('accepted_at');
            }

            if (!Schema::hasColumn('call_sessions', 'ended_at')) {
                $table->timestamp('ended_at')->nullable()->after('started_at');
            }

            if (!Schema::hasColumn('call_sessions', 'duration_seconds')) {
                $table->unsignedInteger('duration_seconds')->default(0)->after('ended_at');
            }

            if (!Schema::hasColumn('call_sessions', 'payment_id')) {
                $table->foreignId('payment_id')->nullable()->after('duration_seconds')->constrained('payments')->nullOnDelete();
            }

            if (!Schema::hasColumn('call_sessions', 'amount_paid')) {
                $table->unsignedBigInteger('amount_paid')->nullable()->after('payment_id');
            }

            if (!Schema::hasColumn('call_sessions', 'currency')) {
                $table->string('currency', 10)->default('INR')->after('amount_paid');
            }

            $table->index('patient_id');
            $table->index('doctor_id');
            $table->index('payment_status');
        });
    }

    public function down(): void
    {
        Schema::table('call_sessions', function (Blueprint $table) {
            if (Schema::hasColumn('call_sessions', 'currency')) {
                $table->dropColumn('currency');
            }

            if (Schema::hasColumn('call_sessions', 'amount_paid')) {
                $table->dropColumn('amount_paid');
            }

            if (Schema::hasColumn('call_sessions', 'payment_id')) {
                $table->dropForeign(['payment_id']);
                $table->dropColumn('payment_id');
            }

            if (Schema::hasColumn('call_sessions', 'duration_seconds')) {
                $table->dropColumn('duration_seconds');
            }

            if (Schema::hasColumn('call_sessions', 'ended_at')) {
                $table->dropColumn('ended_at');
            }

            if (Schema::hasColumn('call_sessions', 'started_at')) {
                $table->dropColumn('started_at');
            }

            if (Schema::hasColumn('call_sessions', 'accepted_at')) {
                $table->dropColumn('accepted_at');
            }

            $table->dropIndex(['patient_id']);
            $table->dropIndex(['doctor_id']);
            $table->dropIndex(['payment_status']);
        });
    }
};
