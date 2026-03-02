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
            if (! Schema::hasColumn('pets', 'last_deworming_date')) {
                $table->date('last_deworming_date')->nullable()->after('deworming_yes_no');
            }

            if (! Schema::hasColumn('pets', 'deworming_status')) {
                $table->string('deworming_status', 50)->nullable()->after('last_deworming_date');
            }

            if (! Schema::hasColumn('pets', 'next_deworming_date')) {
                $table->date('next_deworming_date')->nullable()->after('deworming_status');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('pets')) {
            return;
        }

        Schema::table('pets', function (Blueprint $table) {
            $dropColumns = [];

            if (Schema::hasColumn('pets', 'next_deworming_date')) {
                $dropColumns[] = 'next_deworming_date';
            }

            if (Schema::hasColumn('pets', 'deworming_status')) {
                $dropColumns[] = 'deworming_status';
            }

            if (Schema::hasColumn('pets', 'last_deworming_date')) {
                $dropColumns[] = 'last_deworming_date';
            }

            if ($dropColumns !== []) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};
