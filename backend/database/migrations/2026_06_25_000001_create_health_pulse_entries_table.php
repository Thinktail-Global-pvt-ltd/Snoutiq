<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('health_pulse_entries')) {
            return;
        }

        Schema::create('health_pulse_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('pet_id');
            $table->date('entry_date');
            $table->string('food', 40)->nullable();
            $table->string('energy', 40)->nullable();
            $table->string('water', 40)->nullable();
            $table->text('symptoms')->nullable();
            $table->boolean('digestion_issue')->nullable();
            $table->string('digestion_note', 255)->nullable();
            $table->string('ai_flag_level', 20)->default('None');
            $table->text('ai_short_summary')->nullable();
            $table->text('ai_pattern_observation')->nullable();
            $table->text('ai_recommended_action')->nullable();
            $table->json('ai_payload')->nullable();
            $table->timestamp('ai_analyzed_at')->nullable();
            $table->timestamps();

            $table->unique(['pet_id', 'entry_date'], 'health_pulse_pet_date_unique');
            $table->index(['user_id', 'entry_date']);
            $table->index(['pet_id', 'entry_date']);
            $table->index(['pet_id', 'ai_flag_level']);

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('pet_id')->references('id')->on('pets')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('health_pulse_entries');
    }
};
