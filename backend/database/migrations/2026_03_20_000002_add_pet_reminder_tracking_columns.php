<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('pets')) {
            return;
        }

        Schema::table('pets', function (Blueprint $table) {
            if (! Schema::hasColumn('pets', 'neutering_reminder_sent_at')) {
                $table->timestamp('neutering_reminder_sent_at')
                    ->nullable()
                    ->index();
            }

            if (! Schema::hasColumn('pets', 'vaccination_upcoming_reminder_sent_at')) {
                $table->timestamp('vaccination_upcoming_reminder_sent_at')
                    ->nullable()
                    ->index();
            }

            if (! Schema::hasColumn('pets', 'vaccination_upcoming_reminder_due_date')) {
                $table->date('vaccination_upcoming_reminder_due_date')
                    ->nullable()
                    ->index();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('pets')) {
            return;
        }

        Schema::table('pets', function (Blueprint $table) {
            if (Schema::hasColumn('pets', 'vaccination_upcoming_reminder_due_date')) {
                $table->dropColumn('vaccination_upcoming_reminder_due_date');
            }

            if (Schema::hasColumn('pets', 'vaccination_upcoming_reminder_sent_at')) {
                $table->dropColumn('vaccination_upcoming_reminder_sent_at');
            }

            if (Schema::hasColumn('pets', 'neutering_reminder_sent_at')) {
                $table->dropColumn('neutering_reminder_sent_at');
            }
        });
    }
};
