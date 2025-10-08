<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vet_registeration_id');
            $table->unsignedBigInteger('doctor_id')->nullable();
            $table->string('name');
            $table->string('mobile', 20);
            $table->string('pet_name')->nullable();
            $table->date('appointment_date');
            $table->string('appointment_time', 16); // HH:MM
            $table->string('status', 24)->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('vet_registeration_id')->references('id')->on('vet_registerations_temp')->onDelete('cascade');
            $table->foreign('doctor_id')->references('id')->on('doctors')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};

