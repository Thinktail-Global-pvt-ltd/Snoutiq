<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('doctor_chat_rooms')) {
            return;
        }

        Schema::table('doctor_chat_rooms', function (Blueprint $table) {
            if (!Schema::hasColumn('doctor_chat_rooms', 'channel_name')) {
                $table->string('channel_name', 191)
                    ->nullable()
                    ->after('doctor_id')
                    ->index();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('doctor_chat_rooms') || !Schema::hasColumn('doctor_chat_rooms', 'channel_name')) {
            return;
        }

        Schema::table('doctor_chat_rooms', function (Blueprint $table) {
            $table->dropColumn('channel_name');
        });
    }
};
