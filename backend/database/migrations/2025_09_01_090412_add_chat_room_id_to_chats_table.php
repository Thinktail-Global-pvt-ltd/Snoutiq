<?php
// database/migrations/xxxx_xx_xx_xxxxxx_add_chat_room_id_to_chats_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('chats', function (Blueprint $table) {
            if (!Schema::hasColumn('chats', 'chat_room_id')) {
                $table->unsignedBigInteger('chat_room_id')->nullable()->after('user_id');
                $table->foreign('chat_room_id')->references('id')->on('chat_rooms')->cascadeOnDelete();
                $table->index(['chat_room_id', 'created_at'], 'idx_chats_room_time');
            }
        });
    }

    public function down(): void
    {
        Schema::table('chats', function (Blueprint $table) {
            if (Schema::hasColumn('chats', 'chat_room_id')) {
                $table->dropForeign(['chat_room_id']);
                $table->dropIndex('idx_chats_room_time');
                $table->dropColumn('chat_room_id');
            }
        });
    }
};
