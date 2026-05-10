<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinic_specialized_packages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('clinic_id');
            $table->unsignedBigInteger('doctor_id');
            $table->decimal('dog_vaccination_package_price', 10, 2)->nullable();
            $table->decimal('cat_vaccination_package_price', 10, 2)->nullable();
            $table->decimal('dog_neutering_price', 10, 2)->nullable();
            $table->decimal('cat_neutering_price', 10, 2)->nullable();
            $table->timestamps();

            $table->unique(['clinic_id', 'doctor_id'], 'clinic_specialized_packages_clinic_doctor_unique');

            $table->foreign('clinic_id')
                ->references('id')
                ->on('vet_registerations_temp')
                ->cascadeOnDelete();

            $table->foreign('doctor_id')
                ->references('id')
                ->on('doctors')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinic_specialized_packages');
    }
};
