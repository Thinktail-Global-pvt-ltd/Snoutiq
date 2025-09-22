<?php

// database/migrations/xxxx_xx_xx_xxxxxx_add_summary_to_chat_rooms.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('chat_rooms', function (Blueprint $table) {
            $table->longText('summary')->nullable()->after('last_emergency_status');
        });
    }

    public function down(): void {
        Schema::table('chat_rooms', function (Blueprint $table) {
            $table->dropColumn('summary');
        });
    }
};
