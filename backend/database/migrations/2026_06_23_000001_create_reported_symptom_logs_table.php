<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reported_symptom_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pet_id')->nullable()->index();
            $table->unsignedBigInteger('doctor_id')->nullable()->index();
            $table->unsignedBigInteger('transaction_id')->nullable()->unique();
            $table->text('reported_symptom')->nullable();
            $table->timestamps();

            $table->index(['pet_id', 'created_at']);
            $table->index(['doctor_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reported_symptom_logs');
    }
};
