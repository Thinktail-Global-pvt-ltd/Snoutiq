<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('call_sessions', function (Blueprint $table) {
            if (!Schema::hasColumn('call_sessions', 'push_notified_at')) {
                $table->timestamp('push_notified_at')->nullable()->after('accepted_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('call_sessions', function (Blueprint $table) {
            if (Schema::hasColumn('call_sessions', 'push_notified_at')) {
                $table->dropColumn('push_notified_at');
            }
        });
    }
};
