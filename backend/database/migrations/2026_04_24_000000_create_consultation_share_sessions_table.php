<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consultation_share_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('session_token', 64)->unique();
            $table->unsignedBigInteger('clinic_id')->nullable()->index();
            $table->unsignedBigInteger('doctor_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->unsignedBigInteger('pet_id')->nullable()->index();
            $table->string('parent_name')->nullable();
            $table->string('parent_phone', 25)->index();
            $table->string('pet_name')->nullable();
            $table->string('pet_type', 120)->nullable();
            $table->string('pet_breed', 120)->nullable();
            $table->unsignedInteger('amount_paise')->default(49900);
            $table->unsignedSmallInteger('response_time_minutes')->default(10);
            $table->string('status', 30)->default('pending')->index();
            $table->string('razorpay_payment_link_id', 120)->nullable()->index();
            $table->text('razorpay_payment_link_url')->nullable();
            $table->string('razorpay_short_code', 120)->nullable();
            $table->timestamp('initiated_at')->nullable();
            $table->timestamp('payment_link_sent_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('last_inbound_message_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consultation_share_sessions');
    }
};
