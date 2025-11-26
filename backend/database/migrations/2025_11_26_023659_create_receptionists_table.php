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
        Schema::create('receptionists', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vet_registeration_id');
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('role', 50)->default('receptionist');
            $table->string('status', 50)->default('active');
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->foreign('vet_registeration_id')
                ->references('id')
                ->on('vet_registerations_temp')
                ->onDelete('cascade');
            $table->index(['vet_registeration_id', 'role']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('receptionists');
    }
};
