<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('chat_service_bookings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('booking_reference', 40)->unique();
            $table->string('session_id', 100)->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->unsignedBigInteger('pet_id')->nullable()->index();
            $table->enum('consultation_type', ['video', 'clinic', 'home'])->index();
            $table->enum('booking_status', ['pending', 'confirmed', 'cancelled', 'completed'])->default('confirmed')->index();
            $table->string('slot_id', 191)->nullable()->index();
            $table->date('scheduled_date')->nullable()->index();
            $table->time('scheduled_time')->nullable();
            $table->dateTime('scheduled_for')->nullable()->index();
            $table->string('timezone', 64)->default('Asia/Kolkata');
            $table->unsignedBigInteger('doctor_id')->nullable()->index();
            $table->unsignedBigInteger('clinic_id')->nullable()->index();
            $table->string('external_place_id', 191)->nullable()->index();
            $table->string('clinic_name', 255)->nullable();
            $table->string('doctor_name', 255)->nullable();
            $table->text('address')->nullable();
            $table->string('phone', 60)->nullable();
            $table->text('maps_link')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->string('currency', 10)->default('INR');
            $table->string('source_tool', 60)->nullable();
            $table->json('booking_payload')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_service_bookings');
    }
};
