<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doctor_chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doctor_chat_room_id')->constrained('doctor_chat_rooms')->cascadeOnDelete();
            $table->enum('sender_type', ['user', 'doctor']);
            $table->unsignedBigInteger('sender_id');
            $table->text('message');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['doctor_chat_room_id', 'id']);
            $table->index(['sender_type', 'sender_id']);
            $table->index('read_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doctor_chat_messages');
    }
};
