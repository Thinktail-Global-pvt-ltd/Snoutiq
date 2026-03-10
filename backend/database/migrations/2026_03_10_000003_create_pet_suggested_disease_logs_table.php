<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pet_suggested_disease_logs')) {
            return;
        }

        Schema::create('pet_suggested_disease_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pet_id')->index();
            $table->text('last_reported_symptom')->nullable();
            $table->text('current_reported_symptom')->nullable();
            $table->string('previous_suggested_disease', 255)->nullable();
            $table->string('new_suggested_disease', 255)->nullable();
            $table->string('source', 120)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pet_suggested_disease_logs');
    }
};
