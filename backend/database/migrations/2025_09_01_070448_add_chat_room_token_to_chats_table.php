<?php
// database/migrations/xxxx_xx_xx_xxxxxx_add_chat_room_token_to_chats_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('chats', function (Blueprint $table) {
            // room token for grouping messages into a room
            $table->string('chat_room_token', 80)->nullable()->index()->after('user_id');
            // speed up queries
            $table->index(['user_id', 'chat_room_token', 'created_at'], 'idx_user_room_time');
        });
    }

    public function down(): void
    {
        Schema::table('chats', function (Blueprint $table) {
            $table->dropIndex('idx_user_room_time');
            $table->dropColumn('chat_room_token');
        });
    }
};
