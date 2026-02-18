<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('transaction_doctor_assignment_logs')) {
            return;
        }

        Schema::create('transaction_doctor_assignment_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('transaction_id')->index();
            $table->unsignedBigInteger('previous_doctor_id')->nullable()->index();
            $table->unsignedBigInteger('new_doctor_id')->nullable()->index();
            $table->unsignedBigInteger('previous_clinic_id')->nullable()->index();
            $table->unsignedBigInteger('new_clinic_id')->nullable()->index();
            $table->unsignedBigInteger('changed_by_user_id')->nullable()->index();
            $table->string('changed_by_name')->nullable();
            $table->timestamps();

            $table->foreign('transaction_id')
                ->references('id')
                ->on('transactions')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('transaction_doctor_assignment_logs')) {
            return;
        }

        Schema::table('transaction_doctor_assignment_logs', function (Blueprint $table) {
            $table->dropForeign(['transaction_id']);
        });

        Schema::dropIfExists('transaction_doctor_assignment_logs');
    }
};
