<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('chats', function (Blueprint $table) {
            if (!Schema::hasColumn('chats', 'diagnosis')) {
                $table->longText('diagnosis')->nullable();
            }
            if (!Schema::hasColumn('chats', 'emergency_status')) {
                $table->string('emergency_status', 20)->nullable()->index();
            }
            if (!Schema::hasColumn('chats', 'response_tag')) {
                $table->string('response_tag', 20)->nullable()->index();
            }
        });

        Schema::table('chat_rooms', function (Blueprint $table) {
            if (!Schema::hasColumn('chat_rooms', 'last_emergency_status')) {
                $table->string('last_emergency_status', 20)->nullable()->index();
            }
        });
    }

    public function down(): void {
        Schema::table('chats', function (Blueprint $table) {
            if (Schema::hasColumn('chats', 'diagnosis')) $table->dropColumn('diagnosis');
            if (Schema::hasColumn('chats', 'emergency_status')) $table->dropColumn('emergency_status');
            if (Schema::hasColumn('chats', 'response_tag')) $table->dropColumn('response_tag');
        });

        Schema::table('chat_rooms', function (Blueprint $table) {
            if (Schema::hasColumn('chat_rooms', 'last_emergency_status')) $table->dropColumn('last_emergency_status');
        });
    }
};
