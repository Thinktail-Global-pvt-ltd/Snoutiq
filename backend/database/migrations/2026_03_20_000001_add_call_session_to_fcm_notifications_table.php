<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('fcm_notifications')) {
            return;
        }

        Schema::table('fcm_notifications', function (Blueprint $table) {
            if (! Schema::hasColumn('fcm_notifications', 'call_session')) {
                $table->string('call_session', 255)
                    ->nullable()
                    ->index()
                    ->after('user_id');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('fcm_notifications')) {
            return;
        }

        Schema::table('fcm_notifications', function (Blueprint $table) {
            if (Schema::hasColumn('fcm_notifications', 'call_session')) {
                $table->dropColumn('call_session');
            }
        });
    }
};
