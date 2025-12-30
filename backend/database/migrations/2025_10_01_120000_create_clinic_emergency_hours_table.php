<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('clinic_emergency_hours')) {
            return;
        }

        Schema::create('clinic_emergency_hours', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('clinic_id');
            $table->json('doctor_ids')->nullable();
            $table->json('doctor_slot_map')->nullable();
            $table->json('night_slots')->nullable();
            $table->decimal('consultation_price', 10, 2)->nullable();
            $table->timestamps();

            $table->unique('clinic_id');
            $table->foreign('clinic_id')->references('id')->on('vet_registerations_temp')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clinic_emergency_hours');
    }
};
