<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doctor_chat_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('doctor_id')->constrained('doctors')->cascadeOnDelete();
            $table->timestamp('last_message_at')->nullable()->index();
            $table->timestamps();

            $table->unique(['user_id', 'doctor_id']);
            $table->index(['user_id', 'last_message_at']);
            $table->index(['doctor_id', 'last_message_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doctor_chat_rooms');
    }
};
