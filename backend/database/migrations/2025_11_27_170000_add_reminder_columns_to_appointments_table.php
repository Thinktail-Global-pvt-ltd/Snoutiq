<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->timestamp('reminder_24h_sent_at')->nullable()->after('notes');
            $table->timestamp('reminder_3h_sent_at')->nullable()->after('reminder_24h_sent_at');
            $table->timestamp('reminder_30m_sent_at')->nullable()->after('reminder_3h_sent_at');
            $table->timestamp('reminder_5m_sent_at')->nullable()->after('reminder_30m_sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn([
                'reminder_24h_sent_at',
                'reminder_3h_sent_at',
                'reminder_30m_sent_at',
                'reminder_5m_sent_at',
            ]);
        });
    }
};
