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
        Schema::create('medical_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('doctor_id')->nullable();
            $table->unsignedBigInteger('vet_registeration_id')->nullable();
            $table->string('file_path');
            $table->string('file_name')->nullable();
            $table->string('mime_type', 150)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('user_id')
                ->references('id')->on('users')
                ->onDelete('cascade');

            $table->foreign('doctor_id')
                ->references('id')->on('doctors')
                ->nullOnDelete();

            $table->foreign('vet_registeration_id')
                ->references('id')->on('vet_registerations_temp')
                ->nullOnDelete();

            $table->index(['user_id', 'vet_registeration_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medical_records');
    }
};
