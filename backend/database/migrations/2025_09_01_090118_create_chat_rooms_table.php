<?php
// database/migrations/xxxx_xx_xx_xxxxxx_create_chat_rooms_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('chat_rooms', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->index();
            $table->string('chat_room_token', 80)->unique();
            $table->string('name', 150)->nullable(); // display name (title)
            $table->timestamps();

            $table->index(['user_id', 'updated_at'], 'idx_chatrooms_user_updated');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_rooms');
    }
};
