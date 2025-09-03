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
        Schema::create('user_ai_chat_histories', function (Blueprint $table) {
            $table->id();
            $table->string("type");
            $table->longText("message");
        
           $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
        $table->foreignId('user_ai_chat_id')->constrained('user_ai_chats')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_ai_chat_histories');
    }
};
