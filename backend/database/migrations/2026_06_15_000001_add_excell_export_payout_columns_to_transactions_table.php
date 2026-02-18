<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('transactions')) {
            return;
        }

        Schema::table('transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('transactions', 'actual_amount_paid_by_consumer_paise')) {
                $table->unsignedBigInteger('actual_amount_paid_by_consumer_paise')->nullable();
            }

            if (!Schema::hasColumn('transactions', 'payment_to_snoutiq_paise')) {
                $table->unsignedBigInteger('payment_to_snoutiq_paise')->nullable();
            }

            if (!Schema::hasColumn('transactions', 'payment_to_doctor_paise')) {
                $table->unsignedBigInteger('payment_to_doctor_paise')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('transactions')) {
            return;
        }

        Schema::table('transactions', function (Blueprint $table) {
            if (Schema::hasColumn('transactions', 'payment_to_doctor_paise')) {
                $table->dropColumn('payment_to_doctor_paise');
            }

            if (Schema::hasColumn('transactions', 'payment_to_snoutiq_paise')) {
                $table->dropColumn('payment_to_snoutiq_paise');
            }

            if (Schema::hasColumn('transactions', 'actual_amount_paid_by_consumer_paise')) {
                $table->dropColumn('actual_amount_paid_by_consumer_paise');
            }
        });
    }
};
