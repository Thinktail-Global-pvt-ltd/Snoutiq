<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('chat_service_bookings')) {
            return;
        }

        Schema::create('chat_service_bookings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->unsignedBigInteger('pet_id')->nullable()->index();
            $table->unsignedBigInteger('vet_registeration_id')->index();
            $table->unsignedBigInteger('doctor_id')->index();
            $table->string('chat_room_token', 120)->nullable()->index();
            $table->string('context_token', 120)->nullable()->index();
            $table->string('service_type', 50)->nullable();
            $table->date('appointment_date')->nullable();
            $table->string('appointment_time', 50)->nullable();
            $table->text('notes')->nullable();
            $table->json('source_payload')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_service_bookings');
    }
};
