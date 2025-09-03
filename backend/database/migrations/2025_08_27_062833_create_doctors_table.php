<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doctors', function (Blueprint $table) {
            $table->id();

            // Foreign key to vet_registerations_temp
            $table->unsignedBigInteger('vet_registeration_id');

            // Doctor fields
            $table->string('doctor_name');
            $table->string('doctor_email')->nullable();
            $table->string('doctor_mobile')->nullable();
            $table->string('doctor_license')->nullable();
            $table->string('doctor_image')->nullable(); // store image path

            $table->timestamps();

            // Foreign key constraint
            $table->foreign('vet_registeration_id')
                  ->references('id')
                  ->on('vet_registerations_temp')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doctors');
    }
};
