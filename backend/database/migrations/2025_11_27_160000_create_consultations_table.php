<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('consultations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pet_id')->nullable()->constrained('pets')->nullOnDelete();
            $table->unsignedBigInteger('clinic_id')->nullable()->index();
            $table->foreignId('doctor_id')->nullable()->constrained('doctors')->nullOnDelete();
            $table->unsignedBigInteger('booking_id')->nullable()->index();
            $table->unsignedBigInteger('call_session_id')->nullable()->index();
            $table->enum('mode', ['video', 'in_clinic'])->default('video');
            $table->dateTime('start_time');
            $table->dateTime('end_time')->nullable();
            $table->string('status')->default('scheduled');
            $table->timestamp('user_joined_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('no_show_marked_at')->nullable();
            $table->integer('follow_up_after_days')->nullable();
            $table->timestamp('follow_up_due_at')->nullable();
            $table->timestamp('reminder_24h_sent_at')->nullable();
            $table->timestamp('reminder_3h_sent_at')->nullable();
            $table->timestamp('reminder_30m_sent_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consultations');
    }
};
