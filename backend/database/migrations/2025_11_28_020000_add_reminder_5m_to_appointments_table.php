<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasColumn('appointments', 'reminder_5m_sent_at')) {
            Schema::table('appointments', function (Blueprint $table) {
                $table->timestamp('reminder_5m_sent_at')->nullable()->after('reminder_30m_sent_at');
            });
        }
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn('reminder_5m_sent_at');
        });
    }
};
