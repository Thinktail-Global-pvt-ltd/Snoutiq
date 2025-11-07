<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('scheduled_push_notifications', function (Blueprint $table) {
            if (!Schema::hasColumn('scheduled_push_notifications', 'is_active')) {
                $table->boolean('is_active')->default(false)->index();
            }
            if (!Schema::hasColumn('scheduled_push_notifications', 'next_run_at')) {
                $table->dateTime('next_run_at')->nullable()->index();
            }
            if (!Schema::hasColumn('scheduled_push_notifications', 'last_run_at')) {
                $table->dateTime('last_run_at')->nullable()->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('scheduled_push_notifications', function (Blueprint $table) {
            if (Schema::hasColumn('scheduled_push_notifications', 'is_active')) {
                $table->dropColumn('is_active');
            }
            if (Schema::hasColumn('scheduled_push_notifications', 'next_run_at')) {
                $table->dropColumn('next_run_at');
            }
            if (Schema::hasColumn('scheduled_push_notifications', 'last_run_at')) {
                $table->dropColumn('last_run_at');
            }
        });
    }
};

