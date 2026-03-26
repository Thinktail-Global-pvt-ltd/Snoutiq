<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('fcm_notifications')) {
            return;
        }

        Schema::table('fcm_notifications', function (Blueprint $table) {
            if (!Schema::hasColumn('fcm_notifications', 'clicked')) {
                $table->boolean('clicked')->default(false)->index();
            }
            if (!Schema::hasColumn('fcm_notifications', 'clicked_at')) {
                $table->timestamp('clicked_at')->nullable()->index();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('fcm_notifications')) {
            return;
        }

        Schema::table('fcm_notifications', function (Blueprint $table) {
            if (Schema::hasColumn('fcm_notifications', 'clicked_at')) {
                $table->dropColumn('clicked_at');
            }
            if (Schema::hasColumn('fcm_notifications', 'clicked')) {
                $table->dropColumn('clicked');
            }
        });
    }
};
