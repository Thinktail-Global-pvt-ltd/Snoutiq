<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vet_at_home_services', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('clinic_id');
            $table->unsignedBigInteger('doctor_id')->nullable();
            $table->boolean('is_enabled')->default(false);
            $table->string('service_hours')->nullable();
            $table->string('response_time')->nullable();
            $table->decimal('base_payout', 10, 2)->nullable();
            $table->string('protocol_label')->nullable();
            $table->timestamps();

            $table->unique(['clinic_id', 'doctor_id'], 'vet_at_home_services_clinic_doctor_unique');

            $table->foreign('clinic_id')
                ->references('id')
                ->on('vet_registerations_temp')
                ->cascadeOnDelete();

            $table->foreign('doctor_id')
                ->references('id')
                ->on('doctors')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vet_at_home_services');
    }
};
