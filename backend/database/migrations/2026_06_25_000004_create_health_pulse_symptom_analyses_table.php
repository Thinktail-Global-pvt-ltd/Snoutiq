<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('health_pulse_symptom_analyses')) {
            return;
        }

        Schema::create('health_pulse_symptom_analyses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('pet_id');
            $table->unsignedBigInteger('health_pulse_entry_id');
            $table->date('entry_date');
            $table->unsignedInteger('symptom_entry_count')->default(0);
            $table->json('symptoms_snapshot')->nullable();
            $table->text('analysis_text')->nullable();
            $table->string('flag_level', 20)->default('None');
            $table->text('recommended_action')->nullable();
            $table->json('ai_payload')->nullable();
            $table->timestamp('analyzed_at')->nullable();
            $table->timestamps();

            $table->unique('health_pulse_entry_id', 'health_pulse_symptom_analysis_entry_unique');
            $table->index(['pet_id', 'entry_date']);
            $table->index(['pet_id', 'flag_level']);

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('pet_id')->references('id')->on('pets')->onDelete('cascade');
            $table->foreign('health_pulse_entry_id')->references('id')->on('health_pulse_entries')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('health_pulse_symptom_analyses');
    }
};
